#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-reindexer.sh

PREFER_HOST=ma

# Set User Environment
. ${HOME}/.bash_profile

cm && source ./shell/cron-bootstrap.sh

mphp -c php.cmd.ini shell/indexer.php reindexallrequired

cm && source ./shell/cron-wrapup.sh
