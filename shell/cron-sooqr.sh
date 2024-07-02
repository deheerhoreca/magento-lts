#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/sooqr.sh

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  # echo "$(date -u) Not running ${0} on ${HOSTNAME} ever"
  exit 0
fi

# Set User Environment
. ${HOME}/.profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cd ~/httpdocs/deheerhoreca-magento

mphp -c php.cmd.ini shell/sooqr.php --generate 1
