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

cm

cat >/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/httpdocs/deheerhoreca-magento/var/log/*.log
~/httpdocs/deheerhoreca-magento/var/log/*.jsonl
~/httpdocs/deheerhoreca-magento/var/log/*.ndjson
~/logs/deheerhoreca-magento/access_log
~/logs/deheerhoreca-magento/error_log
~/logs/deheerhoreca-magento/*.log
~/logs/deheerhoreca-magento/*_log
{
  daily
  nocopytruncate
  dateext
  rotate 2
  missingok
}
EOF

/usr/sbin/logrotate /tmp/logrotate-deheerhoreca-magento.conf -s /tmp/logrotate.deheerhoreca-intel.tmp -v

rm /tmp/logrotate-deheerhoreca-magento.conf
