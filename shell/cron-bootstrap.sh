#!/bin/bash

# Set User Environment
. ${HOME}/.bash_profile

# Set some bash flags that also apply to the rest of the parent script
set -e          # Exit immediately if a command exits with a non-zero status
set -u          # Treat unset variables as an error when substituting

# BC only. Use phpenv to control PHP versions:
alias mphp=php
alias iphp=php
alias tphp=php
alias ephp=php

cm

# Bash trick to not crash the script if the variable is not set yet:
THIS_IS_CRON=${THIS_IS_CRON:-false}
VERBOSE=${VERBOSE:-false}
DRYRUN=${DRYRUN:-false}
PROFILE=${PROFILE:-false}
PREFER_HOST=${PREFER_HOST:-}
NO_DEV=${NO_DEV:-1}
REQUIRE_HOST=${REQUIRE_HOST:-}
ARGS=

ISO_DATE=$(TZ="Europe/Amsterdam" date +"%F %T")
SCRIPT_PATH=${0}
THE_CWD="$(pwd)/"
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*"${THE_CWD}"}
CURRENT_CRON_CMD="${ABBR_SCRIPT_PATH} ${*}"

# Check preferred host against current host and CRON
if [[ -n "${PREFER_HOST}" && ${THIS_IS_CRON} ]]; then
  if [[ "${HOSTNAME}" != "${PREFER_HOST}.deheerhoreca.nl" && "${HOSTNAME}" != "dev.deheerhoreca.nl" ]]; then
    printf "%s  NOOP   %s  prefers to run on %s.deheerhoreca.nl\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}" "${PREFER_HOST}"
    exit 0
  fi
fi

# Check required host against current host regardless of CRON
if [ -n "${REQUIRE_HOST}" ]; then
  if [[ "${HOSTNAME}" != "${REQUIRE_HOST}.deheerhoreca.nl" && "${HOSTNAME}" != "dev.deheerhoreca.nl" ]]; then
    if [[ ! ${THIS_IS_CRON} ]]; then
      printf "%s  NOOP   %s  only runs on %s.deheerhoreca.nl\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}" "${REQUIRE_HOST}"
    else
      # Uncomment during development:
      printf "%s  NOOP   %s  only runs on %s.deheerhoreca.nl\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}" "${REQUIRE_HOST}"
      :
    fi
    exit 0
  fi
fi

# Wait for the above checks to complete before printing the start message
if ${THIS_IS_CRON}; then
  printf "%s  NOOP   %s  is skipped in development environments\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}"
fi

printf "%s  START  %s\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}"
