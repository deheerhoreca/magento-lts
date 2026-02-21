#!/bin/bash

: '
~/workspace/openmage/shell/cron-reindex-prices.sh
'

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

openmage shell/om-indexer --reindex catalogsearch_fulltext

. ./shell/cron-wrapup.sh
