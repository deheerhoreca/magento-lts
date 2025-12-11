#!/bin/bash

ISO_DATE=$(TZ="Europe/Amsterdam" date +"%F %T")
SCRIPT_PATH=$0
THE_CWD="$(pwd)/"
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*"${THE_CWD}"}
CURRENT_CRON_CMD="${ABBR_SCRIPT_PATH} ${*}"

# Wait for the above checks to complete before printing the start message -- Max 110 chars
if ${THIS_IS_CRON}; then
  printf "%s  STOP   %s\n" "${ISO_DATE}" "${CURRENT_CRON_CMD}"
fi
