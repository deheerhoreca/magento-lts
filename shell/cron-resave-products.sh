#!/bin/bash

set -e      # Exit immediately if a command exits with a non-zero status
# set -u      # Treat unset variables as an error when substituting -- crashes on THIS_IS_CRON

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  # echo "$(date -u) Not running ${0} on ${HOSTNAME} ever"
  exit 0
fi
