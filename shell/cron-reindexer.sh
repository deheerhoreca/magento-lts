#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindexer.sh

export PREFER_HOST=ma

# Set User Environment
. ${HOME}/.bash_profile

cm && source ./shell/cron-bootstrap.sh

php -c etc/php.cmd.ini shell/indexer.php reindexallrequired

cm && source ./shell/cron-wrapup.sh
