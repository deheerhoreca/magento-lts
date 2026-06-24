#!/usr/bin/env bash

: "───────────────────────────────────────────────────────────────────────────────────────────────────────────────
${HOME}/workspace/openmage/shell/cron-wrapup.sh
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────"

SCRIPT_PATH=$0
THE_CWD="$(pwd)/"
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*"${THE_CWD}"}
CURRENT_CRON_CMD="${ABBR_SCRIPT_PATH} ${*}"
THIS_IS_CRON=${THIS_IS_CRON:-false}
VERBOSE_LOGGING=${VERBOSE_LOGGING:-false}

# Wait for the above checks to complete before printing the start message -- Max 110 chars
if ${THIS_IS_CRON}; then
  log_line "STOP   ${CURRENT_CRON_CMD}" "INFO"
else
  if is_truthy "${VERBOSE_LOGGING}"; then
    # Uncomment during development:
    log_line "STOP   ${CURRENT_CRON_CMD}" "INFO"
  fi
fi
