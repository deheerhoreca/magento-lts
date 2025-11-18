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
PREFER_HOST=${PREFER_HOST:-}
REQUIRE_HOST=${REQUIRE_HOST:-}
export ARGS=

# Get the current date in ISO 8601 format
ISO_DATE=$(date --iso-8601=seconds)

# Basename of the called Shell script
SCRIPT_PATH=$0

# Home dir of the current user
HOME_DIR=$(pwd)

# Shell script path relative to the home directory
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*"${HOME_DIR}"}

# Check preferred host against current host and CRON
if [[ -n "${PREFER_HOST}" && ${THIS_IS_CRON} ]]; then
  if [[ "${HOSTNAME}" != "${PREFER_HOST}.deheerhoreca.nl" && "${HOSTNAME}" != "dev.deheerhoreca.nl" ]]; then
    echo "This script prefers to run on ${PREFER_HOST}.deheerhoreca.nl"
    exit 0
  fi
fi

# Check required host against current host regardless of CRON
if [ -n "${REQUIRE_HOST}" ]; then
  if [[ "${HOSTNAME}" != "${REQUIRE_HOST}.deheerhoreca.nl" && "${HOSTNAME}" != "dev.deheerhoreca.nl" ]]; then
    echo "This script only runs on ${REQUIRE_HOST}.deheerhoreca.nl"
    exit 0
  fi
fi

# Wait for the above checks to complete before printing the start message
if ${THIS_IS_CRON}; then
  echo "----------- Start: ${ABBR_SCRIPT_PATH} @ ${ISO_DATE} (CRON) -----------"
fi
