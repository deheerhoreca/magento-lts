#!/bin/bash

: '---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-sooqr.sh                                                # ~5 min
-----------------------------------------------------------------------------------------------------------------'

export PREFER_HOST=prod
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

openmage shell/sooqr.php --generate 1
dasel -i xml -o json < ./media/sooqr/sooqr-datafeed-5AjS-1.xml > ./media/sooqr/sooqr-datafeed-5AjS-1.json
chmod 0666 ./media/sooqr/*

. ./shell/cron-wrapup.sh
