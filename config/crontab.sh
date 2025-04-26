
# Cancelled transactions
0 * * * * php /var/www/solidaritySF/bin/console app:cancelled-transaction >> ./var/var/crontab-cancelled-transaction-`date +\%d-\%m-\%Y`.txt

# Create damaged educator period
0 1 23 * * php /var/www/solidaritySF/bin/console app:create-damaged-educator-period `date -d' -1 month' +\%n` `date -d' -1 month' +\%Y` full >> ./var/var/crontab-cancelled-transaction-`date +\%d-\%m-\%Y`.txt
