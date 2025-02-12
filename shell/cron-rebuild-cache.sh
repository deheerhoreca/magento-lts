#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-rebuild-cache.sh

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting
# set -x      # Print commands and their arguments as they are executed

cm

mphp -c php.cmd.ini shell/rebuild_cache.php
