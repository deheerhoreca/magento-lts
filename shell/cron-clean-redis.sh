#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-clean-redis.sh

# set -e      # Exit immediately if a command exits with a non-zero status
# set -u      # Treat unset variables as an error when substituting

# Set User Environment
. ${HOME}/.profile

# set -x      # Print commands and their arguments as they are executed

cm

mphp -c php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0,1

# set +x
