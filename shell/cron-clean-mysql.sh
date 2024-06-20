#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-mysql.sh

# set -e      # Exit immediately if a command exits with a non-zero status
# set -u      # Treat unset variables as an error when substituting

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  # echo "$(date -u) Not running ${0} on ${HOSTNAME} ever"
  exit 0
fi

# Set User Environment
. ${HOME}/.profile

# set -x      # Print commands and their arguments as they are executed

cm

mphp -c php.cmd.ini shell/clean_mysql.php

# set +x
