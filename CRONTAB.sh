# MAILTO="it+cron@deheerhoreca.nl"

# ┌───────────── minute (0 - 59)
# │   ┌───────────── hour (0 - 23)
# │   │  ┌───────────── day of the month (1 - 31)
# │   │  │  ┌───────────── month (1 - 12)
# │   │  │  │  ┌───────────── day of the week (0 - 6) (Sunday to Saturday; 7 is also Sunday on some systems)
# │   │  │  │  │
# m   h dom m dow command to execute

# THIS FILE SHOULD BE WINDOWS-LINE ENDED FOR PROPER COPY FUNCTION IN NOTEPAD++

# DEHEERHORECA-MAGENTO
 *    *  *  *  *  ! test -e ~/httpdocs/deheerhoreca-magento/maintenance.flag && cd ~/httpdocs/deheerhoreca-magento && /bin/bash ./scheduler_cron.sh --mode always  1>>./var/log/cron.log 2>>./var/log/cron.err
 *    *  *  *  *  ! test -e ~/httpdocs/deheerhoreca-magento/maintenance.flag && cd ~/httpdocs/deheerhoreca-magento && /bin/bash ./scheduler_cron.sh --mode default 1>>./var/log/cron.log 2>>./var/log/cron.err
05    0  *  *  *  cd ~/httpdocs/deheerhoreca-magento; /usr/sbin/logrotate logrotate.conf -s /tmp/logrotate                          1>>./var/log/cron.log 2>>./var/log/cron.err
29   05  *  *  *  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/rebuild_cache.php             1>>./var/log/cron.log 2>>./var/log/cron.err
30   05  *  *  *  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/indexer.php reindexall        1>>./var/log/cron.log 2>>./var/log/cron.err
30   02  *  *  *  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/cm_redis_tools/rediscli.php -s 127.0.0.1 -p 6379 -d 0,1 1>>./var/log/cron.log 2>>./var/log/cron.err
#00   22  *  *  0  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/resave_all_products.php       1>>./var/log/cron.log 2>>./var/log/cron.err
55   21  *  *  0  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/clean_mysql.php               1>>./var/log/cron.log 2>>./var/log/cron.err
00   01  *  *  *  cd ~/httpdocs/deheerhoreca-magento; ./shell/download_external_libs.sh                                             1>>./var/log/cron.log 2>>./var/log/cron.err
00   05  *  *  *  cd ~/httpdocs/deheerhoreca-magento; /opt/plesk/php/7.4/bin/php -c php.cmd.ini shell/sooqr.php --generate 1        1>>./var/log/cron.log 2>>./var/log/cron.err

# ASYNC-CACHE-WARMER
00    0  *  *  *  ~/async-cache-warmer/cron-logrotate.sh        1>>~/async-cache-warmer/log/logrotate.log 2>>~/async-cache-warmer/log/logrotate.log
00   06  *  *  *  ~/async-cache-warmer/cron-all.sh              1>>~/async-cache-warmer/log/cron.log 2>>~/async-cache-warmer/log/cron.err
00  */4  *  *  *  ~/async-cache-warmer/cron-categories.sh       1>>~/async-cache-warmer/log/cron.log 2>>~/async-cache-warmer/log/cron.err
#00   07  *  *  0  ~/async-cache-warmer/cron-cartesian.sh        1>>~/async-cache-warmer/log/cron.log 2>>~/async-cache-warmer/log/cron.err
