#!/bin/bash
mkdir -p /data/cron.backup
cp /var/spool/cron/www /data/cron.backup/www.`date +"%Y%m%d%H%M%S"`

cat > /var/spool/cron/www<<EOF
# DO NOT EDIT THIS FILE - edit the master and reinstall.
# (/tmp/crontab.kuHm9o/crontab installed on Wed Mar  2 10:31:40 2016)
# (Cron version -- $Id: crontab.c,v 2.13 1994/01/17 03:20:37 vixie Exp $)
# Edit this file to introduce tasks to be run by cron.
# 
# Each task to run has to be defined through a single line
# indicating with different fields when the task will be run
# and what command to run for the task
# 
# To define the time you can provide concrete values for
# minute (m), hour (h), day of month (dom), month (mon),
# and day of week (dow) or use '*' in these fields (for 'any').# 
# Notice that tasks will be started based on the cron's system
# daemon's notion of time and timezones.
# 
# Output of the crontab jobs (including errors) is sent through
# email to the user the crontab file belongs to (unless redirected).
# 
# For example, you can run a backup of all your user accounts
# at 5 a.m every week with:
# 0 5 * * 1 tar -zcf /var/backups/home.tgz /home/
# 
# For more information see the manual pages of crontab(5) and cron(8)
# 
# m h  dom mon dow   command

*/2 * * * * /alidata/server/php5.4/bin/php  /data/adn/up/maintenance/createDeliveryCache.php

* * * * * /alidata/server/php/bin/php /data/adn/bos-backend-api/artisan schedule:run 1>> /dev/null 2>&1

EOF

crontab -uwww /var/spool/cron/www
