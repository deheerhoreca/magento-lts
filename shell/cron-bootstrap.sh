#!/bin/bash -l

# ---------------------------------------------------- SETUP ENV ---------------------------------------------------- #

# Include .profile if running non-interactively
if [[ $- != *i* ]]; then
	# shellcheck disable=SC1091
	. ${HOME}/.profile
fi

## DEV:
. /etc/profile.d/phpenv.sh

CRON_BOOTSTRAP_DIR="$(cd -P "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
# shellcheck disable=SC1091
. "${CRON_BOOTSTRAP_DIR}/cron-functions.sh"
unset CRON_BOOTSTRAP_DIR

# Set some bash flags that also apply to the rest of the parent script
set -e          # Exit immediately if a command exits with a non-zero status
set -u          # Treat unset variables as an error when substituting

cm

export VERBOSE_LOGGING=${VERBOSE_LOGGING:-false}
export THIS_IS_CRON=${THIS_IS_CRON:-false}
export PREFER_HOST=${PREFER_HOST:-}
export NO_DEV=${NO_DEV:-1}
export REQUIRE_HOST=${REQUIRE_HOST:-}
export HEAD_START_HOST=${HEAD_START_HOST:-}
export HEAD_START_SECS=${HEAD_START_SECS:-}

SCRIPT_PATH=${0}
THE_CWD="$(pwd)/"
ABBR_SCRIPT_PATH=${SCRIPT_PATH#*"${THE_CWD}"}
CURRENT_CRON_CMD="${ABBR_SCRIPT_PATH} ${*}"
ARGS=


# ----------------------------------------------- RUN OR NOT DECISION ----------------------------------------------- #


# Check PREFERRED host against current host and exit if not on the preferred host AND running in cron.
if [ -n "${PREFER_HOST}" ]; then
  if [ "${HOSTNAME}" != "${PREFER_HOST}.deheerhoreca.nl" ] && is_truthy "${THIS_IS_CRON}"; then
    if is_truthy "${VERBOSE_LOGGING}"; then
      log_line "${CURRENT_CRON_CMD} prefers to run on ${PREFER_HOST}.deheerhoreca.nl" "INFO" "NOOP"
    fi
    exit 0
  fi
fi

# Check REQUIRED host against current host regardless of cron.
if [ -n "${REQUIRE_HOST}" ]; then
  if [ "${HOSTNAME}" != "${REQUIRE_HOST}.deheerhoreca.nl" ]; then
    if is_truthy "${VERBOSE_LOGGING}"; then
      log_line "${CURRENT_CRON_CMD} only runs on ${REQUIRE_HOST}.deheerhoreca.nl" "INFO" "NOOP"
    fi
    exit 0
  fi
fi

# Check for DEVELOPMENT host during CRON: If NO_DEV=1 is set in the job's shell script, do not continue.
if [[ "${HOSTNAME}" == "dev.deheerhoreca.nl" ]] && is_truthy "${NO_DEV}" && is_truthy "${THIS_IS_CRON}"; then
  if is_truthy "${VERBOSE_LOGGING}"; then
    log_line "${CURRENT_CRON_CMD} is skipped in development environments" "INFO" "NOOP"
  fi
  exit 0
fi

# Most cronjobs are configured with PREFER_HOST to avoid contention but mostly to avoid running heavy
# jobs on hosts that run the public website. So we use a mechanism where non-preferred hosts sleep a
# tiny random amount of time to allow the preferred host to acquire the lock first, but if the
# preferred host is not available, the non-preferred hosts can still run the job without waiting for
# a long time. Of course this works best if the job will not trigger again right after it was
# completed, such as a lastrun mechanism or a minimum file size. This only applies to cronjobs. Note
# that this mechanism does not prevent running on DEV, use NO_DEV for that.
#
# To enable this mechanism, add something like:
# export HEAD_START_HOST="ma"
# export HEAD_START_SECS=10

if [ -n "${HEAD_START_HOST}" ] && [ -n "${HEAD_START_SECS}" ] && [ "${HOSTNAME}" != "${HEAD_START_HOST}.deheerhoreca.nl" ] && is_truthy "${THIS_IS_CRON}"; then
  SLEEP_SECS=$((RANDOM % HEAD_START_SECS + 2))
  if is_truthy "${VERBOSE_LOGGING}"; then
    log_line "Sleeping for ${SLEEP_SECS} seconds to give ${HEAD_START_HOST}.deheerhoreca.nl a head start" "INFO" "RUN"
  fi
  sleep "${SLEEP_SECS}"
fi


# ------------------------------------------------- DECIDED TO RUN -------------------------------------------------- #


if is_truthy "${VERBOSE_LOGGING}"; then
  log_line "${CURRENT_CRON_CMD}" "INFO" "START"
fi

# Build an $ARGS string based on env vars, which should be added to any PHP intel command invoked in subsequent scripts.

# @todo  Instead of env vars, implement the same flags that we use for the PHP intel commands in here, and build $ARGS
# @todo   based on those flags. Include a -h flag to explain the options. This task includes checking all cron-*.sh
# @todo   scripts for usage of $ARGS and replacing it with the actual flags, e.g. `intel ${ARGS} --action=a:b:c` instead of
# @todo   `intel --action=a:b:c ${ARGS}` to avoid confusion about whether flags in $ARGS can override explicitly set
# @todo   flags or not.

# @todo  We will also need a way to avoid duplicating flags if the intel command is invoked
# @todo   with a flag that is already set in $ARGS, e.g. `intel --action=a:b:c --n=10 ${ARGS}` where $ARGS already
# @todo   contains `--n=5` - maybe the best way is to only allow flags in the parent script and not in the PHP intel
# @todo   command itself, but that would limit flexibility in jobs with multiple consecutive intel commands. Perhaps
# @todo   PHP can be made tolerant to duplicated flags by using the last one, so `intel --n=5 --n=10` would use
# @todo   `--n=10` and we can allow that in $ARGS as well as the PHP command.

# Inherit optional environment vars to be added to intel commands later via ${ARGS}.
# All short options should be supported here except for -h and -s (-dpfv)
export ARGS=""
export DEBUG=${DEBUG:-false}
export DRYRUN=${DRYRUN:-false}
export FORCE=${FORCE:-false}
export N=${N:-}
export PROFILE=${PROFILE:-false}
export VERBOSE=${VERBOSE:-false}

if is_truthy "${VERBOSE}"; then
  ARGS+=" -v"
  log_line "Verbose output enabled"
fi

if is_truthy "${DRYRUN}"; then
  ARGS+=" -d"
  log_line "Dryrun enabled"
fi

if is_truthy "${PROFILE}"; then
  ARGS+=" -p"
  log_line "Profiling output enabled"
fi

if is_truthy "${DEBUG}"; then
  ARGS+=" --debug"
  log_line "Debug output enabled"
fi

N_NORMALIZED=""
if N_NORMALIZED="$(normalize_numeric_int "${N}")"; then
  export N="${N_NORMALIZED}"
  ARGS+=" --n=${N_NORMALIZED}"
fi

if is_truthy "${FORCE}"; then
  ARGS+=" -f"
  log_line "Force enabled"
fi
