#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-logrotate.sh

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

now=`date`
echo "--------------------------------------------------------------------"
echo "Current date: $now"
echo "--------------------------------------------------------------------"

# Set User Environment
. ${HOME}/.profile

set -x      # Print commands and their arguments as they are executed

cm

cat >/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/httpdocs/deheerhoreca-magento/var/log/*.log
~/httpdocs/deheerhoreca-magento/var/log/*.jsonl
~/httpdocs/deheerhoreca-magento/var/log/*.ndjson
{
  daily
  nocopytruncate
  dateext
  rotate 7
  missingok
  notifempty
}
EOF

/usr/sbin/logrotate /tmp/logrotate-deheerhoreca-magento.conf -s /tmp/logrotate.deheerhoreca-intel.tmp -v

set +x
