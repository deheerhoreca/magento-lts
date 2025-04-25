#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindexer.sh

# This runs on ma.deheerhoreca.nl only
if [ "${HOSTNAME}" != "ma.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cm

mphp -c php.cmd.ini shell/indexer.php reindexallrequired
