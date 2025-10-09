#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-sooqr.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

cm

mphp -c php.cmd.ini shell/sooqr.php --generate 1

dasel -f ./media/sooqr/sooqr-datafeed-5AjS-1.xml -r xml -w json > ./media/sooqr/sooqr-datafeed-5AjS-1.json

chmod 0666 ./media/sooqr/*
