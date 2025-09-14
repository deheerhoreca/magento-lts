#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindex-prices.sh

# Set User Environment
. ${HOME}/.bash_profile

cm && source ./shell/cron-bootstrap.sh

# This runs on ma.deheerhoreca.nl only
if [ "${HOSTNAME}" != "ma.deheerhoreca.nl" ] && [ "${HOSTNAME}" != "dev.deheerhoreca.nl" ]; then
  exit 0
fi

# Product prices are not always updating for reporting (intel), even when Index on Save is enabled. Reindex prices regularly:
cm
n98 index:reindex catalog_product_price

source ./shell/cron-wrapup.sh
