# CLAUDE.md — Contexte module dbadmin

## Aperçu (Overview)

`dbadmin` est un module externe Dolibarr **tiers** (éditeur ksar) qui embarque l'outil
d'administration de base de données **Adminer** dans l'interface Dolibarr :

- gestion complète de la base de l'instance (tables, données, structure, export/import, requêtes SQL),
- intégration en iframe dans le menu Outils d'administration,
- connexion automatique avec les identifiants de la base de l'instance (aucune saisie),
- ensemble de plugins Adminer (filtre de tables, structures détaillées, exports zip/bz2, thème sombre…).

Informations module (issues du code et du changelog local) :

- Éditeur : ksar — <https://github.com/ksar-ksar/Dolibarr_DbAdmin>
- Numéro module : `207301`
- Licence : GPL v3+
- Compatibilité Dolibarr : `9.0+`
- Compatibilité PHP : `5.5+` (Adminer 6.0.0 vérifié compatible PHP 7.4 → 8.4)
- Version locale : `2.3 - InfraS` (descripteur) — ⚠️ `version.txt` indique `1.1` : c'est la version du module upstream ksar dont ce module est un **fork InfraS** (voir Adaptations InfraS) ; seul le descripteur porte la version du fork
- Adminer embarqué : **6.0.0** (mis à jour depuis 5.4.2 le 2026-08-13)
- Dépendance obligatoire : aucune
- Emplacement : `htdocs/custom/dbadmin/`

**⚠️ Module tiers (ne commence pas par `infras`)** : toute modification d'un fichier de ce module
doit porter les tags `// InfraS add` / `// InfraS change` (ligne ou bloc begin/end), conformément
aux règles de l'instance. Exception de fait : les remplacements en bloc de fichiers *vendorés*
upstream (`adminer/adminer.php`, plugins repris tels quels) ne sont pas taguables — ils sont
documentés ici et dans les commentaires d'en-tête le cas échéant.

Convention de lecture :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes)

## Structure (Summary)

```text
htdocs/custom/dbadmin/
├── CLAUDE.md
├── COPYING / LICENSE
├── ChangeLog.md                     # Changelog upstream (format Markdown, pas docs/changelog.xml InfraS)
├── README.md
├── version.txt
├── admin/
│   └── about.php
├── adminer/
│   ├── adminer.php                  # Adminer officiel compilé (6.0.0, bit-identique à la release GitHub)
│   ├── adminer-dark.css             # Fichier vide : sa seule présence active le mode sombre natif d'Adminer
│   └── adminer-plugins/             # Plugins chargés par glob() depuis iframe.php (voir tableau ci-dessous)
├── core/
│   └── modules/
│       └── modDbadmin.class.php     # Descripteur module
├── img/
├── includes/
│   └── jquery-ui/                   # jQuery + jQuery UI + datepicker/timepicker + langs fr/en (pour edit-calendar)
├── index.php                        # Page Dolibarr (llxHeader) contenant l'<iframe src="iframe.php">
├── iframe.php                       # Wrapper : env Dolibarr allégé + auto-login + include adminer.php
├── langs/
│   ├── en_US/dbadmin.lang
│   └── fr_FR/dbadmin.lang
└── lib/
    └── dbadmin.lib.php
```

## Descripteur module (Module descriptor : `modDbadmin`)

Dans `core/modules/modDbadmin.class.php` :

- **Module parts** : aucun hook, aucun trigger, aucune CSS globale — le module est autoporté par ses 2 pages
- **Dépendances** : aucune
- **Dictionnaires / Boxes / Cron** : aucun
- **Permissions** : 1 permission
	- `access` (id `2073011`) — « Access to the Database Admin tool », contrôlée dans `index.php` et `iframe.php`
- **Menus** : 1 entrée de menu gauche « DbAdmin » sous Outils d'administration
	(`/dbadmin/index.php?mainmenu=home&leftmenu=admintools`)
- **Famille** : `Dolibarr LTS by InfraS` si le module dolinfras est actif (constante `DOLINFRAS_FAMILY`), `base` sinon
- **Version distante** : `url_last_version` pointe vers le `version.txt` du dépôt GitHub upstream

## Fonctionnement principal (Core behavior)

1. `index.php` : page Dolibarr classique (auth complète, `llxHeader`, vérification
   `$user->rights->dbadmin->access`) qui n'affiche qu'une `<iframe src="iframe.php">` (hauteur 90vh).
2. `iframe.php` : recharge l'environnement Dolibarr en mode allégé
   (`NOCSRFCHECK`, `NOREQUIREMENU`, `NOREQUIREHTML`, `NOTOKENRENEWAL`, etc.), revérifie la
   permission `access`, prépare l'auto-login (voir Notes techniques), définit `adminer_object()`
   (chargement des plugins) puis `include "./adminer/adminer.php"`.
3. Adminer se connecte à la base avec les identifiants `$dolibarr_main_db_*` du `conf.php`
   de l'instance : **seule la base de l'instance est visible/administrable** (limité par les
   privilèges MySQL du compte de l'instance).

## Plugins Adminer (adminer-plugins/)

Chargés par `glob("adminer/adminer-plugins/*.php")` (tous inclus), puis instanciés
explicitement dans la liste `new Adminer\Plugins(array(...))` d'`iframe.php`.

| Fichier | Origine | Rôle | Écarts locaux vs upstream |
|---------|---------|------|---------------------------|
| `AdminierDoliLogin.php` | module (ksar) | Auto-login avec les identifiants Dolibarr (`credentials()` + `login()` retourne true) | traduction fr |
| `AdminerCollations.php` | Pematon (adapté) | Réduit les listes déroulantes de collation à une liste courte | instancié avec `array("utf8mb4_unicode_ci", "utf8mb4_general_ci", "ascii_general_ci")` (`// InfraS change`, standard serveur en tête) |
| `tables-filter.php` | upstream 6.0.0 | Champ de filtre au-dessus de la liste des tables | + traduction fr |
| `table-structure.php` | upstream 6.0.0 | Structure détaillée des tables (colonnes/collation/null/défaut) | + traduction fr |
| `table-indexes-structure.php` | upstream 6.0.0 | Structure détaillée des index | + traduction fr |
| `backward-keys.php` | upstream 6.0.0 | Liens inverses entre tables (clés étrangères entrantes) | + traduction fr |
| `dump-date.php` | upstream 6.0.0 | Date/heure dans le nom des fichiers d'export | + traduction fr |
| `dump-zip.php` | upstream 6.0.0 | Export ZIP | + traduction fr |
| `dump-bz2.php` | upstream 5.4.2 + gardes | Export Bzip2 | gardes `function_exists('bzwrite'/'bzclose')` locales + traductions fr/hr |
| `edit-foreign.php` | upstream 6.0.0 | Selects de clés étrangères dans le formulaire d'édition (limite 1000) | + traduction fr |
| `foreign-system.php` | upstream 6.0.0 | Liens FK virtuels entre tables système mysql/information_schema | + traduction fr |
| `dark-switcher.php` | upstream 6.0.0 | Bascule manuelle clair/sombre (cookie `adminer_dark`) | + traduction fr |
| `config.php` | upstream 6.0.0 | Écran de configuration utilisateur (stockage cookie) | + traduction fr (dont clé `Save`) |
| `menu-links.php` | upstream 6.0.0 | Liens données/structure dans le menu des tables (dépend d'AdminerConfig) | + traduction fr (dont clé `Both, select on hover`) |
| `frames.php` | upstream 6.0.0 | Autorise l'exécution en iframe (retire `X-Frame-Options`) | + traduction fr |
| `edit-calendar.php` | upstream 5.4.2 (retiré en 6.0.0) | Datepicker/timepicker jQuery UI sur les champs date | chemins adaptés vers `includes/jquery-ui/` + support timepicker + langue fr — **conservé** : API (`head`/`editInput`) toujours compatible 6.0.0 |
| `dump-php.php` | upstream 5.4.2 (retiré en 6.0.0) | Export au format PHP | **présent sur disque mais NON chargé** (`// InfraS change` dans iframe.php) : la signature `dumpData()` a changé en 6.0.0 (reçoit les composants du SELECT, plus une requête SQL) |

## Adaptations InfraS (InfraS changes)

Le module local est un **fork InfraS** du dépôt ksar (`Dolibarr_DbAdmin`, version `1.1`,
resté sur **Adminer 4.x**). Liste exhaustive des divergences par rapport à l'upstream,
établie par diff complet le 2026-08-13 :

### Refonte de l'intégration Adminer 4.x → 5/6 (non taguable — remplacement en bloc)

- `adminer/adminer.php` : Adminer 4.x upstream remplacé par l'officiel **6.0.0**
  (+ 1 patch tagué, voir ci-dessous).
- Chargeur de plugins d'`iframe.php` réécrit pour l'API Adminer 5/6 :
  `adminer/plugins/plugin.php` + classe `AdminerPlugin` (Adminer 4) →
  `glob("adminer/adminer-plugins/*.php")` + `new Adminer\Plugins(array(...))`.
- Répertoire `adminer/plugins/` (9 fichiers upstream) remplacé par `adminer/adminer-plugins/`
  (17 fichiers) : plugins upstream retirés (`plugin.php`, `AdminerSimpleMenu`,
  `AdminerTheme('default-blue')`), jeu étendu (TableStructure, TableIndexesStructure,
  BackwardKeys, DumpBz2, EditCalendar, EditForeign, ForeignSystem, DarkSwitcher, Config,
  MenuLinks), traductions **fr** ajoutées partout, gardes bz2, `edit-calendar` adapté
  (voir tableau Plugins).
- `AdminierDoliLogin.php` réécrit pour Adminer 5/6 (upstream : classe globale sans héritage ;
  local : `extends Adminer\Plugin`, gestion du port dans `credentials()`, traduction fr).
- Thème : `AdminerTheme` remplacé par le mode sombre natif (`adminer-dark.css` vide, nouveau)
  + plugin `dark-switcher`.
- `includes/jquery-ui/` : répertoire **ajouté** (absent upstream) — jQuery + jQuery UI +
  datepicker/timepicker + langues fr/en pour `edit-calendar`.
- `Frames(true)` (même origine uniquement) upstream → `Frames()` (retrait X-Frame-Options,
  délégué au vhost).

### Modifications taguées dans les fichiers conservés

`iframe.php` :

1. `session_cache_limiter('');` (`// InfraS add`) — évite le warning « Session cache limiter
   cannot be sent after headers have already been sent » déclenché par le `session_start()`
   interne d'Adminer (`restart_session()`).
2. Test de session avant injection de l'auto-login (`// InfraS change`) :
   `if (($_SESSION["db"]["server"][""][""][""] ?? null) != true)` — ajout du `?? null`
   (le test existe upstream sans garde) : supprime le warning « undefined index » au premier
   chargement.
3. Liste de collations passée à `AdminerCollations` (`// InfraS change`) — aligne les
   propositions sur le standard du serveur (`utf8mb4_unicode_ci`).
4. Retrait de `new AdminerDumpPhp()` de la liste des plugins (`// InfraS change`) — plugin
   supprimé upstream en Adminer 6.0.0, incompatible avec la nouvelle API `dumpData()`.

`adminer/adminer.php` (fichier compilé, tag `/* InfraS change */` inline) :

5. `@` rétabli dans le wrapper `Adminer\ini_set()` — supprime le warning
   `Session ini settings cannot be changed when a session is active` (session Dolibarr
   déjà active à l'include ; la 5.4.2 portait ce `@` sur chaque appel, la 6.0.0 l'a retiré).

### Modifications non taguées du descripteur (`modDbadmin.class.php`)

Antérieures à l'introduction des tags sur ce module — à taguer à l'occasion d'une
prochaine édition :

- famille dynamique : `$this->family = 'base'` upstream → `DOLINFRAS_FAMILY` si le module
  dolinfras est actif, + `familyinfo` et `module_position = 100017` (upstream : `'90'`) ;
- `$this->version = '1.1'` upstream → `'2.3 - InfraS'` (version du fork) ;
- entrée de menu : `'leftmenu' => ''` upstream → `'admintools'` (rattachement au menu
  Outils d'administration).

### Fichiers strictement identiques à l'upstream ksar

`index.php`, `lib/dbadmin.lib.php`, `admin/about.php`, `langs/fr_FR/dbadmin.lang`,
`langs/en_US/dbadmin.lang`, `version.txt` (vérifié par md5 le 2026-08-13).

## Conventions de développement (Development conventions)

- **Tags obligatoires** : module tiers → `// InfraS add` / `// InfraS change` sur toute
  modification de fichier (voir la règle globale de l'instance).
- **Fichiers vendorés** : `adminer/adminer.php` doit rester au plus près de la release
  officielle (vérifiable par md5 contre
  `https://github.com/vrana/adminer/releases/download/v<X.Y.Z>/adminer-<X.Y.Z>.php`) ;
  toute adaptation passe par `iframe.php` ou par un plugin. **Exception unique en place**
  (tagué `/* InfraS change */` dans le fichier compilé) : `@` rétabli dans le wrapper
  `Adminer\ini_set()` — la 6.0.0 a retiré la suppression d'erreur que portait chaque appel
  en 5.4.2, or `ini_set("session.use_trans_sid")` s'exécute alors que la session Dolibarr
  est déjà active sous l'iframe dbadmin → warning `Session ini settings cannot be changed`
  à chaque page (constaté le 2026-08-13, non corrigé upstream). À reporter (ou réévaluer)
  à chaque mise à jour d'Adminer — le contrôle md5 de l'étape 2 du workflow le détectera.
- **PHP cible** : le code doit rester compatible PHP 7.4 (des instances de la flotte
  tournent encore en 7.4) — linter avec `php7.4 -l` avant livraison.
- Compatibilité multi-instances : le module est déployé sur ~45 instances du serveur ; toute
  évolution locale doit être pensée pour être répliquée à l'identique.

## Workflow — mise à jour d'Adminer (Adminer update procedure)

Procédure vérifiée lors du passage 5.4.2 → 6.0.0 (2026-08-13) :

1. **Sauvegarde** : `tar` du répertoire `adminer/` + `iframe.php` vers
   `/var/backups/dbadmin/<instance>-dbadmin-adminer-<ancienne-version>.bak.<AAAAMMJJ>.tar.gz`.
2. **Noyau** : télécharger la release officielle GitHub (fichier toutes-langues, sans suffixe
   `-en`), vérifier le md5 du fichier en place contre l'ancienne release officielle (détecte un
   patch local oublié), `php7.4 -l`, puis écraser **par `cat >`** (préserve inode/propriétaire/ACL).
3. **Plugins** : pour chaque plugin d'origine upstream, partir de la version upstream de la
   release cible et **réappliquer les écarts locaux** (tableau ci-dessus — essentiellement les
   blocs de traduction `'fr' => array(...)` dans `protected $translations`).
4. **Invariants à revérifier dans le nouveau noyau** (le fichier compilé remplace les espaces
   par des sauts de ligne → normaliser avec `tr '\n' ' '` avant grep) :
   - classes `Adminer\Plugin` / `Adminer\Plugins` et fonctions utilisées par les plugins
     (`nonce`, `lang`, `h`, `script`, `script_src`, `js_escape`, `redirect`, `page_header`…) ;
   - hooks appelés (`credentials`, `login`, `head`, `headers`, `tablesPrint`,
     `tableStructurePrint`, `editInput`, `dumpFormat`…) ;
   - structure de session `$_SESSION["db"][driver][server][username][db]` et
     `$_SESSION["token"]` (utilisées par l'auto-login d'iframe.php) ;
   - chemin d'auth : le traitement de `$_POST["auth"]` doit toujours se terminer par
     `redirect()` quand `count($_POST) == 1` **avant** tout `verify_token()`
     (sinon l'auto-login forgé casse — voir Notes techniques).
5. **Banc d'essai CLI** (hors instance) : harness qui définit `adminer_object()` avec tous les
   plugins puis `include adminer.php` sous `php7.4` — (a) sans `$_POST` : doit rendre la page
   de login sans erreur ; (b) avec `$_POST['auth']` seul (forgé comme iframe.php) : doit sortir
   sans rien afficher (chemin redirect) et sans page d'erreur token.
6. **Après bascule** : lint en place, contrôle propriétaire/permissions, test réel via l'interface.

## Points d'attention (Watchpoints)

- `iframe.php` définit `NOCSRFCHECK` : la protection CSRF est celle d'Adminer (token de session
  + contrôle `Sec-Fetch-Site` depuis 5.4.3), pas celle de Dolibarr.
- L'auto-login expose la base **complète** de l'instance à tout utilisateur Dolibarr disposant
  de la permission `dbadmin->access` : n'accorder cette permission qu'aux administrateurs.
- Le plugin `frames.php` retire `X-Frame-Options` envoyé par Adminer ; la protection
  clickjacking reste assurée par l'en-tête `X-Frame-Options SAMEORIGIN` posé par le vhost Apache.
- **Cache navigateur (incident 2026-08-13)** : la sous-ressource `script=db` d'Adminer
  (remplit collations/tailles/lignes de la liste des tables) est servie en `text/javascript`
  sans en-têtes de cache propres. Avant le correctif flotte des vhosts (exclusion
  `<FilesMatch "\.php$"> ExpiresActive off`), `mod_expires` la faisait cacher 1 mois par le
  navigateur → affichage figé (ex. collations utf8mb3 après migration utf8mb4). Ne pas
  réintroduire d'`ExpiresDefault` s'appliquant aux réponses PHP — voir
  `/etc/apache2/sites-available/CLAUDE.md`, section « Politique de cache (mod_expires) ».
- `adminer-dark.css` est volontairement **vide** : sa présence suffit à activer le support du
  mode sombre ; ne pas le supprimer (le `dark-switcher` cible `link[href*="dark.css"]`).
- Le changelog du module est `ChangeLog.md` (format upstream) — pas de `docs/changelog.xml`
  InfraS : les règles InfraS de changelog XML ne s'appliquent pas ici.
- `langs/fr_FR/` contient un reliquat `dbadmin.lang.back` (sauvegarde upstream, non chargé).

## Notes techniques (Technical notes)

### Mécanisme d'auto-login (AdminierDoliLogin + iframe.php)

```
L'utilisateur ouvre index.php (auth Dolibarr + permission dbadmin->access)
    ↓
iframe.php (env Dolibarr allégé, permission revérifiée)
    ↓
$_SESSION["token"] initialisé si absent (numérique, requis par Adminer)
    ↓
Si la session Adminer n'existe pas ($_SESSION["db"]["server"][""][""][""] != true) :
    → forge $_POST["auth"] = [driver=server, server='', username='', password='', db='']
      (seule clé du POST — la requête HTTP reste un GET)
    ↓
adminer.php : traitement de $_POST["auth"]
    → AdminerDoliLogin::credentials() fournit host:port/user/password depuis
      $dolibarr_main_db_* (globales du conf.php chargées par main.inc.php)
    → AdminerDoliLogin::login() retourne true (bypass de la validation par défaut)
    → count($_POST) == 1 → redirect(auth_url(...)) → exit
      ⚠️ ce redirect sort AVANT le contrôle if ($_POST) { verify_token() } :
      c'est ce qui rend l'auto-login sans token possible — invariant à revérifier
      à chaque montée de version d'Adminer
    ↓
Requêtes suivantes : session Adminer ouverte, plus aucune injection
```

Le contrôle `Sec-Fetch-Site` de `verify_token()` (Adminer ≥ 5.4.3) accepte `""` (en-tête
absent) et `same-origin` : les formulaires soumis depuis l'iframe même-origine passent.

### Fichier compilé Adminer

`adminer/adminer.php` est la version compilée mono-fichier : identifiants de variables
minifiés, espaces remplacés par des sauts de ligne (grep direct inefficace — aplatir avec
`tr '\n' ' '`), traductions compressées (deflate depuis 5.5.1). Les assets internes sont
servis par le fichier lui-même via `?file=...&version=<X.Y.Z>`.

### Historique local

- **2026-08-13** : Adminer 5.4.2 → **6.0.0** (nombreux correctifs de sécurité upstream :
  XSS structure de table, contrôle Sec-Fetch-Site, GHSA SQLite/proxy/login) ; plugins
  upstream rafraîchis en version 6.0.0 avec réapplication des traductions fr ; `dump-php`
  déchargé ; liste `AdminerCollations` alignée sur `utf8mb4_unicode_ci`.
  Sauvegarde : `/var/backups/dbadmin/tpi-dbadmin-adminer-5.4.2.bak.20260813.tar.gz`.
  Au moment de cette mise à jour, le reste de la flotte est en 5.4.2 (43 instances,
  fichiers bit-identiques) et 5.4.1 (2 instances).
- **2026-08-13 (bis)** : patch tagué du wrapper `Adminer\ini_set()` dans `adminer.php`
  (`@` rétabli) — supprime le warning `ini_set(): Session ini settings cannot be changed
  when a session is active` émis à chaque page dbadmin depuis le passage en 6.0.0
  (voir Conventions, « Fichiers vendorés »). Validé sur banc d'essai CLI PHP 7.4 et 8.4.
