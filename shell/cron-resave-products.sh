#!/usr/bin/env bash

: "---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-resave-products.sh                                            # 120+ min
-----------------------------------------------------------------------------------------------------------------"

export PREFER_HOST=ma
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# openmage shell/resave_all_products.php

. ./shell/cron-wrapup.sh
