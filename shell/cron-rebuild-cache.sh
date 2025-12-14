#!/bin/bash

: "
~/workspace/openmage/shell/cron-rebuild-cache.sh
"

export REQUIRE_HOST=prod
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/rebuild_cache.php

. ./shell/cron-wrapup.sh
