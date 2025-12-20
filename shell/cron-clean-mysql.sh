#!/bin/bash

: '
~/workspace/openmage/shell/cron-clean-mysql.sh
'

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

openmage shell/clean_mysql.php

. ./shell/cron-wrapup.sh
