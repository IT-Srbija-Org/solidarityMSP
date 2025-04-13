# Mreža Solidarnosti

Mreža solidarnosti je inicijativa [IT Srbija](https://itsrbija.org/) za direktnu finansijsku podršku nastavnicima i vannastavnom osoblju čija je plata umanjena zbog obustave rada.

[![build](../../actions/workflows/build.yml/badge.svg)](../../actions/workflows/build.yml)

![GitHub stars](https://img.shields.io/github/stars/IT-Srbija-Org/solidaritySF?style=social)
![GitHub forks](https://img.shields.io/github/forks/IT-Srbija-Org/solidaritySF?style=social)
![GitHub watchers](https://img.shields.io/github/watchers/IT-Srbija-Org/solidaritySF?style=social)
![GitHub repo size](https://img.shields.io/github/repo-size/IT-Srbija-Org/solidaritySF)
![GitHub language count](https://img.shields.io/github/languages/count/IT-Srbija-Org/solidaritySF)
![GitHub top language](https://img.shields.io/github/languages/top/IT-Srbija-Org/solidaritySF)
![GitHub last commit](https://img.shields.io/github/last-commit/IT-Srbija-Org/solidaritySF?color=red)

## ❤️ Zajednica

[IT Srbija](https://itsrbija.org/) okuplja profesionalce iz svih oblasti informacionih tehnologija s ciljem umrežavanja, deljenja znanja i jačanja solidarnosti u IT industriji. Naša misija je povezivanje stručnjaka, podrška zajednici i kreiranje prilika za profesionalni razvoj.

## 🚀 Instalacija

Pre pokretanja projekta, potrebno je da na vašem računaru bude instaliran [Docker](https://www.docker.com/). Kompletna instalacija i inicijalna konfiguracija se vrši automatski pokretanjem sledeće komande:

```bash
bash ./configureProject.sh
```

Projekat će biti inicijalno podignut sa svim test podacima na adresi [localhost:1000](http://localhost:1000). Aplikacija koristi [passwordless](https://symfony.com/doc/6.4/security/login_link.html) autentifikaciju, tako da se umesto lozinke pri logovanju korisniku šalje link za prijavu na njegovu email adrese.

| Email              | Privilegije  |
|--------------------|--------------|
| korisnik@gmail.com | ROLE_USER    |
| delegat@gmail.com  | ROLE_DELEGAT |
| admin@gmail.com    | ROLE_ADMIN   |

Nakon unosa email adrese prilikom logovanja, link za prijavu će biti dostupan na adresi [localhost:1002](http://localhost:1002)
([Mailcatcher](https://mailcatcher.me/) service koji hvata sve email poruke u razvojnom okruženju).

## 📫 Imate pitanje?

Sva pitanja nam možete postaviti na zvanicnom [Discord](https://discord.gg/it-srbija) serveru.

## 🐞 Pronašli ste problem?

Slobodno napravite novi [Issue](https://github.com/IT-Srbija-Org/solidaritySF/issues) sa odgovarajućim naslovom i opisom. Ako ste već pronašli rešenje za problem, **slobodno otvorite [pull request](https://github.com/IT-Srbija-Org/solidaritySF/pulls)**.

## ❤️ Hvala!

<a href = "https://github.com/IT-Srbija-Org/solidaritySF/graphs/contributors">
    <img src = "https://contrib.rocks/image?repo=IT-Srbija-Org/solidaritySF"/>
</a>
