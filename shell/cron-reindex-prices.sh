#!/bin/bash

: "
~/workspace/openmage/shell/cron-reindex-prices.sh
"

export REQUIRE_HOST=ma
export NO_DEV=0

. ${HOME}/.bash_profile
cm
. ./shell/cron-bootstrap.sh

# Product prices are not always updating for reporting (intel), even when Index on Save is enabled. Reindex prices regularly:
# n98 index:reindex catalog_product_price
php -c etc/php.cmd.ini shell/indexer.php --reindex catalog_product_price

. ./shell/cron-wrapup.sh
