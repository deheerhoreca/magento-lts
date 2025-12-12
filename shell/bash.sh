#!/bin/bash

# OpenMage-related functions &c that gets sourced in bash profiles:

# Change to the openmage directory
function cm() {
  local SCRIPT_DIR
  SCRIPT_DIR=$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")
  cd "${SCRIPT_DIR}/.." || return 1
}
export -f cm
