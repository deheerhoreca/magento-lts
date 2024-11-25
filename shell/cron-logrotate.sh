#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cron-logrotate.sh

# Set User Environment
. ${HOME}/.bash_profile

set -e      # Exit immediately if a command exits with a non-zero status
set -u      # Treat unset variables as an error when substituting

cm

touch ~/logs/deheerhoreca-magento/access_log
touch ~/logs/deheerhoreca-magento/error_log
touch ~/logs/deheerhoreca-magento/cron_error.log
touch ~/logs/deheerhoreca-magento/cron_event.log

now=`date`
echo "--------------------------------------------------------------------"
echo "Current date: $now"
echo "--------------------------------------------------------------------"

cat > ~/tmp/logrotate-deheerhoreca-magento.conf << EOF
~/httpdocs/deheerhoreca-magento/var/log/*.log
~/httpdocs/deheerhoreca-magento/var/log/*.jsonl
~/httpdocs/deheerhoreca-magento/var/log/*.ndjson
~/logs/deheerhoreca-magento/access_log
~/logs/deheerhoreca-magento/error_log
~/logs/deheerhoreca-magento/*.log
~/logs/deheerhoreca-magento/*_log
{
  daily
  copytruncate
  dateext
  rotate 3
}
EOF

/usr/sbin/logrotate ~/tmp/logrotate-deheerhoreca-magento.conf -s ~/tmp/logrotate.deheerhoreca-magento.tmp -v

rm ~/tmp/logrotate-deheerhoreca-magento.conf
