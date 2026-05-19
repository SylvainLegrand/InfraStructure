# htdocs/core — Framework central

Classes framework, bibliothèques, modules, abstraction de base de données, triggers et hooks.

## Structure des répertoires

| Sous-répertoire | Objectif |
|-----------------|----------|
| class/ | Classes framework central (CommonObject, Form, etc.) |
| lib/ | Bibliothèques de fonctions auxiliaires |
| modules/ | Descripteurs de modules et modèles de numérotation |
| triggers/ | Système de triggers d'événements |
| boxes/ | Classes de base pour widgets tableau de bord |
| tpl/ | Fragments de templates |
| db/ | Classes de pilotes de base de données |
| login/ | Gestionnaires d'authentification |
| menus/ | Classes du système de menus |
| substitutions/ | Système de substitution de variables |

## Classes clés

### CommonObject (class/commonobject.class.php)

Classe de base pour tous les objets métier. Consultez la skill `/dolibarr-class-conventions` pour la structure de classe, le tableau $fields, les motifs CRUD et les workflows de statut.

### Form (class/html.form.class.php)

Auxiliaire de formulaire pour générer les entrées HTML (sélecteurs, sélecteurs de date, etc.). Consultez la skill `/dolibarr-page-patterns` pour les exemples d'utilisation.

## Bibliothèques (lib/)

| Fichier | Objectif |
|---------|----------|
| functions.lib.php | Fonctions auxiliaires centrales |
| date.lib.php | Manipulation des dates |
| files.lib.php | Opérations sur les fichiers |
| security.lib.php | Fonctions de sécurité |
| pdf.lib.php | Auxiliaires de génération PDF |

## Triggers

Système d'événements pour les notifications inter-modules. Consultez la skill `/dolibarr-triggers`.

## Hooks

Points d'extension dans les pages centrales. Consultez la skill `/dolibarr-hooks`.
