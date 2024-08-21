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
~/httpdocs/deheerhoreca-magento/var/log/*.txt
~/logs/deheerhoreca-magento/access_log
~/logs/deheerhoreca-magento/error_log
~/logs/deheerhoreca-magento/*.log
~/logs/deheerhoreca-magento/*_log
{
  daily
  copytruncate
  dateext
  rotate 3
  missingok
  notifempty
}
EOF

/usr/sbin/logrotate /tmp/logrotate-deheerhoreca-magento.conf -s /tmp/logrotate.deheerhoreca-intel.tmp -v

rm /tmp/logrotate-deheerhoreca-magento.conf
