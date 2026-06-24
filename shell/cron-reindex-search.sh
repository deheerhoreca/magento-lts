#!/usr/bin/env bash

: "───────────────────────────────────────────────────────────────────────────────────────────────────────────────
${HOME}/workspace/openmage/shell/cron-reindex-search.sh                                       # 18 sec
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────"

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

openmage shell/indexer.php --reindex catalogsearch_fulltext

. ./shell/cron-wrapup.sh
