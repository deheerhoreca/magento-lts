---
name: "DeHeerHoreca/OpenMage development guide"
description: "Onboarding notes for coding agents working in DeHeerHoreca/OpenMage."
category: "E-commerce platform"
tags: ["php", "magento1", "openmage", "ecommerce", "shell", "cron"]
lastUpdated: "2026-06-04"
---

# DeHeerHoreca/OpenMage development guide

## Purpose (WHY)

- OpenMage (a maintained Magento 1.x fork) is the storefront platform for
  DeHeerHoreca. Most runs touch production data, so prefer safe-by-default,
  observable changes.

> This guide currently focuses on shell-script conventions and the commit
> quality gate. Expand it with Magento/PHP specifics (modules, indexers,
> deploy flow) as they get documented in-repo.

## Shell Scripts & Commit Quality Gate

`shell/` mixes PHP CLI scripts (`*.php`) and bash helpers (`om-*`, `cron-*.sh`,
`*.sh`). The bash scripts are linted by a git pre-commit hook (PHP files are
skipped). Two footguns that **shellcheck and code review both routinely miss**
are documented here because they have bitten us — check for them by hand.

### Footgun 1 — `read` stealing a loop's stdin (fd inheritance)

A `while ... read` loop fed by a pipe or process substitution redirects the
*entire loop body's* stdin to that pipe. Any `read` (or stdin-consuming command
like `ssh`/`ffmpeg`) called inside the body — even several calls deep — then
consumes the pipe instead of the keyboard.

```bash
# BAD: the prompt's `read` eats filenames from find, not the user's answer.
while IFS= read -r f; do
  confirm_and_do "$f"        # contains: read -rp "> " answer
done < <(find . | sort)
```

Rule: interactive reads must use `read ... </dev/tty`, or feed the loop on a
dedicated fd (`while read -r f <&3; do ...; done 3< <(find ...)`). Same hazard
for child processes that read stdin — use `ssh -n` / redirect. Not bash-only: in
Python, `subprocess` inherits fd 0 too — pass `stdin=subprocess.DEVNULL`.

### Footgun 2 — dangerous defaults turn malfunctions silent

An empty/EOF read that defaults to "yes"/"run" turns the bug above (and any
non-interactive invocation) into *silent* action. For anything destructive the
safe outcome (skip/no/quit) must be the fallback, and the script should guard
interactivity with `[ -t 0 ]`. Fail closed.

### Linting

- `.githooks/pre-commit` runs `shellcheck --severity=warning` on staged shell
  scripts (detected by extension or shebang; binaries skipped). `.shellcheckrc`
  tunes down opinionated style checks. Neither catches the two footguns above.
- Activate once per clone: `./.githooks/install` (sets `core.hooksPath`;
  idempotent). Bypass a single commit with `git commit --no-verify`.
