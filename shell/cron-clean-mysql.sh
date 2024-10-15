#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-mysql.sh

if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cm

mphp -c php.cmd.ini shell/clean_mysql.php
