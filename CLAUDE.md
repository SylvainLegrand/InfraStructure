# Dolibarr ERP/CRM

Open source ERP & CRM. PHP-based, no heavy frameworks, no Composer, no template engines.

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

## Documentation

https://wiki.dolibarr.org/index.php/Developer_documentation
