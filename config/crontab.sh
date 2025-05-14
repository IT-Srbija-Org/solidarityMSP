
# Transactions
#*/10 7-21 * * 1-4 php /var/www/solidaritySF/bin/console app:transaction:create 10000 > /var/www/solidaritySF/var/log/crontab-transaction-create-`date +\%d-\%m-\%Y`.txt
#0 7-21 * * 1-4 php /var/www/solidaritySF/bin/console app:transaction:create-for-large-amount > /var/www/solidaritySF/var/log/crontab-transaction-create-for-large-amount-`date +\%d-\%m-\%Y`.txt
*/10 7-21 * * 1-4 php /var/www/solidaritySF/bin/console app:transaction:create 60000 --schoolTypeId=9 > /var/www/solidaritySF/var/log/crontab-transaction-create-`date +\%d-\%m-\%Y`.txt
0 7-21 * * 1-4 php /var/www/solidaritySF/bin/console app:transaction:create-for-large-amount --schoolTypeId=9 > /var/www/solidaritySF/var/log/crontab-transaction-create-for-large-amount-`date +\%d-\%m-\%Y`.txt
0 * * * * php /var/www/solidaritySF/bin/console app:transaction:expired >> /var/www/solidaritySF/var/log/crontab-transaction-expired-`date +\%d-\%m-\%Y`.txt
0 7 * * * php /var/www/solidaritySF/bin/console app:transaction:notify-delegates >> /var/www/solidaritySF/var/log/crontab-transaction-notify-delegates-`date +\%d-\%m-\%Y`.txt
0 7 * * * php /var/www/solidaritySF/bin/console app:transaction:notify-donors >> /var/www/solidaritySF/var/log/crontab-transaction-notify-donors-`date +\%d-\%m-\%Y`.txt

# Cache
*/10 * * * * php /var/www/solidaritySF/bin/console app:cache-numbers >> /var/www/solidaritySF/var/log/crontab-cache-numbers-`date +\%d-\%m-\%Y`.txt

# Cleaner
0 2 * * * find /var/www/solidaritySF/var/log/crontab* -maxdepth 0 -type f -mtime +30 -exec rm {} \;

# Create damaged educator period
10 0 * * * php /var/www/solidaritySF/bin/console app:log-numbers >> /var/www/solidaritySF/var/log/crontab-log-number-`date +\%d-\%m-\%Y`.txt
