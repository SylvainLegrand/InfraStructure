# CLAUDE.md — Contexte module infrashelpdesk

## Aperçu (Overview)

`infrashelpdesk` est un module externe Dolibarr InfraS d'assistance au support / diagnostic :

- **bouton flottant de contexte** affiché sur toutes les pages (déplaçable, position mémorisée par navigateur) : le clic ouvre un panneau présentant la documentation wiki de la page courante et la liste des modules externes qui interagissent avec elle ; en mode développeur, le panneau affiche aussi le chemin de la page, ses contextes de hook (`$hookmanager->contextarray`) et les fonctions de hook qu'elle utilise,
- **dictionnaire de correspondance** `llx_c_infrashelpdesk_ctxurl` (page Dolibarr → contexte → fonctions de hook → URL wiki `url_page` (sous-chapitre) / `url_wiki` (chapitre)), seedé depuis les sources Dolibarr et éditable via *Accueil / Configuration / Dictionnaires*,
- un clic sur une URL du panneau ouvre une **popup (dialog jQuery UI modale avec iframe)** affichant la page wiki correspondante,
- visibilité du bouton : administrateurs, utilisateurs porteurs de la permission `paramInfraSHelpDeskBtn`, ou tous les utilisateurs connectés si `INFRASHELPDESK_BUTTON_FOR_ALL_USERS` est activée,
- pages d'administration standard InfraS (paramètres, à propos, changelog) avec vérification de version Dolibarr/PHP et sauvegarde/restauration des paramètres.

Le module ne contient **pas encore de fonctionnalité de gestion de tickets** — ces fonctionnalités restent à concevoir et implémenter.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500101`
- Position dans la famille (`module_position`) : `100022` — famille `DOLINFRAS_FAMILY` « Dolibarr LTS by InfraS » quand dolinfras est activé
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.2.8` (2026-10)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
- Emplacement : `htdocs/custom/infrashelpdesk/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrashelpdesk/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrashelpdesksetup.php
├── class/
│   └── actions_infrashelpdesk.class.php
├── config.php
├── core/
│   ├── lib/
│   │   └── infrashelpdeskAdmin.lib.php
│   └── modules/
│       └── modinfrashelpdesk.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrashelpdesk.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
├── js/
│   └── infrashelpdesk.js
├── langs/
│   ├── en_US/infrashelpdesk.lang
│   ├── es_ES/infrashelpdesk.lang
│   └── fr_FR/infrashelpdesk.lang
└── sql/
    ├── llx_c_infrashelpdesk_ctxurl.sql      # Table dictionnaire page → contexte → hooks → URL wiki
    ├── llx_c_infrashelpdesk_ctxurl.key.sql  # Index unique (page, context, entity)
    └── data.sql                             # Seed : 3 constantes + 787 lignes de dictionnaire (INSERT IGNORE)
```

## Descripteur module (Module descriptor : `modinfrashelpdesk`)

Dans `core/modules/modinfrashelpdesk.class.php` :

- **Module parts** :
	- hooks : `all` (la classe de hook est instanciée sur toutes les pages — nécessaire pour le bouton flottant de contexte)
	- JS : `/infrashelpdesk/js/infrashelpdesk.js`
	- CSS : `/infrashelpdesk/css/infrashelpdesk.css.php`
- **Dépendances** : aucune (`depends`, `requiredby`, `conflictwith` vides)
- **Dictionnaires** : 1 — `c_infrashelpdesk_ctxurl` (colonnes éditables `page`, `context`, `hooks`, `url_page`, `url_wiki`), filtré sur l'entité courante, trié par `page ASC`, visible si le module est activé (`tabcond`)
- **Boxes / Cron / Tabs / Constantes déclaratives** : aucun (tableaux vides)
- **Permissions** : 4 permissions
	- `paramMenu` (défaut : activée) — visibilité des menus du module
	- `paramInfraSHelpdesk` — accès à la page de paramètres
	- `paramInfraSHelpDeskBtn` — voir le bouton flottant de contexte (sans être admin)
	- `paramBkpRest` — sauvegarde/restauration des paramètres
- **Page de configuration** : `config_page_url = array('infrashelpdesksetup.php@infrashelpdesk')`
- **Famille** : `DOLINFRAS_FAMILY` si dolinfras est activé, sinon `'Modules '.$langs->trans('basenameInfraSHelpdesk')` (position de la famille `001` dans la page des modules)

### Initialisation (Lifecycle : `init()`)

`init()` charge les fichiers de `sql/` (`_load_tables('/infrashelpdesk/sql/')`) — création de la table dictionnaire, index unique, seed — puis effectue l'initialisation standard (`_init()`). Les inserts du seed sont en `INSERT IGNORE` avec index unique `(page, context, entity)` : les URL modifiées par l'admin ne sont pas écrasées par une réactivation.

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- suppression des constantes `INFRASHELPDESK_%` de l'entité courante,
- **`DROP TABLE IF EXISTS llx_c_infrashelpdesk_ctxurl`** — le dictionnaire est détruit à la désactivation (voir *Points d'attention*).

### Gestion de version (Lifecycle : `getLocalVersion()`)

Lecture depuis `docs/changelog.xml` via `infrashelpdesk_getLocalVersionMinDoli()` : renseigne `need_dolibarr_version`, `phpmin`, `phpmax`, désactive automatiquement le module si la version Dolibarr est inférieure au minimum requis (`INFRASHELPDESK_DISABLE_CHECK_VERSION_MIN` désactive ce contrôle).

### Menus (Menu structure)

Le module crée des entrées dans le menu Outils :

1. **Entrée InfraS-2** (`leftmenu = infras2`, position 130) : créée seulement si `infrashelpdesk_no_topmenu()` retourne `1` (aucune entrée `leftmenu = infras` existante dans `llx_menu` pour l'entité) ; visible pour tous.
2. **Sous-menu InfraSHelpdesk** (rattaché à `fk_leftmenu=infras2`) :
	- **Titre module** (position 132) : `/core/tools.php?leftmenu=infrashelpdesk` — permission `paramMenu`
	- **Changelog** (position 134) : `/infrashelpdesk/admin/changelog.php` — permission `paramMenu`
	- **Paramètres** (position 135) : `/infrashelpdesk/admin/infrashelpdesksetup.php` — permissions `paramMenu` ET `paramInfraSHelpdesk`

**Note** : le menu « À propos » n'est pas présent dans la structure de menus (accessible uniquement via les onglets admin).

## Fonctionnement principal (Core behavior)

Fonctionnalité actuelle unique : le **bouton flottant de contexte**, construit sur trois briques :

1. le hook `printCommonFooter` (`class/actions_infrashelpdesk.class.php`) qui collecte les données et imprime la configuration JSON `infrashelpdeskCtxConf` en pied de page,
2. le JS `js/infrashelpdesk.js` (chargé partout via `module_parts`) qui construit le bouton et le panneau,
3. le CSS `css/infrashelpdesk.css.php` (styles thème-conscients, support oblyon).

Le dictionnaire `llx_c_infrashelpdesk_ctxurl` fait le lien entre la page courante et le wiki InfraS (`wiki.infras.fr`, BookStack).

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrashelpdesk` est instanciée sur toutes les pages (contexte `all`) :

- `afterLogin` : vérifie si la version Dolibarr installée dépasse la version maximum supportée et affiche un avertissement le cas échéant (désactivable via `INFRASHELPDESK_DISABLE_CHECK_VERSION_MAX`).
- `printCommonFooter` : injecte en pied de page la configuration JSON du bouton flottant de contexte — voir *Notes techniques*.

Deux méthodes privées d'affichage :

- `getContextLabel($context)` : traduit un nom de contexte en libellé lisible via l'index insensible à la casse des clés de traduction chargées par la page, en retirant progressivement les suffixes techniques (`dao`, `card`, `list`, `index`, `agenda`, `note`, `document`, `contact`, `ldap`, `partnership`, `stats`) — ex. `membertypeldapcard` → `membertype`.
- `getWikiLabel($url, $context)` : déduit le libellé d'un lien wiki depuis le slug de l'URL — `/page/NN-slug` → sous-chapitre (`Propositions commerciales`), `/chapter/NN-slug` → chapitre en majuscules (`COMMERCE`), sinon repli sur `getContextLabel()`.

## Trigger (Trigger behavior)

Le module ne possède pas de trigger (pas de répertoire `core/triggers/`). Aucun événement n'est écouté.

## Données / SQL (Data model)

Le module crée 1 table SQL (dictionnaire) :

### `llx_c_infrashelpdesk_ctxurl` — Dictionnaire page → contexte → hooks → URL wiki

| Colonne | Type | Description |
|---------|------|-------------|
| `rowid` | int (PK) | Identifiant unique |
| `page` | varchar(128) | Chemin du script relatif à htdocs (ex. `comm/propal/card.php`), éventuellement suffixé `?leftmenu=xxx` ou `?contextpage=xxx` ; vide pour les contextes hérités sans page |
| `context` | varchar(128) | Contexte de hook principal de la page (`initHooks()`) |
| `hooks` | text | Fonctions `executeHooks()` de la page, séparées par virgules (y compris appels indirects via libs/classes) |
| `url_page` | varchar(255) | URL wiki du sous-chapitre de la page (`/page/NN-slug`), prioritaire |
| `url_wiki` | varchar(255) | URL wiki du chapitre parent (`/chapter/NN-slug`), repli |
| `active` | int | Ligne active (dictionnaire standard) |
| `entity` | int | Entité multi-société |

Index unique : `uk_c_infrashelpdesk_ctxurl (page, context, entity)`.

### Données initiales (`data.sql`)

- 3 constantes (`INSERT INTO llx_const`, placeholder `__ENTITY__`) :

| Constante | Valeur seed | Description |
|-----------|-------------|-------------|
| `INFRASHELPDESK_BUTTON_FOR_ALL_USERS` | `1` | Bouton flottant visible par tous les utilisateurs connectés |
| `INFRASHELPDESK_ENABLE_DEV_MODE` | `1` | Sections développeur du panneau (page, contextes, fonctions de hook) |
| `INFRASHELPDESK_PREFIX_LINK_WIKI_DOC` | `https://wiki.infras.fr/books/guide-dolibarr-v24` | Préfixe ajouté aux URL wiki relatives du dictionnaire (pages core) |

- 787 lignes de dictionnaire en `INSERT IGNORE`, dont 114 **contextes hérités sans page** (`page` vide : classes dao, `main.inc.php`, contextes dynamiques… jamais retournés par le lookup mais éditables) et 673 lignes rattachées à une page (variantes `?leftmenu=` / `?contextpage=` comprises), générées depuis les sources Dolibarr — voir *Notes techniques*. Depuis 2026-07-30 : 16 lignes ajoutées manuellement (hors script de génération) pour les modules partenaires **oblyon** (9 lignes : racine + 8 pages admin) et **sirene** (7 lignes : racine + 6 pages admin), pointant vers leurs livres dédiés sur l'étagère wiki `https://wiki.infras.fr/shelves/module-partenaire` (`/books/oblyon`, `/books/sirene`) plutôt que vers l'étagère générique `modules-infras` — à préserver lors d'une prochaine régénération du seed. Depuis 2026-09-14 : 20 lignes supplémentaires, également à préserver — racine `custom/einvoicing/` et racine `custom/multismtp/` (livres `/books/e-invoicing` et `/books/multismtp` en `url_wiki`, pour la section « Documentation des modules externes ») ; 4 pages **infras2smtp2go** (`admin/about.php`, `admin/changelog.php`, `admin/infras2smtp2gosetup.php`, `card.php`) et 4 pages **infrasfiles** (`admin/about.php`, `admin/changelog.php`, `admin/infrasfilessetup.php`, `document.php`) pointant vers les pages de leurs livres `/books/infras2smtp2go` et `/books/infrasfiles`, pour la section « Documentation Dolibarr » ; et, pour **infrasfiles**, le rattachement de ses 12 chapitres aux pages sur lesquelles le module agit — pages natives `compta/prelevement/card.php` (bordereau), `product/inventory/card.php` et `product/inventory/inventory.php` (feuille de comptage), `admin/mails_templates.php` (envoi par email) via une **seconde ligne au contexte propre au module** (`infrasfileswidthdraw`, `infrasfilesinventory`, `infrasfilesemailtemplates`) : la clé unique porte sur `(page, context, entity)`, plusieurs lignes peuvent donc décrire la même page et le panneau affiche alors un lien par ligne (le guide Dolibarr **et** le chapitre du module). Ces lignes restent visibles sur une instance où infrasfiles n'est pas installé : le lookup ne teste pas `isModEnabled()`, l'admin les désactive au besoin via la colonne `active` du dictionnaire. Sur toutes ces lignes (et sur les 7 lignes **sirene**), le livre du module est renseigné en `url_wiki` : la section « Documentation des modules externes » privilégie cette colonne, donc le lien du module ouvre le livre quelle que soit la ligne retenue par le lookup. Depuis 2026-09-21 : 40 lignes supplémentaires pour le module **uptosign** (signature électronique), également à préserver — racine `custom/uptosign/` et les 6 pages du module absentes du seed généré (`index.php`, `admin/about.php`, `admin/uptosignlist_extrafields.php`, `uptosignlist_advcibles.php`, `uptosignlist_docs.php`, `uptosignlist_list.php`) ; 31 secondes lignes au contexte propre au module (`uptosign*`) sur les pages natives où il agit — fiches et listes proposition commerciale, commande, facture, contrat, intervention, expédition, projet (onglet *Signature électronique*, boutons signer / sceller, action de masse de scellement en lot, hooks indirects `addMoreMassActions, doMassActions` et `completeTabsHead` renseignés en colonne `hooks`), fiches tiers, contact, utilisateur, commande fournisseur et demande de prix fournisseur, onglets *Fichiers joints* (hook indirect `formBuilddocLineOptions` de la liste des fichiers) et `admin/mails_templates.php` ; 2 lignes sur les pages du module `infrassalariescontracts` (fiche et onglet UptoSign). Les 24 lignes `custom/uptosign/*` déjà présentes, qui pointaient sur l'étagère `modules-infras`, ont été réorientées vers le livre `https://wiki.infras.fr/books/uptosign` (page du livre en `url_page`, livre en `url_wiki`). Depuis 2026-09-22 : 40 lignes pour les modules partenaires **scaninvoices** (26) et **zenfusionmaps** (14), également à préserver — livres `https://wiki.infras.fr/books/scaninvoices` et `https://wiki.infras.fr/books/zenfusionmaps` en `url_wiki`. ScanInvoices : racine `custom/scaninvoices/`, pages du module (`admin/about.php` ×2 : présentation, installation ; `admin/setup.php` → onglet Compte, `admin/setup-1.php` → Réglages, `admin/setup-2.php` → Partage (import distant), `admin/setup-3.php` → Import des lignes ; `importinvoice.php` et `importauto.php` ×2 : utilisation au quotidien, choix des documents PDF ; `filestoimport_list.php` ×2 : utilisation, questions fréquentes ; `scaninvoices_thirdparty.php`, `settings_*` et `admin/settings_extrafields.php` → onglet ScanInvoices de la fiche tiers ; `scaninvoices_invoicesuppliercard.php`, `filestoimport_card.php`, `admin/filestoimport_extrafields.php` → utilisation), les 4 lignes générées (`filestoimport_*`, `settings_*`) ayant été réorientées de l'étagère `modules-infras` vers le livre ; secondes lignes au contexte `scaninvoices*` sur `societe/card.php` et `fourn/card.php` (onglet ScanInvoices), `fourn/commande/card.php` (bouton Scanner la facture), `fourn/facture/card.php` (onglet ScanInvoices), `admin/emailcollector_card.php` (hook indirect `addmoduletoeamailcollectorjoinpiece` en colonne `hooks`) et `cron/list.php` (tâches cron). ZenFusion Maps : racine `custom/zenfusionmaps/`, `admin/about.php` ×5 (présentation, installation, configuration, limites, dépannage) ; secondes lignes `zenfusionmaps*` sur `societe/card.php`, `contact/card.php`, `adherents/card.php` (×2 : utilisation au quotidien avec hook indirect `printAddress` — appelé par `dol_print_address()` depuis la bannière `CommonPeople::getBannerAddress()` — et fonctionnement détaillé du lien) et sur `user/perms.php` / `user/group/perms.php` (droits utilisateurs). Les onglets Client (`comm/card.php`, contexte `thirdpartycomm`) et Fournisseur (`fourn/card.php`, `thirdpartysupplier`) ne sont volontairement pas rattachés à ZenFusion Maps : le module déclare `commcard` / `suppliercard`, contextes inexistants en Dolibarr 22, sa classe de hook n'y est donc jamais chargée (le wiki les annonce à tort). Depuis 2026-10-02 : 18 lignes supplémentaires, également à préserver. Module **infrasagenda** (agenda interactif ; livre `https://wiki.infras.fr/books/infrasagenda`, 9 chapitres d'une page chacun, rédigés le 2026-10-02 depuis `infrasagenda/docs/manuel_utilisateur.txt`) : racine `custom/infrasagenda/` (→ page présentation), `admin/about.php` ×3 (présentation ; `infrasagendainstall` → prérequis et installation ; `infrasagendaactivation` → mise en service et reprise de l'existant), `admin/changelog.php`, `admin/infrasagendasetup.php` et `admin/infrasagendacolors.php` (→ page paramètres du module, qui couvre les onglets Paramètres et Couleurs, la sauvegarde et restauration et les onglets À propos et Changelog), 4 secondes lignes sur `comm/action/index.php` (`infrasagendagrid` → prise en main, hooks `addCalendarChoice, beforeAgenda` tous deux appelés directement par la page ; `infrasagendaevents` → gestion des événements ; `infrasagendadragdrop` → déplacer et redimensionner ; `infrasagendasources` → congés et calendriers externes) et lignes `infrasagendauserperms` / `infrasagendagroupperms` sur `user/perms.php` / `user/group/perms.php` (→ droits d'accès) ; `comm/action/peruser.php` et `pertype.php` volontairement non rattachés (le module y garde l'affichage natif). Module **infrasfiles** : racine `custom/infrasfiles/` (→ page présentation), `societe/card.php` (contexte `infrasfilesthirdpartycard`, attribut « Adresser les bordereaux à la maison mère » → page bordereau), `admin/agenda.php` (`infrasfilesagenda`, événements automatiques « envoyé par email » → page envoi par email), `user/perms.php` / `user/group/perms.php` (`infrasfilesuserperms` / `infrasfilesgroupperms` → page permissions) ; colonne `hooks` des trois fiches natives corrigée en `addMoreActionsButtons, doActions, printCommonFooter` (le module n'implémente pas `formObjectOptions` ; sa section « Fichiers joints » vient du hook indirect `printCommonFooter`) et `getFormMail` ajouté sur la ligne `infrasfilesemail` de `custom/infrasfiles/document.php`. Ces lignes et corrections ont été appliquées directement dans la base dolinfras le même jour (`INSERT IGNORE` + `UPDATE`), le seed ne s'appliquant qu'à la réactivation du module.

## Pages d'administration (Admin pages)

Trois pages dans `admin/`, avec onglets communs générés par `infrashelpdesk_admin_prepare_head()` :

| Page | Onglet | Rôle |
|------|--------|------|
| `admin/infrashelpdesksetup.php` | `infrashelpdesksetup` | Page de paramètres : section sauvegarde/restauration (admin ou `paramBkpRest`), options du bouton flottant — `INFRASHELPDESK_PREFIX_LINK_WIKI_DOC` (input), `INFRASHELPDESK_BUTTON_FOR_ALL_USERS` (on/off), `INFRASHELPDESK_ENABLE_DEV_MODE` (on/off). Actions génériques `set_<CONSTANTE>` (on/off), `update_tblCtxButton` (inputs), `bkupParams` / `restoreParams` |
| `admin/about.php` | `about` | Affiche `README.md` en HTML |
| `admin/changelog.php` | `changelog` | Affiche le changelog complet (`infrashelpdesk_getChangeLog()`) avec bouton de vérification de mise à jour (action `dwnChangelog`) |

Contrôle d'accès de la page de paramètres : admin ou `paramBkpRest` → accès complet (niveau 2, avec section sauvegarde/restauration) ; `paramInfraSHelpdesk` → options seulement (niveau 1) ; sinon `accessforbidden()`.

## Fonctions utilitaires (Library functions)

### `infrashelpdeskAdmin.lib.php`

| Fonction | Description |
|----------|--------------|
| `infrashelpdesk_admin_prepare_head()` | Génère les onglets des pages d'administration (Paramètres — si admin —, À propos, Changelog) |
| `infrashelpdesk_no_topmenu()` | Vérifie si une entrée `leftmenu = infras` existe déjà dans le menu Outils (`llx_menu`) |
| `infrashelpdesk_test_php_ext()` | Vérifie si l'extension PHP XML est chargée, stocke le résultat dans `INFRAS_PHP_EXT_XML` |
| `infrashelpdesk_getLocalVersionMinDoli($appliname)` | Lit `docs/changelog.xml` et retourne un tableau [version, minDoli, errFlag, versionsArray, maxDoli, minPHP, maxPHP] |
| `infrashelpdesk_getChangelogFile($appliname, $from)` | Charge et parse un fichier changelog XML (local ou téléchargé) via `simplexml_load_string()` avec `LIBXML_NONET` |
| `infrashelpdesk_dwnChangelog($appliname)` | Télécharge le dernier changelog depuis le dépôt GitHub `InfraS-SARL/modules-versions` via `getURLContent()` et le stocke dans `DOL_DATA_ROOT` |
| `infrashelpdesk_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn)` | Génère le HTML complet du changelog : bannière de support InfraS, tableau comparatif local/téléchargé, bouton de vérification |
| `infrashelpdesk_bkup_module($appliname)` | Sauvegarde des paramètres en SQL (`DOL_DATA_ROOT/.../infrashelpdesk/sql/update.<entity>` + copie horodatée dans `admin/`) |
| `infrashelpdesk_bkup_table($table, $sql, ...)` | Génère les `INSERT ... ON DUPLICATE KEY UPDATE` d'une table pour le fichier de sauvegarde |
| `infrashelpdesk_print_backup_restore()` | Section HTML sauvegarde/restauration de la page de paramètres |
| `infrashelpdesk_load_title()` / `infrashelpdesk_print_*()` | Fonctions d'affichage HTML génériques des tableaux de paramètres admin (colgroup, liste_titre, btn_action, input, subTitle, hr, final) |

## Traductions (Translations)

Trois répertoires de traduction : `en_US`, `es_ES`, `fr_FR`. Fichier unique `infrashelpdesk.lang` par locale. Chapitres : `### Permissions ###`, `### Lib functions ###`, `### Setup page ###`, `### Context button ###`, `### Dictionary ###`, `### Changelog / About ###`. Chargement :

```php
$langs->load('infrashelpdesk@infrashelpdesk');
```

## CSS / JS

- `css/infrashelpdesk.css.php` : polices embarquées (`puentebold`, `NeuropolRegular`), classes utilitaires `.infrashelpdesk*` de la bannière de support du changelog, et styles du bouton flottant de contexte (`#infrashelpdesk-ctx-*`, `.infrashelpdesk-wiki-dialog*`) — thème-conscients avec branche dédiée oblyon (`isModEnabled('oblyon')`). Bouton positionné en `bottom: 145px` pour ne pas chevaucher le sommaire flottant du module `infrastructure` (`bottom: 85px`).
- `js/infrashelpdesk.js` : deux blocs indépendants —
	1. détection du thème sombre sur `/admin/modules.php` (luminance BT.601 de `--colorbline` → classe `infrashelpdesk-dark-bg` sur `<html>`),
	2. construction du bouton flottant de contexte si `infrashelpdeskCtxConf` est défini (voir *Notes techniques*).

## Constantes de configuration (Key settings)

| Constante | Type | Description |
|-----------|------|--------------|
| `INFRAS_PHP_EXT_XML` | int | État de l'extension PHP XML (1 = ok, -1 = absente) |
| `INFRASHELPDESK_BUTTON_FOR_ALL_USERS` | bool | Affiche le bouton flottant à tous les utilisateurs connectés (seed : `1` ; sinon admins et permission `paramInfraSHelpDeskBtn`) |
| `INFRASHELPDESK_ENABLE_DEV_MODE` | bool | Affiche les sections développeur du panneau : page courante, contextes de hook, fonctions de hook (seed : `1`) |
| `INFRASHELPDESK_PREFIX_LINK_WIKI_DOC` | string | Préfixe ajouté aux URL wiki **relatives** du dictionnaire (pages core Dolibarr) ; les URL absolues (modules externes) ne sont pas préfixées |
| `INFRASHELPDESK_DISABLE_CHECK_VERSION_MIN` | bool | Désactive la vérification de version minimum Dolibarr (auto-désactivation du module) |
| `INFRASHELPDESK_DISABLE_CHECK_VERSION_MAX` | bool | Désactive l'avertissement de version maximum Dolibarr (`afterLogin`) |
| `INFRAS_SKIP_CHECKVERSION` | bool | Désactive la vérification de mise à jour en ligne (bouton téléchargement changelog) |

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets,
- contenu du panneau JS inséré via `textContent` (pas d'injection HTML), JSON encodé avec `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`, `<script nonce>`.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / menus / hooks :

1. Désactiver puis réactiver le module (attention : `remove()` **détruit** le dictionnaire — sauvegarder les URL modifiées avant)
2. Vérifier la création de la table `llx_c_infrashelpdesk_ctxurl` et son seed
3. Vérifier les constantes module (`INFRASHELPDESK_*`)
4. Tester le bouton flottant sur une page core (ex. fiche devis) : sections wiki et modules interagissants
5. Tester le lookup des pages à variantes (`product/list.php?leftmenu=service`, `commande/list.php?contextpage=billableorders`)
6. Tester la popup wiki (iframe) et le drag & drop du bouton
7. Vérifier les menus Outils → InfraS-2 et les 3 onglets admin

## Points d'attention (Watchpoints)

- Le module n'a **pas de gestion de tickets** pour l'instant — sa fonctionnalité actuelle est le bouton flottant de contexte.
- **`remove()` fait un `DROP TABLE` du dictionnaire** : toute URL corrigée manuellement via *Dictionnaires* est perdue à la désactivation du module. L'`INSERT IGNORE` du seed ne protège les éditions que tant que la table survit.
- **`infrashelpdesk_restore_module()` n'existe pas** dans `infrashelpdeskAdmin.lib.php` alors que la page de paramètres l'appelle (action `restoreParams`) : cliquer sur « Restaurer » provoque une erreur fatale PHP. Fonction à implémenter (modèle : `infrassearch_restore_module`).
- **`infrashelpdesk_bkup_module()` dumpe le dictionnaire avec l'ancien schéma** (`context, entity, hooks, url, active`) : la colonne `url` n'existe plus (remplacée par `page`, `url_page`, `url_wiki`), la requête échoue et la section dictionnaire de la sauvegarde est silencieusement vide. Colonnes à mettre à jour.
- Le JS est déclaré **sans cache-buster** (`/infrashelpdesk/js/infrashelpdesk.js`, pas de `?version=`) : Apache sert les JS de modules avec `max-age` 30 jours — après une évolution du JS, forcer Ctrl+F5 ou ajouter un paramètre de version dans le descripteur.
- `infrashelpdesk_no_topmenu()` teste l'existence de `leftmenu = "infras"` mais le module crée `leftmenu = "infras2"` (titre `InfraS-2`) : si un autre module InfraS a déjà créé l'entrée `infras`, l'entrée `InfraS-2` n'est pas créée alors que les sous-menus restent rattachés à `fk_leftmenu=infras2`.
- Le seed active par défaut `INFRASHELPDESK_BUTTON_FOR_ALL_USERS = 1` et `INFRASHELPDESK_ENABLE_DEV_MODE = 1` : sur une instance de production, désactiver ces options si le bouton/les sections développeur ne doivent pas être visibles de tous.
- Le hook `printCommonFooter` s'exécute aussi sur les pages publiques : le garde `empty($user->id)` évite toute sortie hors session.
- La liste des modules « interagissant » croise deux critères : contextes déclarés ∩ contextes de la page, ET implémentation d'au moins une fonction de `functionHooks`. Si aucune fonction de hook n'est connue pour la page (ni scan du script, ni colonne `hooks` du dictionnaire), la liste est **vide** — pas de repli sur le seul croisement de contextes (les sections « Fonctions de hook » et « Modules externes » restent cohérentes).
- Limite du scan statique : les hooks invoqués par les classes core pour le compte de la page (ex. `printObjectLine` appelé depuis `CommonObject::printObjectLines()`) ne sont pas vus dans le script — les documenter dans la colonne `hooks` du dictionnaire pour qu'ils comptent.
- Seuls les modules **externes** (répertoire dans `htdocs/custom/`) sont listés ; les hooks du core ne le sont pas. Le module `infrashelpdesk` s'exclut lui-même.
- `$hookmanager->hooksHistory` (traçage runtime) n'est rempli que si le module `debugbar` est actif — d'où la détection par scan statique.
- Si la table dictionnaire n'existe pas encore (module non réactivé après mise à jour), l'erreur SQL est journalisée (`dol_syslog`, `LOG_WARNING`) et la section wiki est simplement absente.
- **Iframe et X-Frame-Options** : la popup wiki embarque `wiki.infras.fr` en iframe — BookStack doit autoriser le framing depuis les instances Dolibarr (variable d'environnement `ALLOWED_IFRAME_HOSTS`), sinon la dialog restera vide.
- La version locale est lue depuis `docs/changelog.xml` — l'extension PHP XML est nécessaire pour la parser.
- Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis.
- Voir la section *Historique* pour le contexte du correctif de la page blanche (numéro de module, classes, fonctions).
- `infrashelpdesk_test_php_ext()` (appelée par le constructeur du descripteur) n'écrit la constante partagée `INFRAS_PHP_EXT_XML` que si sa valeur change (depuis 18.2.9) : la réécriture systématique (DELETE + INSERT dans `llx_const`) pouvait entrer en conflit avec une transaction concurrente (incident d'octobre 2026 avec Infrastructure : lignes de document perdues en silence). Ne jamais réintroduire d'écriture inconditionnelle de constante dans du code exécuté à chaque requête, à chaque connexion ou pendant une transaction métier

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Bouton flottant de contexte — flux du hook `printCommonFooter`

```
Chargement d'une page Dolibarr (contexte hook 'all')
    ↓
Gardes : $user->id non vide, puis admin OU INFRASHELPDESK_BUTTON_FOR_ALL_USERS
         OU $user->hasRight('infrashelpdesk', 'paramInfraSHelpDeskBtn')
    ↓
Collecte des contextes de la page ($hookmanager->contextarray)
    ↓
Scan statique du script courant ($_SERVER['SCRIPT_FILENAME']) :
    occurrences executeHooks('xxx') → $pagefunctions
    ↓
Modules candidats : chaque module de $conf->modules_parts['hooks'] dont le répertoire
    est dans htdocs/custom/ et dont les contextes déclarés croisent ceux de la page
    ('all' correspond toujours) ; instance Actions* récupérée dans $hookmanager->hooks
    ↓
Lookup dictionnaire sur la colonne page (voir ci-dessous) → URL wiki + colonne hooks
    ↓
functionHooks = fusion triée/dédoublonnée (scan direct + colonne hooks du dictionnaire),
    chaque fonction portant les modules qui l'implémentent (method_exists sur Actions*)
    ↓
Filtrage final : modules retenus = exactement l'union des implémenteurs de functionHooks
    (aucune fonction connue → liste vide)
    ↓
URL de doc de chaque module retenu : première URL absolue des lignes du dictionnaire
    dont page LIKE 'custom/<module>/%' (url_wiki — livre — en priorité, sinon url_page),
    repli sur l'étagère https://wiki.infras.fr/shelves/modules-infras
    ↓
print '<script nonce>' : infrashelpdeskCtxConf = { page, contexts, functionHooks,
    modules, wiki, devmode, langs } (JSON_HEX_*)
```

### Lookup wiki par page (dictionary lookup)

Le chemin de la page courante est dérivé de `PHP_SELF` moins `DOL_URL_ROOT`. Variantes cherchées, de la plus spécifique à la plus générale (première ligne trouvée gagne) :

1. `page?contextpage=<contextpage>` (si l'URL porte un paramètre `contextpage`)
2. `page?leftmenu=<leftmenu>` (si l'URL porte un paramètre `leftmenu`)
3. `page` nue

Les pages de modules custom sont cherchées avec et sans le préfixe `custom/`. Utilisé pour les pages partagées entre zones ou vues fonctionnelles : `product/list.php?leftmenu=service` → Services, `commande/list.php?contextpage=billableorders` → Commandes à facturer ; la ligne nue des pages ambiguës pointe sur le chapitre wiki parent.

Pour chaque ligne retenue : `url_page` (sous-chapitre) en priorité, sinon `url_wiki` (chapitre) ; les URL relatives sont préfixées par `INFRASHELPDESK_PREFIX_LINK_WIKI_DOC` (pages core), les URL absolues (modules externes) restent intactes. Le libellé est déduit du slug via `getWikiLabel()`. Les doublons d'URL sont dédupliqués.

### Panneau JS (`js/infrashelpdesk.js`)

Si `infrashelpdeskCtxConf` est défini, le JS construit :

- le **bouton rond flottant** `#infrashelpdesk-ctx-floating` (icône `fa-lightbulb`) — déplaçable (souris + tactile, seuil de 5 px pour distinguer clic et drag), position mémorisée dans `localStorage` (clé `infrashelpdesk_ctx_pos`), fermeture du panneau au clic extérieur ; pattern repris du sommaire flottant du module `infrastructure` (`js/summary-menu.js`) ;
- le **panneau** `#infrashelpdesk-ctx-panel` :
	- en mode développeur (`conf.devmode`) : page courante, badges des contextes, section « Fonctions de hook de la page » (chaque fonction avec ses modules implémenteurs entre crochets) ;
	- section « Documentation wiki » : liens issus de `conf.wiki`, avec `<link rel="preconnect">` vers l'origine du wiki pour accélérer la première ouverture ;
	- liste des modules externes interagissants — le nom de chaque module est un lien ouvrant sa documentation (`module.url`), message « Aucun module… » si liste vide ;
- les **dialogs wiki** : une dialog jQuery UI modale par URL (cache `wikiDialogs`, réutilisée à la réouverture), iframe avec spinner masqué au `load`, dimensions 90 % × 85 % de la fenêtre (max 1000 px de large).

Tout le contenu est inséré via `textContent` (pas d'injection HTML).

### Dictionnaire — génération du seed (`sql/data.sql`)

Le seed est généré depuis les sources Dolibarr — une ligne par script de page (fichiers `.php` hors `.class/.lib/.inc/.tpl/.modules.php`, hors répertoires `core/`, `ajax/`, `scripts/`, `includes/`, `install/`, `theme/`) : colonne `page` = chemin relatif à htdocs, `context` = premier contexte `initHooks()` (ou défaut littéral de `$contextpage`), `hooks` = fonctions `executeHooks()` du fichier. Conventions d'URL :

- **pages core Dolibarr** → `url_page` = sous-chapitre correspondant du livre `guide-dolibarr-v24` (`/page/NN-slug`), `url_wiki` = chapitre parent (`/chapter/NN-slug`) ; les pages d'accueil de zone n'ont que le chapitre (ex. `comm/index.php` → `/chapter/08-commerce`) ; une table de règles répertoire → chapitre complète les pages sans correspondance héritée ; URL stockées **relatives** (préfixées à l'affichage par `INFRASHELPDESK_PREFIX_LINK_WIKI_DOC`) ;
- **pages de modules externes** (`custom/<module>/...`) → URL **absolues** vers le livre du module dans l'étagère `modules-infras` (slugs BookStack réels, certains avec suffixe aléatoire : `infrastructure-Dau`, `infrassecureiban-7TQ`…) ; modules sans livre → **étagère** `https://wiki.infras.fr/shelves/modules-infras` ;
- les **contextes de l'ancien seed non rattachés à une page** sont conservés en fin de seed avec `page` vide (jamais retournés par le lookup, mais éditables).

Régénération : adapter le script de génération (exclusions, table de règles répertoire → chapitre, sommaire du livre wiki) à chaque changement de version majeure Dolibarr ou d'organisation du wiki. Les URL restent corrigeables individuellement via le dictionnaire.

### Structure du changelog (Changelog structure)

```xml
<changelog>
	<Version Number="18.2.3" MonthVersion="2026-09">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
	</Version>
	<InfraS Downloaded="20260914"/>
	<Dolibarr minVersion="18.0.0" maxVersion="24.x.x"/>
	<PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

- Types de changement : `add` (ajout, vert), `chg` (modification, bleu), `fix` (correction, rouge/caution)
- L'attribut `Downloaded` est mis à jour lors du téléchargement de la version distante
- Versions ordonnées chronologiquement (la dernière est la plus récente)
- Parsé par `infrashelpdesk_getChangelogFile()` / `infrashelpdesk_getLocalVersionMinDoli()` (`LIBXML_NONET`)

`infrashelpdesk_getLocalVersionMinDoli()` retourne :

```php
[
    0 => "18.2.3",           // Version courante
    1 => "18.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag d'erreur (0 = OK, -1 = erreur XML)
    3 => SimpleXMLElement,   // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

## Cas d'usage courants (Common use cases)

### Cas 1 : Consulter la documentation wiki d'une page

1. Ouvrir une page Dolibarr (ex. fiche proposition commerciale)
2. Cliquer sur le bouton flottant (icône ampoule)
3. Section « Documentation wiki » : cliquer sur le lien (ex. `Propositions commerciales`)
4. La page wiki s'ouvre dans une dialog modale (iframe) sans quitter Dolibarr

### Cas 2 : Diagnostiquer les interactions de modules sur une page

1. Activer `INFRASHELPDESK_ENABLE_DEV_MODE` (page de paramètres)
2. Ouvrir la page à diagnostiquer, cliquer sur le bouton flottant
3. Lire : contextes de hook actifs, fonctions de hook de la page et, pour chacune, les modules externes qui l'implémentent
4. Si une interaction connue n'apparaît pas (hook indirect via classe core) : renseigner la fonction dans la colonne `hooks` de la ligne du dictionnaire de la page

### Cas 3 : Corriger l'URL wiki d'une page

1. *Accueil / Configuration / Dictionnaires* → dictionnaire du module
2. Rechercher la ligne par sa colonne `page`
3. Renseigner `url_page` (sous-chapitre, relatif pour le core) ou `url_wiki` (chapitre)
4. Pour une page partagée entre plusieurs vues : créer des lignes variantes `page?leftmenu=xxx` / `page?contextpage=xxx`
