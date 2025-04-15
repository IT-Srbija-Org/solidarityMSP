# Production Deployment Guide

This guide describes how to deploy the Solidarity Network application on a production VM without Docker.

## System Requirements

- Ubuntu 22.04 LTS or newer
- MySQL 8.0
- PHP 8.3.6 with extensions:
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

## Installation Steps

### 1. Update System
```bash
sudo apt-get update && sudo apt-get upgrade -y
```

### 2. Install Required Packages
```bash
# Install MySQL, Nginx, and basic tools
sudo apt-get install -y mysql-server nginx git curl unzip imagemagick

# Install PHP and extensions
sudo apt-get install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-zip php8.3-gd php8.3-mbstring \
    php8.3-curl php8.3-xml php8.3-bcmath php8.3-opcache \
    php8.3-intl php8.3-imagick php8.3-igbinary
```

### 3. Configure PHP

Create/edit `/etc/php/8.3/fpm/conf.d/custom.ini`:
```ini
date.timezone = Europe/Belgrade
memory_limit = 2048M
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.3-fpm
```

### 4. Install and Configure Redis

```bash
# Install Redis
sudo apt-get install -y redis-server php8.3-redis

# Configure Redis to start on boot
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Secure Redis (edit /etc/redis/redis.conf)
sudo sed -i 's/^# requirepass .*/requirepass your_strong_redis_password/' /etc/redis/redis.conf

# Restart Redis to apply changes
sudo systemctl restart redis-server
```

Configure Symfony to use Redis for sessions. Add to `.env.local`:
```dotenv
REDIS_URL=redis://default:your_strong_redis_password@localhost:6379
```

Update `config/packages/framework.yaml`:
```yaml
framework:
    session:
        handler_id: '%env(REDIS_URL)%'
        cookie_secure: true
        cookie_samesite: lax
        storage_factory_id: session.storage.factory.native
```

Test Redis connection:
```bash
redis-cli ping
# Should return PONG
```

### 5. Configure MySQL

```bash
sudo mysql_secure_installation
```

Create database and user:
```sql
CREATE DATABASE solidarity;
CREATE USER 'solidarity'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON solidarity.* TO 'solidarity'@'localhost';
FLUSH PRIVILEGES;
```

### 6. Configure Nginx

Create `/etc/nginx/sites-available/solidarity`:
```nginx
server {
    listen 80;
    server_name your_domain.com;
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

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/solidarity /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 7. Deploy Application

Clone repository:
```bash
cd /var/www
git clone https://github.com/IT-Srbija-Org/solidaritySF.git solidarity
cd solidarity
```

Install dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

Set up environment:
```bash
cp .env .env.local
# Edit .env.local to set:
# - APP_ENV=prod
# - APP_SECRET=your_secret
# - DATABASE_URL=mysql://solidarity:your_password@127.0.0.1:3306/solidarity
# - MAILER_DSN=your_mail_configuration
```

Set permissions:
```bash
sudo chown -R www-data:www-data var
sudo chmod -R 775 var
```

### 8. Initialize Database

```bash
php bin/console doctrine:schema:create
```

Load initial data (if needed):
```bash
php bin/console doctrine:fixtures:load --group=1 --no-interaction
```

### 9. Final Steps

Clear cache:
```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Set up SSL with Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your_domain.com
```

### 10. Maintenance

Regular backups:
```bash
# Add to crontab
0 3 * * * mysqldump -u solidarity -p'your_password' solidarity > /backup/solidarity_$(date +\%Y\%m\%d).sql
```

Monitor logs:
```bash
tail -f /var/log/nginx/solidarity_error.log
```

## Security Considerations

1. Always keep the system updated
2. Use strong passwords
3. Configure firewall (UFW)
4. Regular security audits
5. Enable HTTPS only
6. Set up fail2ban
7. Regular backup strategy

## Performance Optimization

1. Enable OPcache
2. Configure PHP-FPM pool settings
3. Set up Redis for session storage
4. Configure Nginx caching
5. Use CDN for assets

## Monitoring

1. Set up application monitoring
2. Configure error logging
3. Monitor system resources
4. Set up alerts for critical events
