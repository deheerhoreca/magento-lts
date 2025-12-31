#!/bin/bash

: '
~/workspace/openmage/shell/cron-reindex-prices.sh
'

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

openmage shell/indexer.php --reindex catalogsearch_fulltext

. ./shell/cron-wrapup.sh
