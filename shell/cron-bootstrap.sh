#!/bin/bash

# Set User Environment
. ${HOME}/.bash_profile

cm

ISO_DATE=$(date --iso-8601=seconds)
SCRIPT_PATH=$0
HOME_DIR=$(pwd)
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*${HOME_DIR}}

printf "Started: %s @ %s\n" ."${ABBR_SCRIPT_PATH}" "${ISO_DATE}"

# Set some bash flags that also apply to the rest of the parent script
set -e          # Exit immediately if a command exits with a non-zero status
set -u          # Treat unset variables as an error when substituting
