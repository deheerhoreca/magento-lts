#!/bin/bash

: '
~/workspace/openmage/shell/cron-reindexer.sh
'

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/indexer.php reindexallrequired

. ./shell/cron-wrapup.sh
