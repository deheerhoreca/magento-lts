#!/usr/bin/env bash

: "───────────────────────────────────────────────────────────────────────────────────────────────────────────────
${HOME}/workspace/openmage/shell/cmyk_to_rgb.sh
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────"

export NO_DEV=0

OPENMAGE_DIR="${HOME}/workspace/openmage"

cd "${OPENMAGE_DIR}" || log_line "Failed to go to the openmage directory" "FATAL"
source ./shell/cron-bootstrap.sh || log_line "Failed to run ./shell/cron-bootstrap.sh" "FATAL"

set +e # ~/.profile sets `set -e`, but we want to continue on errors in this script

CATALOG_DIR="${OPENMAGE_DIR}/media/catalog/product"

DRYRUN=true

if [ "${DRYRUN}" == true ]; then
  echo "Dryrun: Printing commands only."
fi

echo "Current catalog dir: ${CATALOG_DIR}"
sleep 1

JOBS=4

export DRYRUN

process_file() {
  local f="$1"
  local TYPE
  if ! TYPE=$(identify -format '%[colorspace]' "${f}" 2>&1); then
    printf "\nERROR: %s — %s\n" "${f}" "${TYPE}" >&2
    return
  fi
  if [ "$TYPE" == "CMYK" ]; then
    if [ "${DRYRUN}" == false ]; then
      magick "${f}" -colorspace sRGB "${f}"
      printf "\n'${f}' converted from CMYK to sRGB\n"
    else
      printf "\nmagick '${f}' -colorspace sRGB '${f}'\n"
    fi
  else
    printf "."
  fi
}
export -f process_file

declare -i COUNTER=0

find "${CATALOG_DIR}" -type f -path "*/cache/*" -prune -o -iname "*.jpg" -print0 \
  | while IFS= read -r -d '' f; do
    ((COUNTER++))
    if [ $((COUNTER % 500)) -eq 0 ]; then
      printf "%d" "${COUNTER}" >&2
    fi
    printf '%s\0' "${f}"
  done \
  | xargs -0 -n 1 -P "${JOBS}" bash -c 'process_file "$@"' _
