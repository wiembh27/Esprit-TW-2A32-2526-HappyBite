# Installation de la base de données

Deux méthodes possibles — **choisissez une seule**, ne les combinez pas.

---

## Option 1 — Démo complète (recommandée pour les évaluateurs)

Un seul import : base déjà remplie (produits, recettes, posts, ~30 défis, commandes, etc.).

### phpMyAdmin

1. Ouvrir http://localhost/phpmyadmin
2. Onglet **Importer**
3. Choisir `happybite_full_demo.sql`
4. Exécuter

### Ligne de commande (XAMPP Windows)

```bat
C:\xampp\mysql\bin\mysql.exe -u root < database\happybite_full_demo.sql
```

**Mot de passe de tous les comptes démo :** `password`

| Rôle | E-mail |
|------|--------|
| Admin | `admin@happybite.tn` |
| Fournisseur | `fournisseur@happybite.tn` |
| Nutritionniste | `nutritionniste@happybite.tn` |
| Clients fictifs | voir table `utilisateur` (ex. `sarra.benali@gmail.com`) |

> Données entièrement fictives, nettoyées pour la publication GitHub.

---

## Option 2 — Installation structurée (développement / conformité ESPRIT)

Scripts séparés : schéma + migrations + jeu minimal (`seed_demo.sql`).

### Windows (XAMPP)

```bat
database\install.bat
```

### Ordre d'import manuel

| # | Fichier | Contenu |
|---|---------|---------|
| 1 | `schema.sql` | Base `happybite`, catalogue de base |
| 2 | `schema_utilisateur_auth.sql` | Utilisateurs, WebAuthn |
| 3 | `schema_commande_livraison.sql` | Commandes, livraisons |
| 4 | `schema_sante_frigo.sql` | Profil santé, suivi, frigo |
| 5 | `communaute_tables_complete.sql` | Posts, commentaires, stories |
| 6 | `challenge_migration.sql` | Défis, participations, roue |
| 7 | `migration_settings.sql` | Préférences UI |
| 8 | `migration_user_notifications.sql` | Notifications |
| 9 | `migration_user_notifications_ref_key.sql` | Index notifications |
| 10 | `migration_utilisateur_profil_image.sql` | Photo de profil |
| 11 | `migration_commande_id_utilisateur.sql` | Lien commande ↔ utilisateur |
| 12 | `migration_fix_commande_date.sql` | Correction dates |
| 13 | `migration_livraison_timeline.sql` | Timeline livraison |
| 14 | `migration_livraison_transit.sql` | Statut transit |
| 15 | `migration_communaute_id_utilisateur.sql` | Auteur des posts |
| 16 | `migration_suivi_journalier_happybite.sql` | Suivi sport + IA |
| 17 | `seed_demo.sql` | Comptes démo + données minimales |

> `schema_social.sql` et `schema_stories.sql` sont remplacés par `communaute_tables_complete.sql`.

---

## Vérification

```sql
USE happybite;
SHOW TABLES;
SELECT email, role FROM utilisateur ORDER BY role, email;
SELECT COUNT(*) AS nb_challenges FROM challenge;
SELECT COUNT(*) AS nb_produits FROM produit;
```

---

## Fichiers SQL du dossier

| Fichier | Rôle |
|---------|------|
| `happybite_full_demo.sql` | **Import unique** — démo riche (recommandé jury) |
| `schema.sql` | Schéma principal ESPRIT |
| `seed_demo.sql` | Données minimales (Option 2) |
| `install.bat` | Enchaîne Option 2 sous Windows |
| `migration_*.sql` | Évolutions du schéma (Option 2) |
