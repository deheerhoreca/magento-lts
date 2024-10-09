#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-redis.sh

# This runs on prod.deheerhoreca.nl only
if [ "${HOSTNAME}" != "prod.deheerhoreca.nl" ]; then
  exit 0
fi

# Set User Environment
. ${HOME}/.profile

cm

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# cm_redis_tools IS OUTDATED

# Clean old FPC stuff:
#mphp -c php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0 -v

# Clean old session stuff: @todo what does this do?
# mphp -c php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 1 -v
