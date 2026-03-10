#!/bin/bash

# OpenMage-related functions &c that gets sourced in bash profiles:

# Do NOT use `export -f` — exported functions get inherited as BASH_FUNC_*
# environment variables, which break in subshells (e.g. Amp) that can't parse
# the truncated definitions. Just define; sourcing this file is sufficient.

function dos2unix_openmage {
  cm
  find . \
    -wholename "./.git/*" -prune \
    -o -wholename "./js/tinymce/*" -prune \
    -o -wholename "./media/*" -prune \
    -o -wholename "./skin/frontend/rwd/external/*" -prune \
    -o -wholename "./vendor/openmage/magento-lts/js/tinymce/*" -prune \
    -o -type f \
    -exec grep -I -q . {} \; \
    -exec file "{}" ";" \
    | grep --color=always CRLF
  return 0
}

# Change to the openmage directory
function cm() {
  local SCRIPT_DIR
  SCRIPT_DIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")
  cd "${SCRIPT_DIR}/.." || return 1
}

# Run an OpenMage command -- Note that the PHP entry point script is not part of the alias
function openmage() {
  cm || exit 1
  command php -c etc/php.ini "$@"
}

# The OpenMage indexer
function omindexer() {
  cm || exit 1
  openmage shell/indexer.php "$@"
}
