#!/bin/bash

# ~/workspace/openmage/shell/cron-clean-mysql.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm

. ./shell/cron-bootstrap.sh

php -c php.cmd.ini shell/clean_mysql.php

. ./shell/cron-wrapup.sh
