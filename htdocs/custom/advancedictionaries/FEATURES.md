# Fonctionnalités Advanced Dictionaries

Ce document décrit les fonctionnalités du module **Advanced Dictionaries** pour Dolibarr.

**Version:** 14.0.18
**Éditeur:** Open-DSI (https://opendsi.fr)
**Compatibilité:** Dolibarr 10-22 | PHP 7.1-8.4

---

## Présentation Générale

Le module Advanced Dictionaries remplace et étend la gestion standard des dictionnaires Dolibarr. Il offre une interface unifiée pour gérer tous les dictionnaires (tables de référence/dropdown) avec des fonctionnalités avancées : multi-entité, API REST, autocomplétion AJAX, et plus de 25 types de champs.

---

## Fonctionnalités Principales

### Gestion des Dictionnaires

| Fonctionnalité | Description |
|----------------|-------------|
| **CRUD complet** | Créer, lire, modifier, supprimer des lignes de dictionnaire |
| **Activation/Désactivation** | Activer ou désactiver des lignes sans les supprimer |
| **Recherche avancée** | Filtrage multi-critères et recherche textuelle |
| **Tri multi-colonnes** | Tri ascendant/descendant sur toutes les colonnes |
| **Pagination** | Navigation par pages avec limite configurable |
| **Actions en masse** | Modification d'entité sur plusieurs lignes |

- **Conditions d'activation** : Module activé
- **Permissions requises** : `advancedictionaries->read` (lecture), `advancedictionaries->create` (ajout/modification), `advancedictionaries->delete` (suppression), `advancedictionaries->disable` (activation/désactivation)

### Remplacement des Dictionnaires Standards

Le module peut remplacer automatiquement la page native `admin/dict.php` de Dolibarr.

- **Activation** : Constante `ADVANCEDICTIONARIES_REPLACE_OLD_DICTIONARIES_PAGE` = 1
- **Fonctionnement** : Redirection automatique vers `/advancedictionaries/admin/dictionaries.php`

### Support Multi-Entité (Multicompany)

| Fonctionnalité | Description |
|----------------|-------------|
| **Filtrage par entité** | Affichage des lignes selon l'entité courante |
| **Changement d'entité** | Déplacer des lignes vers une autre entité |
| **Partage inter-entités** | Configuration du partage via `getEntity()` |

- **Dépendances** : Module Multicompany (optionnel)

### Types de Champs Supportés

Le module gère plus de 25 types de champs différents :

| Type | Description |
|------|-------------|
| `varchar` | Texte court |
| `text` | Texte long (textarea) |
| `int` | Nombre entier |
| `float`, `double` | Nombre décimal |
| `date`, `datetime` | Date et date/heure |
| `boolean` | Case à cocher oui/non |
| `price` | Montant monétaire |
| `phone` | Numéro de téléphone |
| `mail` | Adresse email |
| `url` | Lien URL |
| `password` | Mot de passe masqué |
| `select` | Liste déroulante statique |
| `sellist` | Liste depuis table SQL |
| `radio` | Boutons radio |
| `checkbox` | Cases à cocher multiples |
| `chkbxlst` | Cases à cocher depuis table SQL |
| `chkbxlstwithorder` | Cases à cocher ordonnables |
| `link` | Lien vers objet Dolibarr |
| `custom` | Champ personnalisé |

### Fonctionnalités Avancées des Champs

| Fonctionnalité | Description |
|----------------|-------------|
| **Champs obligatoires** | Validation avant enregistrement |
| **Valeurs par défaut** | Pré-remplissage automatique |
| **Champs en lecture seule** | Via `fixed_value` |
| **Aide contextuelle** | Tooltips et textes d'aide |
| **Mise à jour en cascade** | Rechargement AJAX des listes dépendantes |
| **Autocomplétion** | Recherche Select2/AJAX |
| **Multiselection** | Sélection de valeurs multiples |

---

## API REST

Le module expose une API REST complète pour l'accès programmatique aux dictionnaires.

### Endpoints Disponibles

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/rest/advancedictionaries/{module}/{name}` | Liste les lignes |
| `GET` | `/api/rest/advancedictionaries/{module}/{name}/{id}` | Récupère une ligne |
| `POST` | `/api/rest/advancedictionaries/{module}/{name}` | Crée une ligne |
| `PUT` | `/api/rest/advancedictionaries/{module}/{name}/{id}` | Met à jour une ligne |
| `DELETE` | `/api/rest/advancedictionaries/{module}/{name}/{id}` | Supprime une ligne |

### Paramètres de Liste

| Paramètre | Description |
|-----------|-------------|
| `sort_field` | Champ de tri |
| `sort_order` | Ordre de tri (ASC/DESC) |
| `limit` | Nombre maximum de résultats |
| `page` | Numéro de page |

- **Permissions requises** : Token API avec droits `advancedictionaries`

---

## Points d'Entrée AJAX

### `/ajax/dictionary.php`

Récupère les options d'un dictionnaire pour les sélecteurs.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `module` | alpha | Module du dictionnaire |
| `name` | alpha | Nom du dictionnaire |
| `htmlname` | alpha | Nom HTML du champ |
| `key` | alpha | Champ clé (défaut: rowid) |
| `label` | alpha | Pattern du libellé |
| `filters` | array | Filtres SQL (JSON) |
| `orders` | array | Tri (JSON) |
| `outjson` | int | 0=HTML, 1=JSON |

### `/ajax/get_select_options.php`

Récupère les options pour mise à jour en cascade de sélecteurs liés.

---

## Triggers (Déclencheurs)

Les opérations sur les dictionnaires peuvent déclencher les triggers Dolibarr standards.

| Contexte | Description |
|----------|-------------|
| `addLine()` | Trigger après ajout de ligne |
| `updateLine()` | Trigger après modification |
| `deleteLine()` | Trigger après suppression |
| `activeLine()` | Trigger après activation/désactivation |

Le paramètre `$noTrigger` permet de désactiver les triggers si nécessaire.

---

## Hooks Implémentés

### Contexte `main` et `login`

| Hook | Description |
|------|-------------|
| `updateSession` | Redirige depuis `admin/dict.php` vers le module |
| `afterLogin` | Redirige depuis `admin/dict.php` après connexion |

- **Conditions** : Constante `ADVANCEDICTIONARIES_REPLACE_OLD_DICTIONARIES_PAGE` activée
- **Action** : Redirection HTTP 302 vers `/advancedictionaries/admin/dictionaries.php`

---

## Configuration

### Constantes du Module

| Constante | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `ADVANCEDICTIONARIES_REPLACE_OLD_DICTIONARIES_PAGE` | chaine | 0 | Remplacer la page standard des dictionnaires |
| `ADVANCEDICTIONARIES_VERSION` | chaine | - | Version installée du module |
| `ADVANCEDICTIONARIES_DICTIONARY_{NAME}_VERSION` | chaine | - | Version de chaque dictionnaire spécifique |

### Page de Configuration

**URL** : `/advancedictionaries/admin/setup.php`

Options disponibles :
- Activer/Désactiver le remplacement de la page dictionnaires standard

---

## Permissions

| ID | Code | Description | Défaut |
|----|------|-------------|--------|
| 163028 | `read` | Lire les dictionnaires | Non |
| 163029 | `create` | Créer et modifier les lignes | Non |
| 163030 | `delete` | Supprimer les lignes | Non |
| 163031 | `disable` | Activer/Désactiver les lignes | Non |

### Utilisation dans le Code

```php
if ($user->hasRight('advancedictionaries', 'read')) { /* ... */ }
if ($user->hasRight('advancedictionaries', 'create')) { /* ... */ }
if ($user->hasRight('advancedictionaries', 'delete')) { /* ... */ }
if ($user->hasRight('advancedictionaries', 'disable')) { /* ... */ }
```

---

## Menus

| Menu | Parent | URL | Permission |
|------|--------|-----|------------|
| Dictionnaires avancés | Configuration | `/advancedictionaries/admin/dictionaries.php` | `advancedictionaries->read` |

---

## Classes Principales

### `Dictionary`

Classe principale de gestion des dictionnaires.

| Méthode | Description |
|---------|-------------|
| `fetchAllDictionaries()` | Récupère tous les dictionnaires |
| `getDictionary()` | Récupère un dictionnaire spécifique |
| `fetch_lines()` | Récupère les lignes du dictionnaire |
| `fetch_array()` | Récupère un tableau clé/valeur |
| `addLine()` | Ajoute une ligne |
| `updateLine()` | Met à jour une ligne |
| `deleteLine()` | Supprime une ligne |
| `activeLine()` | Active/désactive une ligne |
| `createTables()` | Crée les tables SQL |

### `DictionaryLine`

Classe représentant une ligne de dictionnaire.

| Méthode | Description |
|---------|-------------|
| `fetch()` | Récupère une ligne |
| `insert()` | Insère une ligne |
| `update()` | Met à jour une ligne |
| `delete()` | Supprime une ligne |
| `active()` | Active/désactive |
| `getLabel()` | Obtient le libellé formaté |

### `FormDictionary`

Classe de génération des formulaires et sélecteurs.

| Méthode | Description |
|---------|-------------|
| `select_dictionary()` | Sélecteur avec autocomplétion AJAX |
| `select_dictionary_list()` | Liste déroulante simple |

---

## Exemple d'Utilisation

### Récupérer un Dictionnaire

```php
require_once DOL_DOCUMENT_ROOT.'/custom/advancedictionaries/class/dictionary.class.php';

$dictionary = Dictionary::getDictionary($db, 'mymodule', 'mydict');
if ($dictionary && $dictionary->enabled) {
    $dictionary->fetch_lines();
    foreach ($dictionary->lines as $line) {
        echo $line->label;
    }
}
```

### Ajouter une Ligne

```php
$fieldsValues = array(
    'code' => 'MYCODE',
    'label' => 'Mon libellé',
    'active' => 1
);
$result = $dictionary->addLine($fieldsValues, $user);
```

### Afficher un Sélecteur

```php
require_once DOL_DOCUMENT_ROOT.'/custom/advancedictionaries/class/html.formdictionary.class.php';

$formdictionary = new FormDictionary($db);
echo $formdictionary->select_dictionary(
    'mymodule',              // module
    'mydict',                // nom
    $selected,               // valeur sélectionnée
    'myselect',              // nom HTML
    '1',                     // option vide
    'rowid',                 // champ clé
    '{{label}}',             // pattern libellé
    array(),                 // filtres
    array('label' => 'ASC'), // tri
    0,                       // forcecombo
    array(),                 // events
    1                        // autocomplétion
);
```

---

## Structure des Fichiers

```
advancedictionaries/
├── admin/                    # Pages d'administration
│   ├── setup.php             # Configuration
│   ├── dictionaries.php      # Gestion des dictionnaires
│   ├── about.php             # À propos
│   └── changelog.php         # Historique
├── ajax/                     # Points AJAX
│   ├── dictionary.php
│   └── get_select_options.php
├── class/                    # Classes métier
│   ├── dictionary.class.php
│   ├── html.formdictionary.class.php
│   ├── api_advancedictionaries.class.php
│   └── actions_advancedictionaries.class.php
├── core/
│   ├── modules/
│   │   └── modAdvanceDictionaries.class.php
│   ├── tpl/
│   │   └── dictionaries.tpl.php
│   └── actions_dictionaries.inc.php
├── css/                      # Styles
├── js/                       # JavaScript
├── langs/                    # Traductions (en_US, fr_FR)
├── lib/                      # Fonctions utilitaires
└── includes/                 # Librairies (Parsedown)
```

---

## Dépendances

### Modules Dolibarr

- Aucune dépendance obligatoire
- **Multicompany** (optionnel) : Support multi-entité avancé

### Librairies Incluses

- **Parsedown** : Parseur Markdown pour le changelog

---

## Support

- **Email** : support@open-dsi.fr
- **Site** : https://opendsi.fr
- **Documentation** : https://wiki.dolibarr.org