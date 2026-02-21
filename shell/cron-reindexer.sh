#!/bin/bash

: '
~/workspace/openmage/shell/cron-reindexer.sh
'

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

openmage shell/indexer.php reindexallrequired

. ./shell/cron-wrapup.sh
