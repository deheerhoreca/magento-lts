#!/bin/bash

: '
~/workspace/openmage/shell/cron-sooqr.sh
'

export PREFER_HOST=prod
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/sooqr.php --generate 1
dasel -f ./media/sooqr/sooqr-datafeed-5AjS-1.xml -r xml -w json > ./media/sooqr/sooqr-datafeed-5AjS-1.json
chmod 0666 ./media/sooqr/*

. ./shell/cron-wrapup.sh
