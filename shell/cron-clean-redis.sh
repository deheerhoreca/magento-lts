#!/bin/bash

: '
~/workspace/openmage/shell/cron-clean-redis.sh
'

export REQUIRE_HOST=prod
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

# php -c etc/php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0
php -c etc/php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 1

. ./shell/cron-wrapup.sh
