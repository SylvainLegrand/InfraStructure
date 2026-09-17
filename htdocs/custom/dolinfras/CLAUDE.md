# CLAUDE.md — Contexte module dolinfras

## Aperçu (Overview)

`dolinfras` est un module externe Dolibarr de branding et suivi de version pour les instances « Dolibarr LTS by InfraS » :

- branding dynamique de la famille module (« Dolibarr LTS by InfraS »),
- lecture et stockage de la version Dolibarr dans une constante dédiée,
- avertissement à la connexion si la version Dolibarr dépasse la version max supportée,
- affichage du changelog avec détection de mises à jour dans l'onglet aide du descripteur,
- détection automatique du thème sombre (dark mode) pour le branding sur la page modules,
- chargement de constantes LTS à l'activation (deux groupes : SaaS by InfraS et options fonctionnelles Dolibarr),
- pages d'administration (paramètres, à propos, changelog) avec application forcée des constantes LTS de `data.sql`.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500100`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.3.0` (2026-09)
- Emplacement : `htdocs/custom/dolinfras/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/dolinfras/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── dolinfrassetup.php
├── class/
│   └── actions_dolinfras.class.php
├── config.php
├── core/
│   ├── lib/
│   │   └── dolinfrasAdmin.lib.php
│   └── modules/
│       └── moddolinfras.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── dolinfras.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
│   ├── Dolibarr_preferred_partner.png
│   ├── Dolibarr_preferred_partner_small.png
│   ├── InfraS.png
│   ├── InfraSheader.png
│   ├── Tools.png
│   ├── corp.png
│   ├── dolistore_logo.png
│   ├── gplv3.png
│   ├── list.png
│   └── option_tool.png
├── js/
│   └── dolinfras.js
├── langs/
│   ├── en_US/dolinfras.lang
│   ├── es_ES/dolinfras.lang
│   └── fr_FR/dolinfras.lang
└── sql/
    └── data.sql                          # Constantes LTS injectées à l'activation (INSERT IGNORE)
```

## Descripteur module (Module descriptor : `moddolinfras`)

Dans `core/modules/moddolinfras.class.php` :

- **Module parts** :
	- hooks : `login`
	- JS : `/dolinfras/js/dolinfras.js`
	- CSS : `/dolinfras/css/dolinfras.css.php`
- **Dépendances** : aucune dépendance obligatoire
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : aucune (tableau vide) — les pages admin sont réservées aux utilisateurs `admin`
- **Menus** : aucun (tableau vide)
- **Page de configuration** : `config_page_url = array('dolinfrassetup.php@dolinfras')`
- **Famille** : `Dolibarr LTS by InfraS` (branding dynamique avec polices puentebold et NeuropolRegular)

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/dolinfras/sql/')`) — injecte les constantes LTS via `data.sql`
2. Initialisation standard (`_init()`)

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue retrait standard (`_remove()`). Les constantes LTS injectées par `data.sql` ne sont pas supprimées (comportement `INSERT IGNORE` / pas de `remove` SQL dédié).

### Gestion de version (Lifecycle : `getLocalVersion()`)

Lecture depuis `docs/changelog.xml` via `dolinfras_getLocalVersionMinDoli()` :

- renseigne `need_dolibarr_version`, `phpmin`, `phpmax`,
- désactive automatiquement le module si la version Dolibarr est inférieure au minimum requis.

### Changelog dans l'aide (Lifecycle : `getChangeLog()`)

`getChangeLog()` appelle `dolinfras_getChangeLog()` pour afficher le changelog complet avec bannière de support et détection de mises à jour dans l'onglet « ? » du descripteur sur la page modules.

## Fonctionnement principal (Core behavior)

Le module assure le branding, le suivi de version et la pré-configuration des instances Dolibarr LTS par InfraS :

- `actions_dolinfras.class.php` : hook `afterLogin` qui vérifie la version max supportée et stocke la version Dolibarr,
- `dolinfrasAdmin.lib.php` : fonctions utilitaires (vérification PHP XML, lecture changelog, affichage changelog avec support, téléchargement de mises à jour, onglets admin, application forcée des constantes LTS),
- `sql/data.sql` : constantes LTS chargées une seule fois à l'activation via `_load_tables`,
- `admin/` : pages d'administration du module (paramètres, à propos, changelog).

## Hooks et comportement (Hook behavior)

La classe `Actionsdolinfras` intervient sur le contexte `login` :

- `afterLogin` : appelle `dolinfras_getVersionDolinfras()` pour stocker la version Dolibarr dans `DOLINFRAS_VERSION`, puis vérifie si la version Dolibarr dépasse la version max supportée (`DOLINFRAS_DISABLE_CHECK_VERSION_MAX`) et affiche un avertissement le cas échéant.

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. La configuration est stockée dans `llx_const` via :

- `dolibarr_set_const()` (constantes runtime : `DOLINFRAS_VERSION`, `DOLINFRAS_FAMILY`, `INFRAS_PHP_EXT_XML`)
- `sql/data.sql` (constantes LTS injectées à l'activation — deux groupes)

### Groupes de constantes SQL (`data.sql`)

**Groupe 1 — Constantes SaaS by InfraS** (`visible=0`) : paramètres propres à la distribution LTS, non exposés dans l'interface admin standard. Exemples :

| Constante | Valeur | Description |
|-----------|--------|-------------|
| `MAIN_BUGTRACK_ENABLELINK` | `https://support.infras.fr/create_ticket.php` | Lien de signalement de bug |
| `MAIN_FILECHECK_LOCAL_SUFFIX` | `-saasbyinfras` | Suffixe de vérification d'intégrité |
| `INFRASPACKPLUS_DISABLED_CORE_CHANGE` | `1` | Verrouille les changements core InfraSPackPlus |
| `INFRASPACKPLUS_DISABLED_MODULE_CHANGE` | `1` | Verrouille les changements module InfraSPackPlus |
| `MAILING_NO_USING_PHPMAIL` | `1` | Force SMTP, interdit le mail PHP natif |
| `MAIN_MAIL_ADD_INLINE_IMAGES_IF_DATA` | `1` | Convertit les images base 64 du corps des mails en fichiers joints embarqués (cid) |
| `MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS` | `1` | Convertit les images des médias dans les mails en fichiers joints embarqués (cid) |
| `MAIN_MOTD` | HTML | Message de bienvenue personnalisé avec `__USER_FIRSTNAME__` |
| `MAIN_HTML_FOOTER` | JS | Place le bouton d'import de lignes (`objectlinked_importbtn`) avant le libellé du type dans le bloc des objets liés |
| `MAIN_SECURITY_DISABLEFORGETPASSLINK` | `1` | Masque le lien « Mot de passe oublié » |
| `DATABASE_PWD_ENCRYPTED` | `1` | Mot de passe base chiffré |
| `FCKEDITOR_SKIN` | `infras` | Skin WYSIWYG InfraS |
| `USER_PASSWORD_PATTERN` | `16;1;1;1;2;1` | Politique de mot de passe LTS |

**Groupe 2 — Options fonctionnelles Dolibarr** (`visible=1`) : options Dolibarr standards activées pour toutes les instances LTS. Exemples :

| Constante | Description |
|-----------|-------------|
| `MAIN_USE_ADVANCED_PERMS` | Permissions avancées |
| `MAIN_MENU_HIDE_UNAUTHORIZED` | Masque les menus non autorisés |
| `FACTURE_REUSE_NOTES_ON_CREATE_FROM` | Reprend les notes lors de la création depuis un autre objet |
| `INVOICE_ALLOW_EXTERNAL_DOWNLOAD` | Lien de téléchargement externe automatique pour les factures |
| `SOCIETE_ASK_FOR_SHIPPING_METHOD` | Mode d'expédition prédéfini sur la fiche client |
| `MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN` | Propagation des contacts lors de la création depuis un autre objet |
| `USER_HIDE_INACTIVE_IN_COMBOBOX` | Masque les utilisateurs inactifs dans les listes |
| `MAIN_EXTRAFIELDS_ENABLE_NEW_SELECT2` | AJAX select2 pour les sellist sans limite |

Toutes ces constantes sont insérées avec `INSERT IGNORE` : elles ne sont posées qu'une seule fois à l'activation et ne sont jamais écrasées si l'admin les a modifiées manuellement. Exception : le bouton « Forcer l'application des paramètres » de `admin/dolinfrassetup.php` (fonction `dolinfras_force_lts_constants()`) réapplique la valeur de toutes les constantes de `data.sql` dans l'entité courante, en **écrasant** les valeurs modifiées en base.

## Pages d'administration (Admin pages)

Trois pages dans `admin/`, réservées aux utilisateurs `admin` (le module n'a pas de permissions propres), avec onglets communs générés par `dolinfras_admin_prepare_head()` :

| Page | Onglet | Rôle |
|------|--------|------|
| `admin/dolinfrassetup.php` | `dolinfrassetup` | Page de paramètres. Trois sections : « Options de gestion des paramètres LTS » (bouton « Forcer l'application des paramètres », action `forceLTSParams`, qui force la valeur des constantes de `sql/data.sql` dans la base via `dolinfras_force_lts_constants()`), « Paramètres SaaS by InfraS » (constantes `visible = 0`) et « Options fonctionnelles Dolibarr » (constantes `visible = 1`). Les deux dernières sections sont **générées dynamiquement** à partir de `sql/data.sql` : une option par constante (bouton On / Off, champ de saisie, liste déroulante selon le type retourné par `dolinfras_get_lts_param_type()`). Actions `set_<CONSTANTE>` / `del_<CONSTANTE>` pour les bascules (écriture immédiate, colonnes `visible` et `note` de `data.sql` conservées) et `update_saas` / `update_options` pour l'enregistrement des champs saisis |
| `admin/about.php` | `about` | Affiche `README.md` en HTML via `dolMd2Html()` |
| `admin/changelog.php` | `changelog` | Affiche le changelog complet (`dolinfras_getChangeLog()`) avec bouton de téléchargement du dernier changelog (action `dwnChangelog`) |

## Fonctions utilitaires (Library functions)

### `dolinfrasAdmin.lib.php`

Fichier unique de bibliothèque contenant toutes les fonctions du module :

| Fonction | Description |
|----------|-------------|
| `dolinfras_admin_prepare_head()` | Génère les onglets des pages d'administration (Paramètres, À propos, Changelog) |
| `dolinfras_get_lts_constants($appliname)` | Parse `sql/data.sql` par regex et retourne la liste ordonnée des constantes LTS (`nom => array('value', 'visible', 'note')`) ; la première déclaration d'une constante fait foi |
| `dolinfras_get_lts_param_type($confkey, $defaultvalue)` | Retourne le type d'option à afficher pour une constante : `on_off`, `lts_value`, `input`, `number`, `textarea`, `select_language` ou `select_featureslevel` (table d'exceptions par nom de constante, sinon déduit de la valeur par défaut : `0`/`1` => `on_off`, autre => `input`) |
| `dolinfras_lts_onoff_link($confkey, $valueon)` | Retourne le lien HTML de bascule On / Off d'une constante (sans ajax, pour conserver `visible` et `note` — le service ajax du core les écrase) |
| `dolinfras_force_lts_constants($appliname)` | S'appuie sur `dolinfras_get_lts_constants()` et force chaque constante via `dolibarr_set_const()` dans l'entité courante (transaction avec rollback en cas d'erreur) ; retourne le nombre de constantes appliquées ou -1 |
| `dolinfras_test_php_ext()` | Vérifie si l'extension PHP XML est chargée, stocke le résultat dans `INFRAS_PHP_EXT_XML` |
| `dolinfras_getLocalVersionMinDoli($appliname)` | Lit `docs/changelog.xml` et retourne un tableau [version, minDoli, errFlag, versionsArray, maxDoli, minPHP, maxPHP] |
| `dolinfras_getVersionDolinfras()` | Lit le fichier `VERSION` de Dolibarr et stocke sa valeur dans `DOLINFRAS_VERSION` et la famille dans `DOLINFRAS_FAMILY` |
| `dolinfras_getChangelogFile($appliname, $from)` | Charge et parse un fichier changelog XML (local ou téléchargé) via `simplexml_load_string()` avec `LIBXML_NONET` |
| `dolinfras_dwnChangelog($appliname)` | Télécharge le dernier changelog depuis le dépôt GitHub `InfraS-SARL/modules-versions` via `getURLContent()` et le stocke en local |
| `dolinfras_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn)` | Génère le HTML complet du changelog : bannière de support InfraS, tableau comparatif local/téléchargé, bouton de vérification |

## Constantes de configuration (Key settings)

Constantes runtime posées dynamiquement par le module :

| Constante | Type | Description |
|-----------|------|-------------|
| `INFRAS_PHP_EXT_XML` | int | État de l'extension PHP XML (1 = ok, -1 = absente) |
| `DOLINFRAS_VERSION` | string | Version de Dolibarr lue depuis le fichier `VERSION` |
| `DOLINFRAS_FAMILY` | string | HTML de la famille module avec branding InfraS |
| `DOLINFRAS_DISABLE_CHECK_VERSION_MIN` | bool | Désactive la vérification de version minimum Dolibarr |
| `DOLINFRAS_DISABLE_CHECK_VERSION_MAX` | bool | Désactive la vérification de version maximum Dolibarr |
| `INFRAS_SKIP_CHECKVERSION` | bool | Désactive la vérification de mise à jour en ligne (bouton téléchargement changelog) |

Les constantes LTS (`data.sql`) sont documentées dans la section *Données / SQL* ci-dessus.

## Traductions (Translations)

Trois répertoires de traduction : `en_US`, `es_ES`, `fr_FR`. Fichier unique `dolinfras.lang` par locale. Chargement :

```php
$langs->load('dolinfras@dolinfras');
```

Clés de traduction principales :

- `Module500100Name` / `Module500100Desc` — nom et description du module
- `DolInfraSCautionMess` / `InfraSXMLextError` — messages d'alerte extension PHP
- `DolInfraSChangelogXMLError` — erreur de parsing XML
- `DolInfraSWarningMaxVersion` — avertissement version Dolibarr trop récente
- `DolInfraSParam*` — 15 clés pour la bannière de support et le changelog (présentation InfraS, slogan, liens, historique des mises à jour, etc.)
- `DolInfraSSetupPages` / `DolInfraSParams` / `DolInfraSParamsChangelog` — titres des pages et onglets admin
- `DolInfraSTitleLTS` / `DolInfraSParamForceApply` / `DolInfraSForceApply*` — section « Options de gestion des paramètres LTS » et bouton d'application forcée
- `DolInfraSTitleSaaSParams` / `DolInfraSTitleDoliOptions` / `DolInfraSParamCautionSave` / `DolInfraSFeaturesLevel*` — titres et libellés des deux sections de paramétrage des constantes LTS
- `DolInfraSConst<NOM_DE_LA_CONSTANTE>` — libellé de l'option de chaque constante de `sql/data.sql` (88 clés) : toute constante ajoutée dans `data.sql` doit recevoir sa clé dans les trois fichiers de langue, sinon la clé brute s'affiche à l'écran. Chaque libellé se termine par un marqueur indiquant si la constante apparaît dans la page *Accueil > Configuration > Divers* (`admin/const.php`, qui ne liste que les constantes `visible = 1`) : `(visible)` / `(caché)` en français, `(visible)` / `(hidden)` en anglais, `(visible)` / `(oculto)` en espagnol. Ce marqueur suit la colonne `visible` déclarée dans `data.sql` : le mettre à jour dans les trois langues si le `visible` d'une constante change

## CSS (Styles)

`css/dolinfras.css.php` : feuille de style dynamique PHP chargée via `module_parts`. Contient :

- **Polices embarquées** : `puentebold` (branding Dolibarr), `NeuropolRegular` (branding InfraS)
- **Classes de branding** : `.dolinfrasneuropolinfras`, `.dolinfraspuentedolibarr`, `.dolinfrasCaution`
- **Classes de dimensionnement** : `.dolinfraswidth180`, `.dolinfraswidth220`, `.dolinfraswidth270`, `.dolinfrasheight25/32/50/75`, `.dolinfraswidthtrentepercent`, `.dolinfrasminwidth700imp`
- **Classes de changelog** : `.dolinfraschangelogbase`, `.dolinfraschangefix` (rouge), `.dolinfraschangeadd` (vert), `.dolinfraschangechg` (bleu), `.dolinfraschangedefault`, `.dolinfrasbgorange` (nouvelles versions disponibles), `.dolinfrasbggreen` (versions en avance)
- **Classes d'affichage** : `.dolinfrasnoborder`, `.dolinfrasnopaddingvert`, `.dolinfrasmargintop10imp`, `.dolinfrasslogan`, `.dolinfrascolor`, `.dolinfrastitleparam`
- **Dark mode** : surcharges via `.dolinfras-dark-bg` (classe ajoutée par `dolinfras.js`)

## JavaScript

`js/dolinfras.js` : détection du thème sombre Dolibarr sur la page `/admin/modules.php` uniquement :

- Lit la variable CSS `--colorbline` du thème Dolibarr
- Calcule la luminance ITU-R BT.601
- Ajoute la classe `dolinfras-dark-bg` sur `<html>` si la luminance est inférieure à 0.5
- Permet les surcharges CSS dark mode pour le branding dans la liste des modules

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification du descripteur / constantes / hooks :

1. Désactiver puis réactiver le module
2. Vérifier les constantes module (`DOLINFRAS_VERSION`, `DOLINFRAS_FAMILY`, `INFRAS_PHP_EXT_XML`)
3. Vérifier le branding sur la page des modules (`admin/modules.php`)
4. Vérifier l'avertissement de version max après connexion
5. Vérifier l'affichage du changelog dans l'onglet « ? » du descripteur

Si modification de `sql/data.sql` :

1. Désactiver puis réactiver le module pour rejouer le `_load_tables` (ou utiliser le bouton « Forcer l'application des paramètres » de `admin/dolinfrassetup.php` — attention : celui-ci écrase les valeurs modifiées en base)
2. Vérifier en base que les nouvelles constantes sont bien présentes (`SELECT name, value FROM llx_const WHERE note LIKE '%InfraS%'`)
3. Rappel : `INSERT IGNORE` — les constantes déjà existantes ne sont jamais écrasées

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`dolinfras_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis
- Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée
- La constante `DOLINFRAS_VERSION` est utilisée par d'autres modules InfraS (infrasdiscount, infraspackplus) pour le branding dynamique de leur famille
- Les constantes LTS (`data.sql`) sont injectées avec `INSERT IGNORE` : jamais réécrites si modifiées en base — sauf via le bouton « Forcer l'application des paramètres » qui, lui, écrase les valeurs
- `dolinfras_get_lts_constants()` (utilisée par le forçage **et** par la page de paramètres) parse `data.sql` par regex : toute nouvelle ligne du fichier doit respecter le format existant (`insert ignore into llx_const (name, entity, value, type, visible, note) values ('NOM', __ENTITY__, 'valeur', 'chaine', 0|1, 'note');`), sans quoi la constante n'apparaît ni dans la page de paramètres ni dans l'application forcée
- Une constante ne doit être déclarée qu'une seule fois dans `data.sql` : un doublon n'est plus affiché qu'une fois dans la page de paramètres (première déclaration retenue), mais reste une source de confusion
- `MAIN_HTML_FOOTER` contient du JavaScript : son option est un simple bouton On / Off qui applique ou retire la valeur de `data.sql` (type `lts_value`), la saisie libre étant volontairement interdite (filtrage `restricthtml` des entrées)

## Notes techniques (Technical notes)

### Branding dynamique (Dynamic branding)

Le module fournit un mécanisme de branding centralisé pour tous les modules InfraS :

1. **À la connexion** : `afterLogin()` appelle `dolinfras_getVersionDolinfras()` qui lit `htdocs/VERSION` et stocke la valeur dans `DOLINFRAS_VERSION`
2. **Dans le constructeur** : le descripteur construit la famille avec les polices InfraS et la stocke dans `DOLINFRAS_FAMILY`
3. **Utilisation par d'autres modules** : les descripteurs des autres modules InfraS (infrasdiscount, infraspackplus, etc.) peuvent lire `DOLINFRAS_VERSION` pour afficher « Dolibarr LTS by InfraS » au lieu du nom de famille standard

### Chargement des constantes LTS (LTS constants loading)

À l'activation, `init()` appelle `_load_tables('/dolinfras/sql/')` qui exécute `data.sql`. Ce fichier contient deux blocs :

1. **Bloc SaaS by InfraS** (`visible=0`) : constantes de configuration de la distribution LTS, non visibles dans l'interface admin standard. Couvrent la sécurité, le mail, le WYSIWYG, les fichiers, le branding, les permissions.
2. **Bloc options fonctionnelles** (`visible=1`) : options Dolibarr standards préactivées pour les instances LTS. Couvrent les documents commerciaux, les tiers, les projets, les contacts, les utilisateurs.

Toutes les constantes utilisent `INSERT IGNORE` — idempotentes, sans écrasement des valeurs déjà modifiées par l'admin. Elles persistent après désactivation du module (pas de nettoyage SQL dans `remove()`).

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="18.0.0" MonthVersion="2026-03">
        <change type='add'>Initiale release.</change>
    </Version>
    <Version Number="18.1.3" MonthVersion="2026-07">
        <change type='add'>Ajout de la constante masquée MAIN_HTML_FOOTER : place le bouton d'import de lignes avant le libellé du type dans le bloc des objets liés</change>
    </Version>
    <InfraS Downloaded="20260708"/>
    <Dolibarr minVersion="18.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

Types de changement supportés : `add` (ajout), `chg` (modification), `fix` (correction).

La fonction `dolinfras_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "18.1.3",           // Version courante du module
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (0 = OK, -1 = KO)
    3 => SimpleXMLElement[], // Tableau des versions
    4 => "24.x.x",          // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Affichage du changelog (`dolinfras_getChangeLog`)

La fonction génère un HTML complet comprenant :

1. **Bannière de support** : header avec logo InfraS, liens vers le wiki, le store, le dolistore, le formulaire de support (pré-rempli avec module/version/PHP/Dolibarr), et le badge Dolibarr Preferred Partner
2. **Tableau comparatif** : trois cas d'affichage selon la comparaison local vs téléchargé :
   - **Nouvelles versions disponibles** (`count(downloaded) > count(local)`) : fond orange (`.dolinfrasbgorange`) pour les versions non installées
   - **Version en avance** (`count(downloaded) < count(local)`) : fond vert (`.dolinfrasbggreen`) pour les versions en avance
   - **À jour ou pas de connexion** : affichage simple sans coloration
3. **Bouton de vérification** : soumission de formulaire pour déclencher `dolinfras_dwnChangelog()` (masqué si `INFRAS_SKIP_CHECKVERSION` est activé)

### Sécurité

- **XXE** : `simplexml_load_string()` appelé avec `LIBXML_NONET` pour bloquer les entités externes
- **XSS** : toutes les sorties XML (numéro de version, date, type de changement, contenu) échappées via `dol_escape_htmltag()`
- **CSRF** : token Dolibarr (`newToken()`) inclus dans le formulaire du changelog
- **PHP_SELF** : échappé via `dol_escape_htmltag()` dans l'attribut `action` du formulaire

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.
