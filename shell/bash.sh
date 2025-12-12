#!/bin/bash

# OpenMage-related functions &c that gets sourced in bash profiles:

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
  command php -c etc/php.cmd.ini "$@"
}
export -f openmage
