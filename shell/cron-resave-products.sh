#!/bin/bash

: '
~/workspace/openmage/shell/cron-resave-products.sh
'

export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

# php -c etc/php.ini shell/resave_all_products.php
