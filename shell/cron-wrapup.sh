#!/bin/bash

ISO_DATE=$(date --iso-8601=seconds)
SCRIPT_PATH=$0
HOME_DIR=$(pwd)
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*${HOME_DIR}}

printf "Finished: %s @ %s\n" ."${ABBR_SCRIPT_PATH}" "${ISO_DATE}"
