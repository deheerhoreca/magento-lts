#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/fix-permissions.sh

cm

cd ./media/catalog/product

find . -type f -perm o-r -print -exec chmod +r -- {} +
