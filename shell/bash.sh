#!/bin/bash

# OpenMage-related functions &c that gets sourced in bash profiles:

# Unset any broken function imports before redefining -- for sub shells like
# when Amp opens one and inherits the parent shell's functions. Errors such as:
# - /bin/bash: error importing function definition for `BASH_FUNC_io'
# - /bin/bash: dos2unix_openmage: line 1: syntax error: unexpected end of file
unset -f dos2unix_openmage cm openmage omindexer 2>/dev/null

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
export -f dos2unix_openmage

# Change to the openmage directory
function cm() {
  local SCRIPT_DIR
  SCRIPT_DIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")
  cd "${SCRIPT_DIR}/.." || return 1
}
export -f cm

# Run a OpenMage command -- Note that the PHP entry point script is not part of the alias
function openmage() {
  cm || exit 1
  command php -c etc/php.ini "$@"
}
export -f openmage

# The OpenMage indexer
function omindexer() {
  cm || exit 1
  openmage shell/indexer.php "$@"
}
export -f omindexer
