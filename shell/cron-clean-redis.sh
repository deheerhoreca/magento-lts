#!/bin/bash

: '---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-clean-redis.sh
-----------------------------------------------------------------------------------------------------------------'

export REQUIRE_HOST=prod
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# Remove expired keys from Redis:
openmage shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0

# Remove expired session cache keys from Redis:
# openmage shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 1

# Remove missing cache keys from Redis sets:
openmage shell/cm_redis_tools/rediscache.php

. ./shell/cron-wrapup.sh
