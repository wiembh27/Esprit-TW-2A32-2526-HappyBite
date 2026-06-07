# Esprit-PW-2A32-2526-HappyBite

Plateforme web de nutrition intelligente **HappyBite** : recommandations personnalisées via un profil santé, frigo virtuel, recettes anti-gaspillage, e-commerce (panier / commandes / livraison), communauté, défis nutritionnistes, assistant IA et back-office administrateur.

**Groupe :** WebCrafters  
**Classe :** 2A32 — Projet Web  
**Année :** 2025-2026  
**Tuteur :** Donia Riahi

| Membre |
|--------|
| Bahar Wiem |
| Samar Allani |
| Inji Mejri |
| Mayssa Aouini |
| Mohamed Aziz Nciri |

---

## Technologies utilisées

| Couche | Stack |
|--------|-------|
| Frontend | HTML5, CSS3, JavaScript (ES6+), i18n FR/EN |
| Backend | PHP 8+ (natif, MVC léger) |
| Base de données | MySQL / MariaDB (via XAMPP) |
| IA (optionnel) | OpenAI API, Google Gemini API |

---

## Prérequis

- [XAMPP](https://www.apachefriends.org/) (PHP 8+ et MySQL/MariaDB)
- Navigateur moderne (Chrome, Firefox ou Edge)
- Extensions PHP : `pdo_mysql`, `curl`, `json`, `mbstring`, `gd` (recommandé)

---

## Installation (< 10 minutes)

### 1. Cloner le dépôt

```bash
git clone https://github.com/wiembh27/Esprit-TW-2A32-2526-HappyBite
cd Esprit-TW-2A32-2526-HappyBite
```

### 2. Copier le projet dans XAMPP

**Windows (PowerShell) :**

```powershell
Copy-Item -Recurse -Force . C:\xampp\htdocs\happybite
```

**macOS / Linux :**

```bash
cp -r . /opt/lampp/htdocs/happybite
```

### 3. Créer la base de données

**Option A — Démo complète (recommandée — 1 seul fichier) :**

Importe produits, recettes, posts, ~30 défis et comptes de test déjà remplis.

```bat
C:\xampp\mysql\bin\mysql.exe -u root < database\happybite_full_demo.sql
```

Ou via phpMyAdmin : importer `database/happybite_full_demo.sql`.

**Option B — Installation structurée (scripts séparés) :**

```bat
database\install.bat
```

Détails et ordre des migrations : [`database/README.md`](database/README.md).

> **Important :** utiliser **Option A ou B**, pas les deux.

### 4. Configurer les variables d'environnement

```bash
copy config\secrets.example.php config\secrets.php
copy config\mail.example.php config\mail.php
```

Éditer `config/secrets.php` et renseigner (optionnel — l'app fonctionne sans IA) :

- `OPENAI_API_KEY` — assistant santé / recettes IA
- `GEMINI_API_KEY` — génération de posts communauté

Voir aussi [`.env.example`](.env.example) pour les variables `DB_*`.

**Comptes de démonstration** — mot de passe pour tous : `password`

| Rôle | E-mail | Page principale à tester |
|------|--------|--------------------------|
| Admin | `admin@happybite.tn` | `BackOffice/login.php` → dashboard |
| Fournisseur | `fournisseur@happybite.tn` | `FrontOffice/List-Produit-Fournisseur.php` |
| Nutritionniste | `nutritionniste@happybite.tn` | `FrontOffice/nutritionniste_dashboard.php` |
| Client | tout compte client en base | `FrontOffice/Home.php` (panier, santé, frigo…) |

> Comptes fictifs (données dans `database/happybite_full_demo.sql` ou `seed_demo.sql`).  
> **Important :** ne pas tester « Changer le mot de passe » sur ces e-mails fictifs — un vrai mail Gmail est envoyé ; voir [`demo/README.md`](demo/README.md#avertissement--changement-de-mot-de-passe-et-e-mail).

---

## Lancement

1. Démarrer **Apache** et **MySQL** dans le panneau XAMPP.
2. Ouvrir dans le navigateur :

| Interface | URL |
|-----------|-----|
| Front Office (accueil) | http://localhost/happybite/FrontOffice/Home.php |
| Connexion utilisateur | http://localhost/happybite/FrontOffice/auth/login.php |
| Back Office | http://localhost/happybite/BackOffice/login.php |

**Alternative (serveur PHP intégré) :**

```bash
cd FrontOffice
php -S localhost:8000
```

Puis ouvrir http://localhost:8000/Home.php

---

## Variables d'environnement

| Variable | Description | Défaut |
|----------|-------------|--------|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_NAME` | Nom de la base | `happybite` |
| `DB_USER` | Utilisateur MySQL | `root` |
| `DB_PASS` | Mot de passe MySQL | *(vide)* |
| `OPENAI_API_KEY` | Clé OpenAI (optionnel) | via `config/secrets.php` |
| `GEMINI_API_KEY` | Clé Gemini (optionnel) | via `config/secrets.php` |

Fichiers locaux **non versionnés** : `config/secrets.php`, `config/mail.php`, `config/openai.key`

---

## Structure du projet

```
├── BackOffice/          # Administration (produits, commandes, utilisateurs…)
├── FrontOffice/         # Interface client (catalogue, panier, santé, communauté…)
├── Controllers/         # Logique métier
├── Models/              # Modèles de données
├── config/              # Connexion BDD, clés API (exemples fournis)
├── database/            # Schémas SQL, migrations, données de test
├── docs/                # Documentation technique
├── demo/                # Captures et lien de présentation
└── uploads/             # Médias uploadés (images produits, etc.)
```

Documentation détaillée : [`docs/architecture.md`](docs/architecture.md)

---

## Fonctionnalités principales

- Catalogue produits & recettes, panier et commandes avec suivi livraison
- Profil santé, suivi journalier et gamification
- Frigo virtuel et suggestions de recettes
- Communauté (posts, stories, commentaires)
- Défis du jour, roue de la fortune, validation IA
- Assistant IA (chat, conseils nutrition)
- Authentification (e-mail, Face ID WebAuthn)
- Back-office administrateur complet
- Mode sombre et internationalisation FR/EN

---

## Démo

- **Présentation Canva Ang :**https://canva.link/js121k5g6a4w3eg
- **Présentation Canva Fr:**https://canva.link/cssc5bbw9zm0l3z
- **Captures d'écran + guide évaluateur :** dossier [`demo/`](demo/) (comptes test, avertissement e-mail / mot de passe)
- **Déploiement en ligne :** https://happybite-demo.infinityfreeapp.com/ 

---

## Licence

Projet académique — ESPRIT School of Engineering, 2025-2026.
