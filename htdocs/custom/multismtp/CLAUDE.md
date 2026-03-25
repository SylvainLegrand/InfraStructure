# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Overview

**Multismtp** (module ID 402002) — Module Dolibarr permettant la configuration de comptes SMTP et IMAP par utilisateur. Chaque utilisateur peut avoir ses propres identifiants d'envoi/réception d'emails, au lieu d'utiliser la configuration système globale.

Compatibilité : Dolibarr 18–22, PHP 7.1–8.4 (source : `.opendsi_info.json`).

## Development Commands

```bash
# PHP syntax check (single file)
php -l path/to/file.php

# PHP syntax check (all PHP files)
find . -name "*.php" -exec php -l {} \;
```

Pas de framework de tests unitaires, pas de linter configuré (phpcs, phpstan). Le CI est géré via `.gitlab-ci.yml` qui inclut un template partagé OpenDSI (`opendsi/ci-templates`).

## Architecture

Le module repose sur trois mécanismes Dolibarr pour intercepter l'envoi d'emails :

1. **Hook `updateSession`** (`class/actions_multismtp.class.php`) — Appelé au chargement de chaque page, il injecte les identifiants SMTP de l'utilisateur courant dans `$conf->global` via `replaceConfiguration()`.
2. **Triggers** (`core/triggers/interface_99_modMultismtp_Multismtp.class.php`) — Intercepte les événements `*_SENTBYMAIL` pour sauvegarder une copie du mail envoyé dans le dossier IMAP "Sent" de l'utilisateur.
3. **Fonction centrale `replaceConfiguration()`** (`lib/multismtp.php`) — Remplace les constantes SMTP globales de Dolibarr par les identifiants de l'utilisateur (serveur, port, TLS, STARTTLS, login, mot de passe, et constantes OAuth2 dynamiques).

### Flux d'exécution

```
Page load → Hook updateSession → replaceConfiguration() → $conf->global SMTP overridden
Email send → Trigger *_SENTBYMAIL → Multismtp::saveMail() → Copie IMAP
CRON daily → cronRefreshOAuth2Tokens() → Refresh expired OAuth2 tokens
```

### Classes principales

- **`Multismtp`** (`class/Multismtp.class.php`) — Classe métier : CRUD des identifiants SMTP/IMAP par utilisateur (table `llx_user2smtp`), test de connexion SMTP/IMAP, listing des dossiers IMAP, sauvegarde des mails envoyés, gestion des tokens OAuth2.
- **`MultismtpImap`** (`class/MultismtpImap.class.php`) — Helper de connexion IMAP (abstraction sur l'extension PHP IMAP native et Webklex PHPIMAP).
- **`modMultismtp`** (`core/modules/modMultismtp.class.php`) — Descripteur de module. Déclare les hooks (`main`, `maildao`, `mail`), les triggers, les constantes, le CRON, et l'onglet utilisateur.
- **`ActionsMultismtp`** (`class/actions_multismtp.class.php`) — Gestionnaire de hooks.
- **`InterfaceMultismtp`** (`core/triggers/...`) — Gestionnaire de triggers.

### Points d'entrée

| URL | Rôle |
|-----|------|
| `user.php?id=X` | Onglet config email d'un utilisateur |
| `admin/setup.php` | Page de configuration admin du module |
| `admin/about.php` | Page À propos / Support |
| `admin/changelog.php` | Historique des versions |
| `ajax/oauthsetup.php` | AJAX — Formulaire de configuration OAuth2 personnalisé |

### Architecture OAuth2

Le module supporte l'authentification XOAUTH2 pour SMTP et IMAP :

- **Tokens** stockés dans `llx_oauth_token` via `DoliStorage`, identifiés par `{PROVIDER}-MultiSmtpUser{userId}{Smtp|Imap}`
- **Deux modes** : service OAuth2 pré-enregistré dans Dolibarr, ou configuration personnalisée par utilisateur
- **`getTokenOAuth2($oauth_service)`** — Récupère et rafraîchit le token d'accès (tampon de 30s avant expiration)
- **`cronRefreshOAuth2Tokens()`** — Tâche CRON quotidienne, rafraîchit tous les tokens expirés (Dolibarr + Multismtp). Seuil configurable via `MULTISMTP_CRON_REFRESH_TOKEN_DAYS` (défaut 30 jours)
- **Constantes dynamiques** : `replaceConfiguration()` injecte `OAUTH_{SERVICE_USER}_ID`, `_SECRET`, etc. dans `$conf->global` pour chaque utilisateur
- **Fournisseurs** : Google, Microsoft (avec tenant Azure), GitHub, générique
- **Double backend IMAP** : extension PHP IMAP native (défaut) ou Webklex PHPIMAP (`MAIN_IMAP_USE_PHPIMAP`, requis pour OAuth2 IMAP)
- **Bibliothèque OAuth** : `lib/oauth.lib.php` définit les providers disponibles

### Helpers

- **`lib/multismtp.php`** — `multismtpPrepareHead()` (onglets utilisateur), `replaceConfiguration()` (injection SMTP/IMAP/OAuth2)
- **`lib/oauth.lib.php`** — Définitions des fournisseurs OAuth2 disponibles
- **`lib/opendsi_common.lib.php`** — Utilitaires OpenDSI partagés

## Schéma SQL

Table unique `llx_user2smtp` : une ligne par utilisateur avec les colonnes SMTP (server, port, tls, starttls, id, pw, auth_type, oauth_*) et IMAP (server, port, tls, id, pw, folder, auth_type, oauth_*). Clé primaire = `fk_user` (FK vers `llx_user.rowid`).

Les colonnes OAuth2 (pour SMTP et IMAP) : `*_auth_type` (LOGIN/XOAUTH2), `*_oauth_service`, `*_oauth_provider`, `*_oauth_id`, `*_oauth_secret`, `*_oauth_url_authorize`, `*_oauth_scope`, `*_oauth_tenant`.

## Constantes de configuration

Les constantes clés dans `llx_const` :
- `MULTISMTP_SMTP_ENABLED` / `MULTISMTP_IMAP_ENABLED` — Active/désactive les fonctionnalités
- `MULTISMTP_ALLOW_CHANGESERVER` — Autorise les utilisateurs à changer le serveur SMTP et les paramètres OAuth2
- `MULTISMTP_SENT_ONLY_FROM_CARD` — N'applique MultiSMTP que pour les envois depuis les formulaires (pas les envois automatiques)
- `MULTISMTP_REPLACE_MAIL_EMAIL_FROM` — Remplace l'adresse FROM par l'identifiant SMTP de l'utilisateur
- `MULTISMTP_IMAP_NOVALIDATECERT` — Désactive la validation SSL (certificats auto-signés)
- `MULTISMTP_IMAP_CONF_SERVER` / `_PORT` / `_TLS` / `_AUTH_TYPE` / `_OAUTH_SERVICE` — Configuration IMAP partagée (optionnelle)
- `MULTISMTP_CRON_REFRESH_TOKEN_DAYS` — Jours après expiration pour rafraîchir les tokens OAuth2 (défaut 30)
- `MAIN_ACTIVATE_UPDATESESSIONTRIGGER` — Obligatoire (créée automatiquement), active le hook `updateSession`

## Conventions

- Fichier `VERSION` à la racine contient le numéro de version
- Fichier `.opendsi_info.json` déclare les compatibilités Dolibarr/PHP
- Les traductions sont dans `langs/{locale}/multismtp.lang` (fr_FR, en_US, es_ES)
- Le ChangeLog est dans `ChangeLog.md` à la racine
- Les fonctionnalités détaillées sont documentées dans `FEATURES.md`
- Les traductions du module sont chargées systématiquement dans le hook `updateSession`