# Vodič za produkcijsko postavljanje

Ovaj vodič opisuje kako da postavite aplikaciju Solidarity Network na produkcioni VM bez Docker-a.

## Sistemski zahtevi

- Ubuntu 22.04 LTS ili noviji
- MySQL 8.0
- PHP 8.3.6 sa ekstenzijama:
  - mysql
  - mbstring
  - zip
  - intl
  - redis
  - igbinary
  - imagick
  - gd
  - bcmath
  - opcache
  - xml
  - curl
- Redis server
- Nginx
- ImageMagick
- Composer
- Git

## Koraci instalacije

### 1. Ažurirajte sistem
```bash
sudo apt-get update && sudo apt-get upgrade -y
```

### 2. Instalirajte potrebne pakete
```bash
# Instalirajte MySQL, Nginx i osnovne alate
sudo apt-get install -y mysql-server nginx git curl unzip imagemagick

# Instalirajte PHP i ekstenzije
sudo apt-get install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring \
    php8.3-curl php8.3-xml php8.3-bcmath php8.3-opcache \
    php8.3-intl php8.3-imagick php8.3-igbinary
```

### 3. Podesite PHP

Kreirajte/izmenite `/etc/php/8.3/fpm/conf.d/custom.ini`:
```ini
date.timezone = Europe/Belgrade
memory_limit = 2048M
```

Restartujte PHP-FPM:
```bash
sudo systemctl restart php8.3-fpm
```

### 4. Instalirajte i podesite Redis

```bash
# Instalirajte Redis
sudo apt-get install -y redis-server php8.3-redis

# Podesite Redis da se startuje pri podizanju sistema
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Obezbedite Redis (izmenite /etc/redis/redis.conf)
sudo sed -i 's/^# requirepass .*/requirepass vaša_jaka_redis_lozinka/' /etc/redis/redis.conf

# Restartujte Redis da bi se promene primenile
sudo systemctl restart redis-server
```

Podesite Symfony da koristi Redis za sesije. Dodajte u `.env.local`:
```dotenv
REDIS_URL=redis://default:vaša_jaka_redis_lozinka@localhost:6379
```

Izmenite `config/packages/framework.yaml`:
```yaml
framework:
    session:
        handler_id: '%env(REDIS_URL)%'
        cookie_secure: true
        cookie_samesite: lax
        storage_factory_id: session.storage.factory.native
```

Testirajte Redis konekciju:
```bash
redis-cli ping
# Trebalo bi da vrati PONG
```

### 5. Podesite MySQL

```bash
sudo mysql_secure_installation
```

Kreirajte bazu i korisnika:
```sql
CREATE DATABASE solidarity;
CREATE USER 'solidarity'@'localhost' IDENTIFIED BY 'vaša_jaka_lozinka';
GRANT ALL PRIVILEGES ON solidarity.* TO 'solidarity'@'localhost';
FLUSH PRIVILEGES;
```

### 6. Podesite Nginx

Kreirajte `/etc/nginx/sites-available/solidarity`:
```nginx
server {
    listen 80;
    server_name vaš_domen.com;
    root /var/www/solidarity/public;

    client_max_body_size 10M;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/solidarity_error.log;
    access_log /var/log/nginx/solidarity_access.log;
}
```

Omogućite sajt:
```bash
sudo ln -s /etc/nginx/sites-available/solidarity /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 7. Deploy aplikacije

Klonirajte repozitorijum:
```bash
cd /var/www
git clone https://github.com/IT-Srbija-Org/solidaritySF.git solidarity
cd solidarity
```

Instalirajte zavisnosti:
```bash
composer install --no-dev --optimize-autoloader
```

Podesite okruženje:
```bash
cp .env .env.local
# Izmenite .env.local i podesite:
# - APP_ENV=prod
# - APP_SECRET=vaš_secret
# - DATABASE_URL=mysql://solidarity:vaša_lozinka@127.0.0.1:3306/solidarity
# - MAILER_DSN=vaša_mail_konfiguracija
```

Podesite dozvole:
```bash
sudo chown -R www-data:www-data var
sudo chmod -R 775 var
```

### 8. Inicijalizujte bazu

```bash
php bin/console doctrine:schema:create
```

Učitajte početne podatke (ako je potrebno):
```bash
php bin/console doctrine:fixtures:load --group=1 --no-interaction
```

### 9. Završni koraci

Očistite keš:
```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Podesite SSL sa Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d vaš_domen.com
```

### 10. Održavanje

Redovan backup:
```bash
# Dodajte u crontab
0 3 * * * mysqldump -u solidarity -p'vaša_lozinka' solidarity > /backup/solidarity_$(date +\%Y\%m\%d).sql
```

Pratite logove:
```bash
tail -f /var/log/nginx/solidarity_error.log
```

## Bezbednosne preporuke

1. Uvek ažurirajte sistem
2. Koristite jake lozinke
3. Podesite firewall (UFW)
4. Redovno radite bezbednosne provere
5. Omogućite samo HTTPS
6. Podesite fail2ban
7. Redovan backup

## Optimizacija performansi

1. Omogućite OPcache
2. Podesite PHP-FPM pool
3. Koristite Redis za sesije
4. Podesite Nginx keširanje
5. Koristite CDN za statičke fajlove

## Monitoring

1. Podesite monitoring aplikacije
2. Podesite logovanje grešaka
3. Pratite resurse sistema
4. Podesite alarme za kritične događaje
