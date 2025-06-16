#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindex-dynamiccategory.sh

# Set User Environment
. ${HOME}/.bash_profile

cm && source ./shell/cron-bootstrap.sh

# This runs on ma.deheerhoreca.nl only
if [ "${HOSTNAME}" != "ma.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

mphp -c php.cmd.ini shell/indexer.php --reindex dynamiccategory

cm && source ./shell/cron-wrapup.sh
