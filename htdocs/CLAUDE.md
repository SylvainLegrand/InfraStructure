# htdocs - Web Application

Main web application directory containing all PHP pages, classes, and modules.

## Key Files

| File | Purpose |
|------|---------|
| main.inc.php | Include in all web pages (full init) |
| master.inc.php | Lightweight init for CLI/background |

## Directory Overview

| Directory | Purpose |
|-----------|---------|
| core/ | Framework classes, libs, modules, triggers, hooks |
| api/ | REST API endpoints (Restler) |
| admin/ | System administration pages |
| conf/ | Configuration (conf.php - never commit) |
| custom/ | External modules (preserved on upgrade) |
| includes/ | Third-party libs (TCPDF, PHPMailer, Restler) |
| langs/ | Translations (118 languages) |
| theme/ | UI themes (eldy, md) |
| public/ | Public pages (no auth required) |
| install/ | Install/upgrade wizard, SQL schemas |
| [module]/ | Business modules |
