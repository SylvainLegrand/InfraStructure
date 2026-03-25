# Fonctionnalités Multismtp

Ce document décrit les fonctionnalités du module Multismtp pour Dolibarr.

---

## Configuration SMTP par utilisateur

Permet à chaque utilisateur Dolibarr de disposer de ses propres identifiants SMTP pour l'envoi d'emails, au lieu d'utiliser la configuration système globale.

- **Condition d'activation** : La constante `MULTISMTP_SMTP_ENABLED` doit être activée et le mode d'envoi Dolibarr doit être configuré sur `SMTP/SMTPS socket library` (pas sur la fonction `mail()` de PHP)
- **Permissions requises** :
  - L'utilisateur peut modifier sa propre configuration s'il possède le droit `user->self->creer`
  - Un administrateur peut modifier la configuration de n'importe quel utilisateur avec le droit `user->user->creer`
- **Champs configurables par utilisateur** : identifiant SMTP, mot de passe SMTP
- **Champs optionnels** (si `MULTISMTP_ALLOW_CHANGESERVER` est activé) : serveur SMTP, port SMTP, TLS, STARTTLS, méthode d'authentification, paramètres OAuth2

### Injection des identifiants

La fonction `replaceConfiguration()` (`lib/multismtp.php`) remplace au chargement de chaque page les constantes globales Dolibarr suivantes par les valeurs de l'utilisateur courant :
- `MAIN_MAIL_SMTP_SERVER`, `MAIN_MAIL_SMTP_PORT`
- `MAIN_MAIL_EMAIL_TLS`, `MAIN_MAIL_EMAIL_STARTTLS`
- `MAIN_MAIL_SMTPS_ID`, `MAIN_MAIL_SMTPS_PW`
- `MAIN_MAIL_SMTPS_AUTH_TYPE`, `MAIN_MAIL_SMTPS_OAUTH_SERVICE`
- `MAIN_MAIL_EMAIL_FROM` (optionnel, si `MULTISMTP_REPLACE_MAIL_EMAIL_FROM` est activé)

### Pages exclues du remplacement SMTP

Le remplacement de la configuration ne s'applique pas sur :
- `/admin/mails.php` — page de configuration email globale Dolibarr
- `/multismtp/admin/setup.php` — page de configuration du module
- `/multismtp/user.php` — onglet email de l'utilisateur

### Mode formulaire uniquement

Lorsque `MULTISMTP_SENT_ONLY_FROM_CARD` est activé, les identifiants de l'utilisateur ne sont injectés que pour les envois depuis un formulaire email Dolibarr (action `send` avec `fromtype=user`), pas pour les envois automatisés (notifications, etc.).

### Remplacement de l'adresse d'expédition

Lorsque `MULTISMTP_REPLACE_MAIL_EMAIL_FROM` est activé, l'adresse email d'expédition (`MAIN_MAIL_EMAIL_FROM`) est remplacée par l'identifiant SMTP de l'utilisateur.

---

## Configuration IMAP par utilisateur

Permet la copie automatique des emails envoyés depuis Dolibarr vers le dossier "Envoyés" du serveur IMAP de l'utilisateur.

- **Condition d'activation** : La constante `MULTISMTP_IMAP_ENABLED` doit être activée **et** l'extension PHP `imap` (`function_exists('imap_open')`) ou la bibliothèque Webklex PHPIMAP (`MAIN_IMAP_USE_PHPIMAP`) doit être disponible
- **Permissions requises** : identiques à la section SMTP
- **Champs configurables par utilisateur** : serveur IMAP, port IMAP, TLS, identifiant, mot de passe, méthode d'authentification, dossier de stockage

### Double backend IMAP

Le module supporte deux implémentations IMAP, sélectionnées via la constante `MAIN_IMAP_USE_PHPIMAP` :
- **Extension PHP IMAP native** (`imap_open`, `imap_list`, `imap_append`) — backend par défaut
- **Bibliothèque Webklex PHPIMAP** (`Webklex\PHPIMAP\ClientManager`) — alternative moderne, **requise pour OAuth2 IMAP**

La classe `MultismtpImap` (`class/MultismtpImap.class.php`) abstrait les deux backends et expose une interface unifiée : `connect()`, `disconnect()`, `getImapFolders()`, `saveMail()`.

### Mode serveur IMAP partagé

L'administrateur peut forcer un serveur IMAP commun pour tous les utilisateurs via les constantes `MULTISMTP_IMAP_CONF_SERVER`, `MULTISMTP_IMAP_CONF_PORT`, `MULTISMTP_IMAP_CONF_TLS`, `MULTISMTP_IMAP_CONF_AUTH_TYPE` et `MULTISMTP_IMAP_CONF_OAUTH_SERVICE`. Dans ce cas, les utilisateurs ne configurent que leurs identifiants, mot de passe et dossier cible.

### Sélection du dossier IMAP

L'utilisateur peut parcourir et sélectionner un dossier IMAP parmi ceux disponibles sur son serveur. La sélection se fait via un formulaire standard Dolibarr (icône d'édition, sélection du dossier dans une liste déroulante, validation) qui met à jour le dossier en base de données. Les dossiers sont chargés en amont de la page pour une meilleure gestion des erreurs.

### Sauvegarde des emails envoyés

Lors de chaque envoi d'email depuis Dolibarr, le trigger intercepte l'événement et appelle `Multismtp::saveMail()` qui copie le message dans le dossier IMAP sélectionné via `MultismtpImap::saveMail()`. Les modes d'envoi supportés sont :
- `smtps` : récupère header + body via `$mailfile->smtps`
- `swiftmailer` : utilise `$mailfile->message->toString()`
- `mail` (PHP natif) : reconstruit le message à partir de `$mailfile->headers` et `$mailfile->message`

### Certificats auto-signés

Si `MULTISMTP_IMAP_NOVALIDATECERT` est activé, la validation du certificat SSL est désactivée pour les connexions IMAP (flag `/novalidate-cert`).

---

## Support OAuth2

Permet l'authentification OAuth2 (type `XOAUTH2`) pour SMTP et IMAP, en remplacement de l'authentification classique par mot de passe.

- **Condition d'activation** : Dolibarr 18+ (SMTP), Dolibarr 22+ ou Easya 2024+ (IMAP), et `MULTISMTP_ALLOW_CHANGESERVER` activé pour le SMTP
- **Fournisseurs supportés** : Google, Microsoft (Azure/Office365 avec tenant), GitHub, et fournisseur générique (`OAUTH_OTHER_NAME`, disponible si `MAIN_FEATURES_LEVEL >= 2`)

### Deux modes de configuration OAuth2

1. **Service pré-enregistré** : utilise un service OAuth2 déjà configuré dans Dolibarr (menu Administration > OAuth). Les identifiants (client ID, secret, URL, scope, tenant) sont hérités de la configuration globale. L'utilisateur sélectionne un service dans une liste déroulante.
2. **Configuration personnalisée** : l'utilisateur définit ses propres identifiants OAuth2 (fournisseur, client ID, secret, URL d'autorisation, scopes, tenant). Le formulaire de configuration est généré dynamiquement via AJAX (`ajax/oauthsetup.php`), qui affiche les champs spécifiques au fournisseur sélectionné (ex : tenant pour Microsoft, URL d'autorisation pour le fournisseur générique).

### Gestion des tokens

- Les tokens OAuth2 sont stockés dans le système de stockage OAuth de Dolibarr (`DoliStorage`, table `llx_oauth_token`) avec un identifiant unique par utilisateur et par protocole : `{PROVIDER}-MultiSmtpUser{userId}{Smtp|Imap}`
- Rafraîchissement automatique des tokens expirés avec un tampon de 30 secondes avant l'expiration (`Multismtp::getTokenOAuth2()`)
- Interface de gestion des tokens sur la fiche utilisateur (fonction `printOauthAccessTokenManagment()`) : génération, renouvellement, suppression et vérification de l'expiration
- Les paramètres OAuth2 (service, fournisseur, scopes) ne peuvent plus être modifiés tant qu'un token actif existe (il faut d'abord supprimer le token)
- Le refresh token est toujours préservé lors du rafraîchissement (critique pour Google qui ne le fournit qu'une seule fois)

### Constantes OAuth2 dynamiques

Lors du remplacement de la configuration, la méthode `replaceConfiguration()` crée dynamiquement les constantes suivantes dans `$conf->global` pour que le système OAuth2 de Dolibarr fonctionne avec les identifiants spécifiques à l'utilisateur :
- `OAUTH_{SERVICE_USER}_ID`
- `OAUTH_{SERVICE_USER}_SECRET`
- `OAUTH_{SERVICE_USER}_URLAUTHORIZE`
- `OAUTH_{SERVICE_USER}_TENANT`
- `OAUTH_{SERVICE_USER}_SCOPE`

Les pages d'administration OAuth sont exclues de ce remplacement : `/admin/oauth.php`, `/admin/oauthlogintokens.php`, `/admin/mails.php`, `/multismtp/admin/setup.php`.

---

## Onglet utilisateur "Email"

Un onglet **Email** est ajouté sur la fiche utilisateur Dolibarr (`user:+email`), accessible à l'URL `/multismtp/user.php?id=__ID__`.

Cet onglet affiche :
- La section **SMTP** (si `MULTISMTP_SMTP_ENABLED`) : serveur, port, TLS, STARTTLS, méthode d'authentification, identifiant, mot de passe (masqué), service OAuth2, gestion du token OAuth2
- La section **IMAP** (si `MULTISMTP_IMAP_ENABLED`) : serveur, port, TLS, méthode d'authentification, identifiant, mot de passe (masqué), service OAuth2, gestion du token OAuth2, sélection du dossier IMAP
- Un bouton **Modifier** pour passer en mode édition (si l'utilisateur a les droits)
- En mode édition, un formulaire avec champs de saisie et sélection du type d'authentification (mot de passe ou OAuth2)
- Un avertissement si aucune fonctionnalité n'est activée, avec un lien vers la configuration admin

---

## Pages d'administration

Accessible via `admin/setup.php`, réservée aux administrateurs (`$user->admin`).

### Section SMTP
- Activation/désactivation de la fonctionnalité SMTP (`MULTISMTP_SMTP_ENABLED`)
- Activation du mode formulaire uniquement (`MULTISMTP_SENT_ONLY_FROM_CARD`)
- Autorisation pour les utilisateurs de changer de serveur SMTP (`MULTISMTP_ALLOW_CHANGESERVER`)
- Avertissements si l'envoi d'emails est désactivé (`MAIN_DISABLE_ALL_MAILS`) ou si le mode d'envoi est sur `mail` au lieu de `smtps`

### Section IMAP
- Activation/désactivation de la bibliothèque Webklex PHPIMAP (`MAIN_IMAP_USE_PHPIMAP`)
- Activation/désactivation de la fonctionnalité IMAP (`MULTISMTP_IMAP_ENABLED`) — indisponible si aucun backend IMAP n'est disponible
- Bypass de la validation de certificat SSL (`MULTISMTP_IMAP_NOVALIDATECERT`)
- Configuration du serveur IMAP partagé : hôte, port, SSL/TLS, méthode d'authentification (`LOGIN` ou `XOAUTH2`), service OAuth2
- Affichage/masquage dynamique du champ service OAuth2 selon la méthode d'authentification sélectionnée

### Section Cron / OAuth2
- Configuration du seuil de rafraîchissement des tokens OAuth2 (`MULTISMTP_CRON_REFRESH_TOKEN_DAYS`)

---

## Tâche planifiée (Cron)

### Rafraîchir les tokens OAuth2 expirés

| Propriété | Valeur |
|-----------|--------|
| **Classe** | `Multismtp` (`/multismtp/class/Multismtp.class.php`) |
| **Méthode** | `cronRefreshOAuth2Tokens()` |
| **Fréquence** | Quotidienne (`unitfrequency = 86400`) |
| **Statut par défaut** | Désactivé (`status = 0`) |
| **Condition** | `isModEnabled("multismtp")` |
| **Priorité** | 50 |

**Fonctionnement détaillé :**

1. Récupère tous les tokens de la table `llx_oauth_token` (entité courante)
2. Déchiffre et désérialise chaque token (`dolDecrypt` + `unserialize`)
3. **Filtres d'exclusion** : tokens sans refresh token, tokens à expiration permanente (`EOL_NEVER_EXPIRES`, `EOL_UNKNOWN`), tokens dont l'expiration n'est pas encore dans le seuil configurable
4. **Pour les tokens Multismtp** (format `{Provider}-MultiSmtpUser{id}{Smtp|Imap}`) : charge l'utilisateur correspondant et appelle `replaceConfiguration()` pour injecter ses credentials OAuth dans `$conf->global`
5. **Pour les tokens Dolibarr officiels** : les credentials sont déjà présentes dans `$conf->global`
6. **Tokens sans credentials configurées** : ignorés (appartiennent à d'autres modules)
7. Crée le service OAuth via `ServiceFactory`, rafraîchit le token via `refreshAccessToken()`, préserve le refresh token, puis stocke le nouveau token
8. **Rapport** : retourne un rapport HTML détaillé (succès/erreur par token) visible dans l'interface Cron et dans les logs syslog. Retourne `0` si tout est OK, `-1` si au moins une erreur

**Seuil configurable** : la constante `MULTISMTP_CRON_REFRESH_TOKEN_DAYS` (défaut `30`) définit le nombre de jours **après expiration** déclenchant le rafraîchissement. Un token est rafraîchi si `endOfLife + (seuil * 86400) <= now`.

---

## Triggers (Déclencheurs)

### Classe `InterfaceMultismtp` (`core/triggers/interface_99_modMultismtp_Multismtp.class.php`)

#### Événements d'envoi d'email (`*_SENTBYMAIL`)

- **Événements** : `COMPANY_SENTBYMAIL`, `BILL_SENTBYMAIL`, `BILL_SUPPLIER_SENTBYMAIL`, `ORDER_SENTBYMAIL`, `ORDER_SUPPLIER_SENTBYMAIL`, `PROPAL_SENTBYMAIL`, `PROPOSAL_SUPPLIER_SENTBYMAIL`, `SUPPLIER_PROPOSAL_SENTBYMAIL`, `SHIPPING_SENTBYMAIL`, `RECEPTION_SENTBYMAIL`, `FICHINTER_SENTBYMAIL`, `USER_SENTBYMAIL`, `MEMBER_SENTBYMAIL`, `BOM_SENTBYMAIL`, `CONTACT_SENTBYMAIL`, `CONTRACT_SENTBYMAIL`
- **Condition** : `MULTISMTP_IMAP_ENABLED` activé et backend IMAP disponible (`MultismtpImap::isEnabled()`)
- **Action** : Sauvegarde une copie de l'email envoyé dans le dossier IMAP de l'utilisateur via `Multismtp::saveMail()`. Vérifie `$mailfile->mail_saved` pour éviter les doublons.

#### Événement de mise à jour de session (`USER_UPDATE_SESSION`)

- **Condition** : `MULTISMTP_SMTP_ENABLED` activé et la page courante n'est pas `admin/mails.php`
- **Action** : Appelle `Multismtp::replaceConfiguration()` pour injecter les identifiants SMTP de l'utilisateur dans `$conf->global`

---

## Hooks

Le module déclare des hooks sur les contextes : `main`, `maildao`, `mail`.

| Hook | Contexte | Condition | Action |
|------|----------|-----------|--------|
| `updateSession` | `main` | `MULTISMTP_SMTP_ENABLED` activé et page courante != `admin/mails.php` | Appelle `replaceConfiguration()` pour injecter les identifiants SMTP/IMAP/OAuth2 de l'utilisateur, charge les traductions du module |

---

## Endpoint AJAX

### `ajax/oauthsetup.php`

- **Méthode** : POST
- **Paramètres** : `id` (int — ID utilisateur), `type` (string — `smtp` ou `imap`), `provider` (string — clé du fournisseur OAuth2 ex. `OAUTH_GOOGLE`)
- **Permissions** : Vérifie les droits d'accès de l'utilisateur connecté sur l'utilisateur cible (`checkUserAccessToObject`)
- **Réponse** : JSON `{"content": "<html>"}` avec le formulaire de configuration OAuth2 spécifique au fournisseur, ou `{"error": "message"}`
- **Champs générés** : URL de callback, API ID, API Secret, Tenant (Microsoft uniquement), Scopes (checkboxes ou champ texte selon le fournisseur)

---

## Configuration

### Constantes activables/désactivables (on/off)

| Constante | Description | Défaut |
|-----------|-------------|--------|
| `MULTISMTP_SMTP_ENABLED` | Active la configuration SMTP par utilisateur | `0` |
| `MULTISMTP_IMAP_ENABLED` | Active la configuration IMAP par utilisateur | `0` |
| `MULTISMTP_ALLOW_CHANGESERVER` | Autorise les utilisateurs à configurer un serveur SMTP différent du serveur principal et les paramètres OAuth2 | `0` |
| `MULTISMTP_SENT_ONLY_FROM_CARD` | N'applique MultiSMTP que pour les envois depuis des formulaires email (pas les envois automatisés) | `0` |
| `MULTISMTP_IMAP_NOVALIDATECERT` | Désactive la validation du certificat SSL (pour les certificats auto-signés) | `0` |
| `MULTISMTP_REPLACE_MAIL_EMAIL_FROM` | Remplace l'adresse d'expédition (`MAIN_MAIL_EMAIL_FROM`) par l'identifiant SMTP de l'utilisateur | `0` |
| `MAIN_IMAP_USE_PHPIMAP` | Utilise la bibliothèque Webklex PHPIMAP au lieu de l'extension PHP IMAP native (requis pour OAuth2 IMAP) | `0` |

### Constantes numériques

| Constante | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `MULTISMTP_CRON_REFRESH_TOKEN_DAYS` | int | `30` | Nombre de jours après expiration pour rafraîchir les tokens OAuth2 via la tâche cron (minimum 1) |

### Constantes de configuration IMAP partagé

| Constante | Type | Description |
|-----------|------|-------------|
| `MULTISMTP_IMAP_CONF_SERVER` | chaine | Serveur IMAP commun à tous les utilisateurs (si renseigné, les utilisateurs ne peuvent pas modifier le serveur, le port ni le TLS) |
| `MULTISMTP_IMAP_CONF_PORT` | chaine | Port IMAP commun |
| `MULTISMTP_IMAP_CONF_TLS` | int | Activer SSL/TLS sur le serveur IMAP commun |
| `MULTISMTP_IMAP_CONF_AUTH_TYPE` | chaine | Méthode d'authentification IMAP forcée (`LOGIN` ou `XOAUTH2`) |
| `MULTISMTP_IMAP_CONF_OAUTH_SERVICE` | chaine | Service OAuth2 IMAP forcé pour tous les utilisateurs (si renseigné, les paramètres OAuth2 individuels sont ignorés) |

### Constante système requise

| Constante | Type | Valeur | Description |
|-----------|------|--------|-------------|
| `MAIN_ACTIVATE_UPDATESESSIONTRIGGER` | chaine | `1` | **Obligatoire** — Active le trigger `USER_UPDATE_SESSION` dans Dolibarr, nécessaire au fonctionnement du module. Créée automatiquement à l'activation du module. |

---

## Schéma de base de données

### Table `llx_user2smtp`

| Colonne | Type | Description |
|---------|------|-------------|
| `fk_user` | INT (PK, FK -> `llx_user.rowid`) | Identifiant de l'utilisateur |
| `smtp_server` | VARCHAR(255) | Serveur SMTP |
| `smtp_port` | INT | Port SMTP |
| `smtp_tls` | INT | TLS activé (0/1) |
| `smtp_starttls` | INT | STARTTLS activé (0/1) |
| `smtp_id` | VARCHAR(255) | Identifiant SMTP |
| `smtp_auth_type` | VARCHAR(255) | Type d'authentification (`LOGIN` ou `XOAUTH2`) |
| `smtp_pw` | VARCHAR(255) | Mot de passe SMTP |
| `smtp_oauth_service` | VARCHAR(255) | Service OAuth2 pré-enregistré dans Dolibarr |
| `smtp_oauth_provider` | VARCHAR(255) | Fournisseur OAuth2 personnalisé (ex: `OAUTH_GOOGLE`) |
| `smtp_oauth_id` | VARCHAR(255) | Client ID OAuth2 personnalisé |
| `smtp_oauth_secret` | VARCHAR(255) | Client Secret OAuth2 personnalisé |
| `smtp_oauth_url_authorize` | VARCHAR(255) | URL d'autorisation OAuth2 personnalisée |
| `smtp_oauth_scope` | TEXT | Scopes OAuth2 personnalisés |
| `smtp_oauth_tenant` | VARCHAR(255) | Tenant Azure/Microsoft OAuth2 personnalisé |
| `imap_server` | VARCHAR(255) | Serveur IMAP |
| `imap_port` | INT | Port IMAP |
| `imap_tls` | INT | TLS activé (0/1) |
| `imap_id` | VARCHAR(255) | Identifiant IMAP |
| `imap_auth_type` | VARCHAR(255) | Type d'authentification (`LOGIN` ou `XOAUTH2`) |
| `imap_pw` | VARCHAR(255) | Mot de passe IMAP |
| `imap_oauth_service` | VARCHAR(255) | Service OAuth2 pré-enregistré dans Dolibarr |
| `imap_oauth_provider` | VARCHAR(255) | Fournisseur OAuth2 personnalisé |
| `imap_oauth_id` | VARCHAR(255) | Client ID OAuth2 personnalisé |
| `imap_oauth_secret` | VARCHAR(255) | Client Secret OAuth2 personnalisé |
| `imap_oauth_url_authorize` | VARCHAR(255) | URL d'autorisation OAuth2 personnalisée |
| `imap_oauth_scope` | TEXT | Scopes OAuth2 personnalisés |
| `imap_oauth_tenant` | VARCHAR(255) | Tenant Azure/Microsoft OAuth2 personnalisé |
| `imap_folder` | VARCHAR(255) | Dossier IMAP pour les emails envoyés |

**Contraintes** : `UNIQUE (fk_user)`, `FOREIGN KEY (fk_user) REFERENCES llx_user (rowid)`

---

## Compatibilité

- **PHP** : 7.1 à 8.4
- **Dolibarr** : 18 à 22+
- **OAuth2 SMTP** : Dolibarr 18+
- **OAuth2 IMAP** : Dolibarr 22+ ou Easya 2024+, avec `MAIN_IMAP_USE_PHPIMAP` activé
- **Fournisseur OAuth2 générique** : `MAIN_FEATURES_LEVEL >= 2`
- **Extension PHP imap** : requise pour les fonctionnalités IMAP (sauf si `MAIN_IMAP_USE_PHPIMAP` est activé)
- **Extension PHP openssl** : requise pour les connexions TLS/STARTTLS
- **Mode d'envoi Dolibarr** : doit être configuré sur `SMTP/SMTPS socket library` (pas sur la fonction `mail()` de PHP)