#!/bin/bash

# Get the current date in ISO 8601 format
ISO_DATE=$(date --iso-8601=seconds)

# Basename of the called Shell script
SCRIPT_PATH=$0

# Home dir of the current user
HOME_DIR=$(pwd)

# Shell script path relative to the home directory
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*${HOME_DIR}}

# Wait for the above checks to complete before printing the start message -- Max 110 chars
if ${THIS_IS_CRON}; then
  echo "----------- Finish ${ABBR_SCRIPT_PATH} @ ${ISO_DATE} (CRON) -----------"
fi
