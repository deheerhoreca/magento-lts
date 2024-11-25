#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/fix-permissions.sh

# Set User Environment
. ${HOME}/.bash_profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cm && cd media/catalog/product

find . -type f ! -perm 0644 -print -exec chmod 0644 -- {} +

cm && cd media/catalog/category

find . -type f ! -perm 0644 -print -exec chmod 0644 -- {} +
