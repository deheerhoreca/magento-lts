#!/bin/bash

: '
fix-permissions.sh
'

# ._."._."._."._."._."._."._."._."._."._."._."._."._."._. #

export NO_DEV=0

. ${HOME}/.profile || die "Failed to load ~/.profile"
cm || die "Failed to go to the openmage directory"
. ./shell/cron-bootstrap.sh || die "Failed to run ./shell/cron-bootstrap.sh"

# =-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-= #
#                           @OPA                          #
# =-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-= #

# cm && cd ./media || exit 1
# find . -type d ! -perm 0777 -print -exec chmod 0777 -- {} +
# find . -type f ! -perm 0666 -print -exec chmod 0666 -- {} +

cm && cd ./media || exit 1
find . -type d ! -perm 0777 -print -exec chmod 0777 -- {} +
find . -type f ! -perm 0666 -print -exec chmod 0666 -- {} +

# cm && cd ./media || exit 1
# find . -type d ! -perm 0777 -print -exec chmod 0777 -- {} +
# find . -type f ! -perm 0666 -print -exec chmod 0666 -- {} +

# ._."._."._."._."._."._."._."._."._."._."._."._."._."._. #

cm && source ./shell/cron-wrapup.sh
