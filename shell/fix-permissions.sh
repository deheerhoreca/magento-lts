#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/fix-permissions.sh

# ._."._."._."._."._."._."._."._."._."._."._."._."._."._. #

# shellcheck source=/dev/null
. "${HOME}/.bash_profile"

cm && source ./shell/cron-bootstrap.sh

#                          LOCAL
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

#                           NFS
# =-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-=x=-= #

#cm && cd ./media.nfs/catalog/product || exit 1
#find . -type d ! -perm 0777 -print -exec chmod 0777 -- {} +
#find . -type f ! -perm 0666 -print -exec chmod 0666 -- {} +

#cm && cd ./media.nfs/catalog/category || exit 1
#find . -type d ! -perm 0777 -print -exec chmod 0777 -- {} +
#find . -type f ! -perm 0666 -print -exec chmod 0666 -- {} +

# ._."._."._."._."._."._."._."._."._."._."._."._."._."._. #

cm && source ./shell/cron-wrapup.sh
