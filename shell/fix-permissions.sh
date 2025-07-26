#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/fix-permissions.sh

# Set User Environment
. ${HOME}/.bash_profile

cm && source ./shell/cron-bootstrap.sh

cm && cd media/catalog/product

find . -type d ! -perm 0775 -print -exec chmod 0775 -- {} +
find . -type f ! -perm 0644 -print -exec chmod 0644 -- {} +

cm && cd media/catalog/category

find . -type d ! -perm 0775 -print -exec chmod 0775 -- {} +
find . -type f ! -perm 0644 -print -exec chmod 0644 -- {} +

cm && source ./shell/cron-wrapup.sh
