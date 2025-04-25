# GitHub Actions CI za Ansible Deploy

Ovaj dokument opisuje kako da podesite automatski deploy aplikacije Mreže Solidarnosti koristeći GitHub Actions i Ansible.

## Načini deploy-a (modovi)

Workflow sada podržava tri moda rada preko opcije **deploy_mode**:

- **code-deploy** (podrazumevano): Pokreće samo taskove označene sa `deploy` tagom. Ovaj mod se koristi za update aplikacionog koda bez menjanja sistemskih servisa ili konfiguracija.
- **no-code-deploy**: Pokreće sve taskove osim onih sa `deploy` tagom (npr. ažuriranje sistema, servisa ili konfiguracija bez redeploy-a aplikacije). Ovim se izbegava kratkotrajno isključivanje aplikacije prilikom deploy-a.
- **all**: Pokreće sve taskove (potpuni redeploy, uključuje i deploy koda i sve sistemske izmene).

Izbor moda se vrši preko opcije **deploy_mode** prilikom pokretanja workflow-a na GitHub-u:
- `code-deploy` — samo aplikacioni kod (default)
- `no-code-deploy` — sve osim redeploy-a aplikacije
- `all` — kompletan sistem i aplikacija

> **Napomena:** Samo admin repozitorijuma ima pravo da pokrene deploy workflow na GitHub-u.

## Priprema repozitorijuma

1. U repozitorijumu se nalazi workflow fajl `.github/workflows/ansible-deploy.yml` koji omogućava automatski deploy na server.
2. Svi Ansible fajlovi i šabloni se nalaze u `ansible/` direktorijumu.

## Potrebni GitHub Secrets

Za bezbedan deploy, potrebno je da u GitHub repozitorijumu podesite sledeće Secrets:

- `SSH_PRIVATE_KEY` — privatni ključ za pristup serveru
- `DOMAIN_NAME` — domen/server na koji se deploy radi (npr. mrezasolidarnosti.org)
- `MYSQL_PASSWORD` — lozinka za aplikacionog korisnika baze
- `APP_SECRET` — aplikacioni secret
- `MAILER_DSN` — DSN za slanje emailova

Po potrebi dodajte i druge Secrets za varijable iz `vars.yml.example`.

## Pokretanje deploy-a

1. Idite na **Actions** tab na GitHub-u.
2. Izaberite workflow "Ansible Deploy".
3. Kliknite na **Run workflow**.
4. Unesite željeni branch (podrazumevano je `main`).
5. Izaberite željeni režim za `deploy_mode` (npr. `code-deploy`, `no-code-deploy`, ili `all`).
6. Pokrenite workflow.

## Šta workflow radi

- Proverava da li je korisnik koji pokreće workflow admin repozitorijuma.
- Klonira repozitorijum i priprema Ansible fajlove.
- Kopira `vars.yml.example` u `vars.yml` i koristi vrednosti iz GitHub Secrets za sensitive podatke.
- Pokreće Ansible playbook na serveru definisanom u `DOMAIN_NAME` secretu.
- Pokreće Ansible playbook sa odgovarajućim tagovima u zavisnosti od izabranog `deploy_mode`:
  - `code-deploy`: koristi `--tags deploy`
  - `no-code-deploy`: koristi `--skip-tags deploy`
  - `all`: bez tag filtera (sve taskove)

## Primer podešavanja inventara

Inventar se automatski generiše iz `DOMAIN_NAME` secreta:

```ini
[all]
mrezasolidarnosti.org
```

Nije potrebno ručno menjati `inventory.ini` za GitHub Actions deploy.

## Napomene

- Svi sensitive podaci treba da budu u GitHub Secrets, a ne u fajlovima u repozitorijumu.
- `vars.yml` se generiše automatski na osnovu `vars.yml.example` i Secrets.
- Samo admini repozitorijuma mogu ručno pokrenuti deploy workflow.

Za dodatna pitanja ili probleme, pogledajte [Ansible README](./README.md) ili kontaktirajte održavaoca projekta.
