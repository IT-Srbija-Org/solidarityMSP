# Ansible Deployment for Solidarity Network

This directory contains Ansible playbooks and configuration for deploying the Solidarity Network application to production environments.

## Directory Structure

```yaml
ansible/production/
├── README.md           # This file
├── deploy.yml         # Main deployment playbook
├── vars.yml          # Variables configuration
└── templates/        # Jinja2 templates for configurations
    ├── etc/
    │   ├── nginx/sites-available/solidarity.j2
    │   ├── php/8.3/fpm/conf.d/custom.ini.j2
    │   └── redis/redis.conf.j2
    └── var/
        └── www/
            └── solidarity/
                └── .env.local.j2
```

## Prerequisites

1. Ansible installed on your local machine
2. Target server running Ubuntu 24.04 LTS or newer
3. SSH access to the target server
4. Python 3.x installed on the target server

### System Dependencies

The playbook will install all required dependencies:

- MySQL 8.0
- PHP 8.3 with extensions:
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

## Configuration

1. Copy `vars.yml.example` to `vars.yml`:

```bash
cp vars.yml.example vars.yml
```

2. Edit `vars.yml` and set your environment-specific values:

- Application settings (domain, repository, etc.)
- Database credentials
- Redis password
- SSL certificate email
- Other customizable parameters

## Usage

### 1. Set up your inventory

The `inventory.ini` file is not committed to version control (see `.gitignore`).
To set up your inventory, copy the provided example file and modify the host if needed (by default, `mrezasolidarnosti.org` is used as the production domain):

```bash
cp inventory.ini.example inventory.ini
```

Edit `inventory.ini` if you want to deploy to a different host.

Example `inventory.ini.example`:

```ini
[solidarity_servers]
mrezasolidarnosti.org ansible_user=root

[all:vars]
ansible_python_interpreter=/usr/bin/python3
```

### 2. Run the playbook

```bash
ansible-playbook -i inventory.ini deploy.yml
```

### 3. Verify deployment

After successful deployment:

1. Check if the application is accessible at `https://your-domain.com`
2. Verify SSL certificate is properly installed
3. Test the login functionality
4. Check logs for any errors:

```bash
tail -f /var/log/nginx/solidarity_error.log
```

## Rollback

In case you need to rollback to a previous version:

1. Specify the previous version in vars.yml:

```yaml
git_branch: v1.0.0  # Or specific commit hash
```

2. Run the playbook again:

```bash
ansible-playbook -i inventory.ini deploy.yml
```

## Maintenance

### Database Backups

Backups are automatically configured to run daily at 3 AM and stored in `/backup/`.

To manually trigger a backup:

```bash
ansible-playbook -i inventory.ini deploy.yml --tags backup
```

### Cache Clear

To clear application cache:

```bash
ansible-playbook -i inventory.ini deploy.yml --tags cache
```

## Security Notes

1. Always change default passwords in `vars.yml`
2. Keep `vars.yml` secure and never commit it to version control
3. Use strong passwords for all services
4. Regularly update system packages
5. Monitor system logs for suspicious activity

## Troubleshooting

1. Check Nginx error logs:

```bash
tail -f /var/log/nginx/solidarity_error.log
```

2. Check PHP-FPM logs:

```bash
tail -f /var/log/php8.3-fpm.log
```

3. Check Redis logs:

```bash
tail -f /var/log/redis/redis-server.log
```

4. Common issues:

- Permission problems: Check ownership of var/ directory
- Database connection: Verify credentials in .env.local
- Redis connection: Check Redis service status and password
- SSL certificate: Verify domain DNS settings
