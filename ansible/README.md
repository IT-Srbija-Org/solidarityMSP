# Ansible Deploy za Solidarity Network

Ovaj direktorijum sadrži Ansible playbook-ove i konfiguraciju za produkcijsko postavljanje aplikacije Mreže Solidarnosti.

## Struktura direktorijuma

```yaml
ansible
├── deploy.yml              # Glavni Ansible playbook za deploy
├── inventory.ini           # Inventar servera (nije u git-u)
├── inventory.ini.example   # Primer inventara servera
├── manual.md               # Detaljan vodič za ručno podizanje servera
├── tasks/                  # Ansible task fajlovi (modularni koraci deploy-a)
│   └── system.yml          # Taskovi za sistemske pripreme i update
│   ├── php.yml             # Taskovi za instalaciju i konfiguraciju PHP-a
│   ├── mysql.yml           # Taskovi za instalaciju i konfiguraciju MySQL-a
│   ├── nginx.yml           # Taskovi za instalaciju i konfiguraciju Nginx-a
│   ├── redis.yml           # Taskovi za instalaciju i konfiguraciju Redis-a
│   ├── app_setup.yml       # Taskovi za setup aplikacije
│   ├── backup.yml          # Taskovi za backup baze
│   ├── cache.yml           # Taskovi za čišćenje symfony cache-a
│   ├── db.yml              # Taskovi za symfony komande oko konfiguracija baze
├── templates/              # Jinja2 šabloni za konfiguracione fajlove
│   ├── etc/                # Šabloni za /etc konfiguracije
│   │   ├── my.cnf.j2                       # MySQL konfiguracija
│   │   ├── nginx/                          # Nginx konfiguracije
│   │   │   └── sites-available/
│   │   │       └── solidarity.j2           # Nginx vhost za aplikaciju
│   │   ├── php/                            # PHP konfiguracije
│   │   │   └── 8.3/
│   │   │       └── fpm/
│   │   │           └── conf.d/
│   │   │               └── custom.ini.j2   # Custom PHP FPM podešavanja
│   │   └── redis/
│   │       └── redis.conf.j2               # Redis konfiguracija
│   └── var/
│       └── www/
│           └── solidarity/
│               └── .env.local.j2           # Šablon za .env.local aplikacije
├── vars.yml                 # Glavna fajl sa konfiguracijom promenljivih (nije u git-u)
└── vars.yml.example         # Primer konfiguracije promenljivih
```

## Preduslovi

1. Instaliran Ansible na lokalnoj mašini
2. Remote server sa Ubuntu 24.04 LTS
3. SSH pristup  serveru
4. Python 3.x instaliran na serveru

### Sistemske zavisnosti

Playbook instalira sve potrebne zavisnosti:

- MySQL 8.0
- PHP 8.3 sa ekstenzijama:
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

## Konfiguracija

1. Kopirajte `vars.yml.example` u `vars.yml`:

```bash
cp vars.yml.example vars.yml
```

2. Izmenite `vars.yml` i podesite vrednosti za vašu sredinu:

- Podešavanja aplikacije (domen, repozitorijum, itd.)
- Baza podataka (korisnik, lozinka)
- Redis lozinka
- Email za SSL sertifikat
- Ostali parametri po potrebi

## Korišćenje

### 1. Podesite inventar

Fajl `inventory.ini` nije u git indexu (vidi `.gitignore`).
Da biste podesili inventory file, kopirajte primer i izmenite host po potrebi (podrazumevano je `mrezasolidarnosti.org`):

```bash
cp inventory.ini.example inventory.ini
```

Izmenite `inventory.ini` ako želite deploy na drugi server.

Primer `inventory.ini.example`:

```ini
[solidarity_servers]
mrezasolidarnosti.org ansible_user=root

[all:vars]
ansible_python_interpreter=/usr/bin/python3
```

### 2. Pokrenite playbook

```bash
ansible-playbook -i inventory.ini deploy.yml
```

### 3. Proverite deploy

Nakon uspešnog deploy-a:

1. Proverite da li je aplikacija dostupna na `https://vaš-domen.com`
2. Proverite da li je SSL sertifikat ispravno instaliran
3. Testirajte login funkcionalnost
4. Proverite logove za greške:

```bash
tail -f /var/log/nginx/solidarity_error.log
```

## Povratak na prethodnu verziju

Ako treba da vratite prethodnu verziju:

1. Podesite prethodnu verziju u vars.yml:

```yaml
git_branch: v1.0.0  # Ili određeni commit hash
```

2. Ponovo pokrenite playbook:

```bash
ansible-playbook -i inventory.ini deploy.yml
```

## Održavanje

### Backup baze

Backup se automatski pokreće svakog dana u 3h i čuva se u `/backup/`.

Ručno pokretanje backup-a:

```bash
ansible-playbook -i inventory.ini deploy.yml --tags backup
```

### Čišćenje keša

Za čišćenje keša aplikacije:

```bash
ansible-playbook -i inventory.ini deploy.yml --tags cache
```

## Bezbednosne napomene

1. Uvek promenite podrazumevane lozinke u `vars.yml`
2. Držite `vars.yml` van verzione kontrole
3. Koristite jake lozinke za sve servise
4. Redovno ažurirajte sistemske pakete
5. Pratite sistemske logove zbog sumnjivih aktivnosti

## Rešavanje problema

1. Proverite Nginx error log:

```bash
tail -f /var/log/nginx/solidarity_error.log
```

2. Proverite PHP-FPM log:

```bash
tail -f /var/log/php8.3-fpm.log
```

3. Proverite Redis log:

```bash
tail -f /var/log/redis/redis-server.log
```

4. Česti problemi:

- Dozvole: Proverite vlasništvo nad var/ direktorijumom
- Konekcija na bazu: Proverite kredencijale u .env.local
- Konekcija na Redis: Proverite status servisa i lozinku
- SSL sertifikat: Proverite DNS podešavanja domena
