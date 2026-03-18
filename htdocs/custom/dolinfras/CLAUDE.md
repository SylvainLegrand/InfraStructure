# CLAUDE.md — Contexte module dolinfras

## Aperçu (Overview)

`dolinfras` est un module externe Dolibarr de branding et suivi de version pour les instances « Dolibarr LTS by InfraS » :

- branding dynamique de la famille module (« Dolibarr LTS by InfraS »),
- lecture et stockage de la version Dolibarr dans une constante dédiée,
- avertissement à la connexion si la version Dolibarr dépasse la version max supportée,
- affichage du changelog avec détection de mises à jour dans l'onglet aide du descripteur,
- détection automatique du thème sombre (dark mode) pour le branding sur la page modules.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500100`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `23.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.0.0` (2026-03)
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
└── langs/
    ├── en_US/dolinfras.lang
    ├── es_ES/dolinfras.lang
    └── fr_FR/dolinfras.lang
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
- **Permissions** : aucune (tableau vide)
- **Menus** : aucun (tableau vide)
- **Famille** : `Dolibarr LTS by InfraS` (branding dynamique avec polices puentebold et NeuropolRegular)

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/dolinfras/sql/')`)
2. Initialisation standard (`_init()`)

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue retrait standard (`_remove()`).

### Gestion de version (Lifecycle : `getLocalVersion()`)

Lecture depuis `docs/changelog.xml` via `dolinfras_getLocalVersionMinDoli()` :

- renseigne `need_dolibarr_version`, `phpmin`, `phpmax`,
- désactive automatiquement le module si la version Dolibarr est inférieure au minimum requis.

### Changelog dans l'aide (Lifecycle : `getChangeLog()`)

`getChangeLog()` appelle `dolinfras_getChangeLog()` pour afficher le changelog complet avec bannière de support et détection de mises à jour dans l'onglet « ? » du descripteur sur la page modules.

## Fonctionnement principal (Core behavior)

Le module assure le branding et le suivi de version des instances Dolibarr LTS par InfraS :

- `actions_dolinfras.class.php` : hook `afterLogin` qui vérifie la version max supportée et stocke la version Dolibarr,
- `dolinfrasAdmin.lib.php` : fonctions utilitaires (vérification PHP XML, lecture changelog, affichage changelog avec support, téléchargement de mises à jour).

## Hooks et comportement (Hook behavior)

La classe `Actionsdolinfras` intervient sur le contexte `login` :

- `afterLogin` : appelle `dolinfras_getVersionDolinfras()` pour stocker la version Dolibarr dans `DOLINFRAS_VERSION`, puis vérifie si la version Dolibarr dépasse la version max supportée (`DOLINFRAS_DISABLE_CHECK_VERSION_MAX`) et affiche un avertissement le cas échéant.

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. Aucun répertoire `sql/` n'est présent. Toute la configuration est stockée dans `llx_const` via `dolibarr_set_const()`.

## Fonctions utilitaires (Library functions)

### `dolinfrasAdmin.lib.php`

Fichier unique de bibliothèque contenant toutes les fonctions du module :

| Fonction | Description |
|----------|-------------|
| `dolinfras_test_php_ext()` | Vérifie si l'extension PHP XML est chargée, stocke le résultat dans `INFRAS_PHP_EXT_XML` |
| `dolinfras_getLocalVersionMinDoli($appliname)` | Lit `docs/changelog.xml` et retourne un tableau [version, minDoli, errFlag, versionsArray, maxDoli, minPHP, maxPHP] |
| `dolinfras_getVersionDolinfras()` | Lit le fichier `VERSION` de Dolibarr et stocke sa valeur dans `DOLINFRAS_VERSION` et la famille dans `DOLINFRAS_FAMILY` |
| `dolinfras_getChangelogFile($appliname, $from)` | Charge et parse un fichier changelog XML (local ou téléchargé) via `simplexml_load_string()` avec `LIBXML_NONET` |
| `dolinfras_dwnChangelog($appliname)` | Télécharge le dernier changelog depuis `infras.fr` via `getURLContent()` et le stocke en local |
| `dolinfras_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn)` | Génère le HTML complet du changelog : bannière de support InfraS, tableau comparatif local/téléchargé, bouton de vérification |

## Constantes de configuration (Key settings)

Constantes système utilisées par le module :

| Constante | Type | Description |
|-----------|------|-------------|
| `INFRAS_PHP_EXT_XML` | int | État de l'extension PHP XML (1 = ok, -1 = absente) |
| `DOLINFRAS_VERSION` | string | Version de Dolibarr lue depuis le fichier `VERSION` |
| `DOLINFRAS_FAMILY` | string | HTML de la famille module avec branding InfraS |
| `DOLINFRAS_DISABLE_CHECK_VERSION_MIN` | bool | Désactive la vérification de version minimum Dolibarr |
| `DOLINFRAS_DISABLE_CHECK_VERSION_MAX` | bool | Désactive la vérification de version maximum Dolibarr |
| `INFRAS_SKIP_CHECKVERSION` | bool | Désactive la vérification de mise à jour en ligne (bouton téléchargement changelog) |

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
- `DolInfraSParam*` — 14 clés pour la bannière de support et le changelog (présentation InfraS, slogan, liens, historique des mises à jour, etc.)

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

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`dolinfras_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis
- Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée
- La constante `DOLINFRAS_VERSION` est utilisée par d'autres modules InfraS (infrasdiscount, infraspackplus) pour le branding dynamique de leur famille
- Le fichier `dolinfrasAdmin.lib.php` utilise des fins de ligne Windows (`\r\n`) — les outils d'édition doivent en tenir compte

## Notes techniques (Technical notes)

### Branding dynamique (Dynamic branding)

Le module fournit un mécanisme de branding centralisé pour tous les modules InfraS :

1. **À la connexion** : `afterLogin()` appelle `dolinfras_getVersionDolinfras()` qui lit `htdocs/VERSION` et stocke la valeur dans `DOLINFRAS_VERSION`
2. **Dans le constructeur** : le descripteur construit la famille avec les polices InfraS et la stocke dans `DOLINFRAS_FAMILY`
3. **Utilisation par d'autres modules** : les descripteurs des autres modules InfraS (infrasdiscount, infraspackplus, etc.) peuvent lire `DOLINFRAS_VERSION` pour afficher « Dolibarr LTS by InfraS » au lieu du nom de famille standard

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="18.0.0" MonthVersion="2026-03">
        <change type='add'>Initiale release.</change>
        <change type='chg'>Changed feature description.</change>
        <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="20260301"/>
    <Dolibarr minVersion="18.0.0" maxVersion="23.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

Types de changement supportés : `add` (ajout), `chg` (modification), `fix` (correction).

La fonction `dolinfras_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "18.0.0",           // Version courante du module
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (0 = OK, -1 = KO)
    3 => SimpleXMLElement[], // Tableau des versions
    4 => "23.x.x",          // Version max Dolibarr
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

- `18.0.0` (2026-03) : version initiale — branding dynamique, hook afterLogin, changelog avec bannière de support, détection dark mode, durcissement sécurité (XXE, XSS, CSRF), compatibilité PHP 8.4
- Entrées du changelog par version (types : `add`, `chg`, `fix`)

Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis. Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée.