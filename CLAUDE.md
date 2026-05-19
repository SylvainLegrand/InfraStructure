# Dolibarr ERP/CRM — Instance <INSTANCE NAME>

Open source ERP & CRM. PHP-based, no heavy frameworks, no Composer, no template engines.

## Instance

- **Version Dolibarr** : <VERSION LTS> (distribution « Dolibarr LTS by InfraS », basée sur Dolibarr officiel 22.0.x)
- **Répertoire** : `/mnt/web/<INSTANCE NAME>/htdocs/`
- **Modules custom** : `/mnt/web/<INSTANCE NAME>/htdocs/custom/`

## Before Starting Implementation

**Always load relevant skills first** before exploring or implementing Dolibarr code:

| Task Type | Skills to Load |
|-----------|----------------|
| New module | `/dolibarr-new-module`, `/dolibarr-module-descriptor` |
| Business class | `/dolibarr-class-conventions`, `/dolibarr-sql-schema` |
| PHP pages | `/dolibarr-page-patterns`, `/dolibarr-lib` |
| JavaScript/AJAX | `/dolibarr-js`, `/dolibarr-ajax` |
| Modal dialogs | `/dolibarr-dialogs` |
| Translations | `/dolibarr-translation` |
| Hooks/Triggers | `/dolibarr-hooks`, `/dolibarr-triggers` |
| PDF templates | `/dolibarr-pdf-template` |
| Tests | `/dolibarr-testing` |
| Widgets | `/dolibarr-widgets` |
| Cron jobs | `/dolibarr-cron` |
| Admin pages | `/dolibarr-page-patterns`, `/dolibarr-module-descriptor` |
| Menus | `/dolibarr-menus` |
| Tabs | `/dolibarr-tabs` |
| ExtraFields | `/dolibarr-extrafields` |

Load skills using the Skill tool: `/dolibarr-<skill-name>`

## Requirements

PHP 7.1-8.4 | MySQL 5.6+/MariaDB 10.0+ | PostgreSQL 9.6+

## Architecture

Active Record pattern, lightweight MVC, jQuery only.

## Directory Structure

| Directory | Purpose |
|-----------|---------|
| htdocs/ | Web application (PHP pages, classes, APIs) |
| test/ | PHPUnit tests |
| dev/ | Development tools and scripts |
| scripts/ | CLI scripts and cron jobs |

## Modules installés

La liste de base des modules (`/mnt/home/infras/dolinfras-2026/htdocs/custom/`) est commune à toutes les instances LTS. Cette instance a des modules supplémentaires ajoutés selon les besoins du client.

### Modules InfraS — base LTS (avec CLAUDE.md dédié)

| Module | Description |
|--------|-------------|
| `dolinfras` | Branding et suivi de version « Dolibarr LTS by InfraS » |
| `infrascusprice` | Prix clients par groupe de sociétés |
| `infrasdiscount` | Remises avancées (cascade, montant fixe, valeur cible) |
| `infraspackplus` | Génération PDF avancée (modèles, adresses, mentions, CGV, signatures) |
| `infrasproject` | Projets avancés (stock, marge provisionnelle, ventilation fournisseurs) |
| `infrassearch` | Recherche avancée multi-objets avec fil d'Ariane |
| `infrassupprice` | Mise à jour tarifs fournisseurs depuis documents commerciaux |
| `infrastechinfos` | Informations techniques produits/services sur documents |

### Modules InfraS — ajoutés pour cette instance (avec CLAUDE.md dédié)

| Module | Description |
|--------|-------------|

### Modules tiers — base LTS

Les modifications dans ces fichiers doivent porter les tags `// InfraS add` / `// InfraS change` :

`abricot`, `advancedictionaries`, `dbadmin`, `extraitcompteclient`, `listexportimport`, `multismtp`, `numberwords`, `oblyon`, `scaninvoices`, `scrollto`, `sirene`, `uptosign`, `zenfusionmaps`

### Modules tiers — ajoutés pour cette instance


## Règles de modification des fichiers

### Avant chaque modification de fichier existant

**Checklist obligatoire** :

```
1. Le fichier modifié se trouve-t-il dans htdocs/custom/infras* ?
   ├─ OUI (ex: htdocs/custom/infrastructure/, htdocs/custom/infraswidgets/)
   │   └─ ✓ PAS de tag requis — fichier InfraS
   │
   └─ NON (ex: htdocs/core/, htdocs/custom/banking4dolibarr/, etc.)
       └─ ✗ TAGS OBLIGATOIRES
          - Une ligne modifiée       → // InfraS change (fin de ligne)
          - Une ligne ajoutée        → // InfraS add (fin de ligne)  
          - Bloc de lignes           → // InfraS add begin / // InfraS add end
          - Bloc modifié             → // InfraS change begin / // InfraS change end
```

### Pourquoi cette règle ?

- **Modules InfraS** : code propriétaire, versionnement autonome, pas de conflit avec upstream
- **Fichiers core / tiers** : modifs visibles en upgrade Dolibarr, nécessitent traçabilité
- **Tags InfraS** : permettent de rebasee sur une nouvelle version de Dolibarr sans perdre les modifications

### Exemples

**Fichier core modifié (htdocs/main.inc.php)** :
```php
	}

	// InfraS add begin
	if (isset($_SESSION["dol_login"])) {
		$_SESSION["dol_tz_string"] = $tz_string_update;  // InfraS add
	}
	// InfraS add end
```

**Fichier tiers modifié (htdocs/custom/banking4dolibarr/...)** :
```php
$value = getDolGlobalString('MAIN_CONSTANT'); // InfraS change
```

## Documentation

https://wiki.dolibarr.org/index.php/Developer_documentation
