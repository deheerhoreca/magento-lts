#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-resave-products.sh

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cm

# mphp -c php.cmd.ini shell/resave_all_products.php
