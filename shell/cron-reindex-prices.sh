#!/usr/bin/env bash

: "───────────────────────────────────────────────────────────────────────────────────────────────────────────────
${HOME}/workspace/openmage/shell/cron-reindex-prices.sh                                       # 33 sec
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────"

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# Product prices are not always updating for reporting (intel), even when Index on Save is enabled. Reindex prices regularly:
# n98 index:reindex catalog_product_price
openmage shell/indexer.php --reindex catalog_product_price

. ./shell/cron-wrapup.sh
