#!/bin/bash

: "
~/workspace/openmage/shell/cron-reindex-dynamiccategory.sh
"

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/indexer.php --reindex dynamiccategory

. ./shell/cron-wrapup.sh
