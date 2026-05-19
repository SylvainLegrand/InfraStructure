# htdocs — Application web

Répertoire principal contenant toutes les pages PHP, classes et modules.

## Fichiers clés

| Fichier | Objectif |
|---------|----------|
| main.inc.php | Include dans toutes les pages web (initialisation complète) |
| master.inc.php | Initialisation légère pour CLI/arrière-plan |

## Vue d'ensemble des répertoires

| Répertoire | Objectif |
|-----------|----------|
| core/ | Classes framework, bibliothèques, modules, triggers, hooks |
| api/ | Points d'entrée API REST (Restler) |
| admin/ | Pages d'administration système |
| conf/ | Configuration (conf.php - ne jamais commiter) |
| custom/ | Modules externes (conservés à la mise à jour) |
| includes/ | Bibliothèques tierces (TCPDF, PHPMailer, Restler) |
| langs/ | Traductions (118 langues) |
| theme/ | Thèmes UI (eldy, md) |
| public/ | Pages publiques (pas d'auth requise) |
| install/ | Assistant d'installation/mise à jour, schémas SQL |
| [module]/ | Modules métier |
