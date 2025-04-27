
# MySQL Backup
0 1 * * * mysqldump -u root solidarity | gzip > /tmp/solidarity_`date +\%d-\%m-\%Y_\%H`.sql.gz
0 13 * * * mysqldump -u root solidarity | gzip > /tmp/solidarity_`date +\%d-\%m-\%Y_\%H`.sql.gz

# Cleaner
0 2 * * * find /tmp/solidarity_* -maxdepth 0 -type f -mtime +30 -exec rm {} \;
