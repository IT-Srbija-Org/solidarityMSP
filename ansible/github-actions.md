# GitHub Actions CI za Ansible Deploy

Ovaj dokument opisuje kako da podesite automatski deploy aplikacije Mreže Solidarnosti koristeći GitHub Actions i Ansible.

## Načini deploy-a (modovi)

Workflow podržava dva moda rada:

- **Full deployment**: Pokreće se samo prvi put u produkciji ili kada se menjaju sistemski fajlovi, konfiguracije, šabloni ili bilo šta van samog aplikacionog koda. Ovaj mod radi kompletan deploy i postavlja sve servise, konfiguracije i zavisnosti. Koristite ga kada uvodite nove servise, menjate konfiguracije servera ili radite veće izmene infrastrukture.

- **Redeployment koda (code update)**: Pokreće se redovno za update aplikacionog koda (npr. novi commit na branch). Ovaj mod koristi samo `--tags app` i deploy-uje samo aplikacioni kod bez menjanja sistemskih servisa ili konfiguracija. Brži je i bezbedniji za svakodnevni rad.

Izbor moda se vrši preko opcije **tags_deploy** prilikom pokretanja workflow-a na GitHub-u:
- `tags_deploy: true` — redeployment koda (samo aplikacija, podrazumevano)
- `tags_deploy: false` — full deployment (kompletan sistem)

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
5. Izaberite da li želite samo update aplikacije (`tags_deploy: true`) ili kompletan deploy (`tags_deploy: false`).
6. Pokrenite workflow.

## Šta workflow radi

- Proverava da li je korisnik koji pokreće workflow admin repozitorijuma.
- Klonira repozitorijum i priprema Ansible fajlove.
- Kopira `vars.yml.example` u `vars.yml` i koristi vrednosti iz GitHub Secrets za sensitive podatke.
- Pokreće Ansible playbook na serveru definisanom u `DOMAIN_NAME` secretu.
- Po potrebi koristi samo određene tagove (`--tags deploy`) ili radi kompletan deploy.

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
