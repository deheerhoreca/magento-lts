#!/bin/bash

log_line() {
  local message="${1:?log_line requires a message as the first argument}"
  local severity="${2:-INFO}"
  local runflag="${3:-RUN}"
  local iso_date

  severity="${severity^^}"
  runflag="${runflag^^}"

  if [[ "${severity}" == "WARNING" ]]; then
    severity="WARN"
  fi

  iso_date="$(TZ=UTC date +"%Y-%m-%dT%H:%M:%SZ")"

  case "${severity}" in
    INFO|WARN|ERROR|CRITICAL|ALERT|EMERGENCY|FATAL)
      printf "%s  %s  %s   %s\n" "${iso_date}" "${severity}" "${runflag}" "${message}" >&2
      ;;
    *)
      printf "%s  %s  %s   %s\n" "${iso_date}" "${severity}" "${runflag}" "${message}"
      ;;
  esac
}

has_argument() {
  [[ ("$1" == *=* && -n ${1#*=}) || (! -z "$2" && "$2" != -*) ]]
}

extract_argument() {
  echo "${2:-${1#*=}}"
}

get_script_dir() {
  local source_path="${BASH_SOURCE[0]}"
  local symlink_dir
  local script_dir

  while [ -L "${source_path}" ]; do
    symlink_dir="$(cd -P "$(dirname "${source_path}")" >/dev/null 2>&1 && pwd)"
    source_path="$(readlink "${source_path}")"
    if [[ "${source_path}" != /* ]]; then
      source_path="${symlink_dir}/${source_path}"
    fi
  done

  script_dir="$(cd -P "$(dirname "${source_path}")" >/dev/null 2>&1 && pwd)"
  echo "${script_dir}"
}

is_truthy() {
  local value="${1,,}"
  [[ "${value}" == "1" || "${value}" == "true" ]]
}

normalize_numeric_int() {
  local value="${1}"

  value="$(awk -v n="${value}" 'BEGIN {
    if (n ~ /^[[:space:]]*[+-]?(([0-9]+(\.[0-9]*)?)|(\.[0-9]+))([eE][+-]?[0-9]+)?[[:space:]]*$/) {
      n += 0
      if (n < 0) {
        printf "%d", -int(-n)
      } else {
        printf "%d", int(n)
      }
      exit 0
    }
    exit 1
  }')" || return 1

  echo "${value}"
}
