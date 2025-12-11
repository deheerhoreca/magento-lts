#!/bin/bash

# ~/workspace/openmage/shell/cron-clean-redis.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

export REQUIRE_HOST=prod
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0,1

. ./shell/cron-wrapup.sh
