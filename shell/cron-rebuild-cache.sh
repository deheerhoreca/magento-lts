#!/usr/bin/env bash

: "---------------------------------------------------------------------------------------------------------------
${HOME}/workspace/openmage/shell/cron-rebuild-cache.sh
-----------------------------------------------------------------------------------------------------------------"

export REQUIRE_HOST=prod
export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

openmage shell/rebuild_cache.php

. ./shell/cron-wrapup.sh
