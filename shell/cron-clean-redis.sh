#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-redis.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# This runs on prod.deheerhoreca.nl only -- Wrong IP for any other production VPS!
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.bash_profile

cm

mphp -c php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0,1
