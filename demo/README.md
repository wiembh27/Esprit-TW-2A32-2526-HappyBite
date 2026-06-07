# Démo — HappyBite

## Présentation

**Canva :** https://canva.link/ferhpartzm84zrj

## Base de données démo

Pour retrouver la même application « remplie » que sur les captures (produits, posts, ~30 défis…) :

```text
Importer database/happybite_full_demo.sql dans phpMyAdmin (un seul fichier)
```

Ne pas lancer `install.bat` en plus. Voir [`database/README.md`](../database/README.md).

## Comptes de test (fictifs — démo ESPRIT)

Mot de passe pour **tous** : **`password`**

| Rôle | E-mail | Où aller après connexion |
|------|--------|--------------------------|
| Admin | `admin@happybite.tn` | Back Office |
| Fournisseur | `fournisseur@happybite.tn` | Mes produits (`List-Produit-Fournisseur.php`) |
| Nutritionniste | `nutritionniste@happybite.tn` | Dashboard défis (`nutritionniste_dashboard.php`) |
| Client | compte client existant | Accueil, panier, santé, communauté |

---

## Avertissement — changement de mot de passe et e-mail

> **À lire avant de tester le profil utilisateur**

La fonction **« Changer le mot de passe »** (`Profile_Utilisateur.php`) envoie un **vrai e-mail** via Gmail (SMTP configuré dans `config/mail.php`). Ce n’est pas une simulation.

### Pour les comptes démo (`@happybite.tn`)

Les adresses `admin@happybite.tn`, `fournisseur@happybite.tn`, etc. sont **fictives**.  
**Ne pas utiliser « Changer le mot de passe »** sur ces comptes : l’e-mail partira vers une boîte inexistante et le processus échouera.

Utilisez ces comptes uniquement pour **parcourir les pages selon le rôle** (catalogue fournisseur, dashboard nutritionniste, back-office…).

### Pour tester le changement de mot de passe

1. Créer un compte avec une **vraie adresse Gmail** que vous consultez, **ou**
2. Utiliser un compte existant dont l’e-mail en base est une **vraie adresse Gmail**.

L’e-mail de confirmation est envoyé **à l’adresse enregistrée sur le compte**, pas à une adresse saisie au moment du changement.

### Configuration requise côté machine qui exécute le projet

Pour que l’envoi fonctionne, l’installateur doit avoir :

```text
config/mail.php   (copié depuis config/mail.example.php)
```

avec un compte Gmail et un **mot de passe d’application Google** valide.

Sans `config/mail.php`, le changement de mot de passe par e-mail ne pourra pas aboutir.

---

## Captures d'écran (application en fonctionnement)

| Fichier | Module |
|---------|--------|
| `home1_v_fr.png` … `home4_v_fr.png` | Page d'accueil |
| `produit1_v_en.png` … `produit3_v_en.png` | Catalogue produits |
| `panier1_v_en.png`, `panier2_v_en.png` | Panier |
| `santé1_v_en.png` … `santé3_v_en.png` | Espace santé |
| `frigo1_v_en.png` | Frigo virtuel |
| `communauté1_v_en.png`, `communauté2_v_en.png` | Communauté |
| `challenge_v_en.png`, `challenge1_v_en.png` | Défis du jour |
| `track_gps_v_en.png` | Suivi livraison GPS |
| `ai_assistant_v_en.png` | Assistant IA |
| `login_frontoffice.png` | Connexion |
| `backoffice_home.png`, `backoffce.png` | Back Office admin |

## Déploiement en ligne

Non disponible — exécution locale via XAMPP.
