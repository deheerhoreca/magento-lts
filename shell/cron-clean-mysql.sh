#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-mysql.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# This runs on ma.deheerhoreca.nl only
if [ "${HOSTNAME}" != "ma.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

cm

php -c php.cmd.ini shell/clean_mysql.php
