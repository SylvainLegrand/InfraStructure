# htdocs/install — Installation et schémas SQL

Assistant d'installation et fichiers de schéma de base de données.

## Structure des répertoires

| Sous-répertoire | Objectif |
|-----------------|----------|
| mysql/ | Fichiers SQL MySQL/MariaDB |
| pgsql/ | Fichiers SQL PostgreSQL (auto-générés) |
| doctemplates/ | Templates de documents installés par défaut |

## Types de fichiers SQL

| Motif | Objectif |
|-------|----------|
| `llx_*.sql` | Création de tables |
| `llx_*.key.sql` | Index et clés étrangères |
| `data_*.sql` | Données initiales/seed |
| `update_*.sql` | Fichiers de migration |

## Conventions de schéma

Consultez la skill `/dolibarr-sql-schema` pour les templates de tables, les champs obligatoires, les index et les motifs de migration.

## Étapes d'installation

1. `step1.php` - Accord de licence
2. `step2.php` - Configuration de la base de données
3. `step4.php` - Création de la base de données
4. `step5.php` - Création de l'utilisateur admin
