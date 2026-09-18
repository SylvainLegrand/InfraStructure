# CLAUDE.md — Contexte module oblyon

## Aperçu (Overview)

`oblyon` est un module externe Dolibarr de thème graphique et de personnalisation de l'interface utilisateur :

- thème CSS complet remplaçant le thème Dolibarr par défaut (eldy),
- gestionnaire de menus personnalisé (top, left, inversé, réduit),
- personnalisation avancée des couleurs (menus, boutons, messages, dashboard, lignes),
- sélection de packs d'icônes FontAwesome (Free/Pro),
- options de layout (sticky bars, menu caché/réduit, effets slide/push),
- personnalisation du dashboard (couleurs infobox, activation/désactivation des blocs),
- éditeur CSS personnalisé intégré (avec support CKEditor/Ace),
- compatibilité Easya.

Informations module (issues du code et du changelog local) :

- Éditeur : Inovea Conseil (Alexandre Spangaro)
- Contributeur : InfraS (Sylvain Legrand)
- Numéro module : `432573`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `24.0.x`
- Compatibilité PHP : `7.1` à `8.4`
- Dernière version locale : `3.7.0` (2026-09)
- Dépendances obligatoires : aucune
- Conflits : `modQuickUX`
- Emplacement : `htdocs/custom/oblyon/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/oblyon/
├── CLAUDE.md
├── CHANGELOG.md
├── README.md
├── VERSION
├── license.txt
├── metapackage.conf
├── .easya_info.json
├── admin/
│   ├── about.php              # Page À propos / Support
│   ├── changelog.php          # Page Changelog (Parsedown)
│   ├── colors.php             # Configuration des couleurs (~100 constantes) + cartes des presets JSON (3.6.0)
│   ├── customcss.php          # Éditeur CSS personnalisé (Ace/CKEditor)
│   ├── dashboard.php          # Configuration du dashboard (infobox, blocs)
│   ├── icons.php              # Sélection pack FontAwesome
│   ├── menus.php              # Configuration des menus (inversé, réduit, effets, sous-menus en volets)
│   └── options.php            # Options générales (police, taille, comportement)
├── backport/
│   └── v21/                   # Backport fonctions Dolibarr v21
├── class/
│   └── actions_oblyon.class.php   # Hook class (contexte main, quasi vide)
├── config.php                 # Chargeur main.inc.php standard
├── core/
│   ├── menus/
│   │   └── standard/
│   │       ├── oblyon_menu.php     # Gestionnaire de menus (MenuManager)
│   │       └── oblyon.lib.php      # Bibliothèque menus (~2400 lignes)
│   └── modules/
│       └── modOblyon.class.php     # Descripteur module
├── css/
│   ├── oblyon.css             # CSS module admin
│   ├── as_style.min.css       # CSS minifié complémentaire
│   └── font.css               # Polices personnalisées
├── img/                       # Images (logos, thèmes, icônes FA)
├── includes/
│   └── parsedown/             # Bibliothèque Parsedown (Markdown → HTML)
├── js/
│   ├── oblyon.js              # JS module (mode tactile tap-to-toggle, décalage du contenu sous le menu haut inversé, volets de sous-menus, tiroir mobile + accordéon + bouton Filtres des listes — voir « js/oblyon.js »)
│   ├── jquery.ui.touch-punch.min.js  # Événements tactiles pour jQuery UI (glisser-déposer)
│   ├── pushy.js               # Effet push menu latéral
│   ├── jscolor.js             # Sélecteur de couleurs
│   └── range-slider.js        # Curseur de plage
├── langs/
│   ├── en_US/oblyon.lang
│   ├── fr_FR/oblyon.lang
│   ├── fr_FR/inovea.lang
│   └── fr_FR/oldauthors.lang
├── lib/
│   ├── oblyon.lib.php         # Bibliothèque admin (onglets, backup/restore, helpers HTML)
│   ├── oblyon_presets.lib.php # Presets JSON (3.6.0) : sections, chargement, appliquer, enregistrer sous, mettre à jour, supprimer, importer, contraste, cartes
│   ├── oblyon_colors.lib.php  # Couleurs par utilisateur (3.6.0) : oblyon_color_setting() (point de lecture unique du thème), liste des constantes de l'onglet, pastille
│   └── inovea_common.lib.php  # Fonctions communes Inovea (changelog Parsedown)
├── dev/
│   └── csscompare.php         # CLI (3.7.0) : comparaison sémantique de deux feuilles servies (valeur finale de chaque propriété par sélecteur), garde-fou avant / après toute modification du thème
├── presets/                   # Presets du module (3.6.0) : green, dark, blue, night, light (.json) + accessible.json (scope user : onglet utilisateur seulement)
├── sql/
│   ├── data.sql               # Constantes initiales (~200 INSERT, exhaustif ; preset « Oblyon Blue » par défaut)
│   └── update_3.2.0_oblyon_to_eldy.sql  # Migration OBLYON_* → THEME_ELDY_* (manuelle, à l'upgrade)
├── user/
│   └── colors.php             # Onglet « Couleurs » de la fiche utilisateur (3.6.0) : couleurs personnelles (llx_user_param)
└── themeoblyon/               # Répertoire du thème CSS
    ├── style.css.php          # Point d'entrée CSS (~400 lignes) : lecture des constantes, normalisation hex, include global.inc.php
    ├── global.inc.php         # Point d'entrée de la feuille (3.7.0, ~90 lignes) : inclut les 8 fichiers thématiques ci-dessous dans l'ordre, puis dropdown, touchmenu, flyoutmenu, info-box, progress, timeline, mobile, modules, puis la queue conditionnelle (THEME_ADD_BACKGROUND_ON_INPUT, THEME_SATURATE_RATIO, THEME_ELDY_USEBORDERONTABLE…)
    ├── core.inc.php           # 1. Styles par défaut : variables CSS :root, typographie, liens, champs, module website (inclut badges.inc.php)
    ├── tools.inc.php          # 2. Outil de scan, objets masqués, utilitaires
    ├── layout.inc.php         # 3. Structure de page, barre du haut, menu latéral, main-nav, bloc de connexion
    ├── cards.inc.php          # 4. Fiches : pictos (inclut main_menu_fa_icons), page de connexion (inclut login), onglets, boutons (inclut btn)
    ├── tables.inc.php         # 5. Listes et tableaux : titres, lignes, totaux, colonnes
    ├── widgets.inc.php        # 6. Calendrier, agenda, autocomplétion, édition en ligne, CKEditor, ACE, jNotify, blockUI, DataTables, Select2, multiselect
    ├── public.inc.php         # 7. Kanban, JMobile, POS, pages publiques, tickets, debugbar, copier-coller, cartes de visite, sondages, BookCal
    ├── fixes.inc.php          # 8. Options globales, en-têtes / colonnes / totaux collants (FIX_*), petits écrans
    ├── custom.css.php         # CSS personnalisé utilisateur
    ├── theme_vars.inc.php     # Variables du thème (couleurs, polices)
    ├── font.css               # Polices embarquées
    ├── manifest.json.php      # Manifeste PWA dynamique
    ├── graph-color.php        # Couleurs des graphiques
    ├── badges.inc.php         # Styles badges
    ├── btn.inc.php            # Styles boutons
    ├── dropdown.inc.php       # Styles dropdown
    ├── touchmenu.inc.php      # Styles mode tactile des menus (classe .is-touch-open)
    ├── flyoutmenu.inc.php     # Styles des sous-menus en volets (effet « flyout » du menu réduit, menus inversés)
    ├── mobile.inc.php         # Disposition mobile (3.5.0) : barre unique + tiroir sous 600 px, fiches, listes, formulaires, dialogues, tableau de bord, connexion
    ├── info-box.inc.php       # Styles infobox dashboard
    ├── login.inc.php          # Page de connexion seulement (.bodylogin, .login_table) ; sa copie périmée de la barre du haut a été retirée en 3.7.0
    ├── main_menu_fa_icons.inc.php  # Icônes FA menus
    ├── modules.inc.php        # Styles pages modules
    ├── modules/               # Extensions CSS modules externes
    │   ├── infrassearch.inc.php
    │   ├── mbicalls.inc.php     # Panneau des appels du module mbicalls sur les jetons du thème (2026-09)
    │   ├── quicklist.inc.php
    │   ├── relatedproducts.inc.php
    │   ├── scaninvoices.inc.php
    │   └── subtotal.inc.php
    ├── progress.inc.php       # Styles barres de progression
    ├── timeline.inc.php       # Fil de discussion des tickets (jetons OBLYON_COLOR_TIMELINE_*)
    ├── ckeditor/              # Configuration CKEditor propre au thème (config.js)
    ├── tpl/                   # Templates
    ├── img/                   # Images du thème
    └── fonts/                 # Polices du thème
```

## Descripteur module (Module descriptor : `modOblyon`)

Dans `core/modules/modOblyon.class.php` :

- **Module parts** :
	- `menus` : gestionnaire de menus Oblyon
	- `hooks` : contexte `main` (entité `0`, toutes les pages)
	- JS : `/oblyon/js/pushy.js`, `/oblyon/js/oblyon.js` (mode tactile)
	- CSS : `/oblyon/css/oblyon.css`, `/theme/oblyon/custom.css.php`, `/oblyon/css/font.css`
- **Dépendances** : aucune
- **Conflits** : `modQuickUX`
- **Répertoires de données** (`$this->dirs`, créés dans `DOL_DATA_ROOT` à l'activation) : `/oblyon/sql` (sauvegardes), `/oblyon/presets` (presets de l'instance, 3.6.0)
- **Onglets** (`$this->tabs`, enregistrés dans `MAIN_MODULE_OBLYON_TABS_0` à l'activation → désactiver / réactiver le module après une mise à jour par copie de fichiers) : `user:+oblyoncolors` = onglet « Couleurs » de la fiche utilisateur (3.6.0), condition `$user->hasRight('oblyon', 'usercolors')` (`$user` = le visiteur ; la page revérifie le droit avec `accessforbidden()`)
- **Dictionnaires** : aucun
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : un droit `oblyon` / `usercolors` (id `4325730` = numéro du module + `0`, libellé `Permission4325730` / `OblyonPermUserColors`, non attribué par défaut aux nouveaux utilisateurs) = « Régler ses couleurs personnelles » (onglet Couleurs de la fiche utilisateur). `rights_class = oblyon` (classe `modoblyon` en minuscules : ne pas renommer). Attribué aux admins à l'activation ; `user/perms.php` réinsère les droits manquants à chaque affichage mais ne les attribue pas. Les pages d'administration restent réservées aux administrateurs (`$user->admin`)
- **Menus** : aucun (gérés directement par le `MenuManager` Oblyon)

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/oblyon/sql/')`)
2. Restauration des constantes module (`oblyon_restore_module`)
3. Copie/mise à jour du thème `themeoblyon/` → `htdocs/theme/oblyon/`, **uniquement si `htdocs/VERSION` est absent** (Dolibarr standard) ; en LTS (`VERSION` présent) le thème est déjà livré → étape ignorée
4. Détection du répertoire FontAwesome le plus récent (`fontawesome-N`) et enregistrement dans `MAIN_FONTAWESOME_DIRECTORY`
5. Suppression des anciens fichiers menu manager du core (`core/menus/standard/oblyon_menu.php`, `oblyon.lib.php`)
6. Activation du thème Oblyon (`MAIN_THEME` = `oblyon`)
7. Restauration de `MAIN_MENU_INVERT` depuis sauvegarde
8. Suppression de `OBLYON_SHOW_COMPNAME` (incompatible menu inversé)
9. Migration de la constante kanban : `OBLYON_DISABLE_KANBAN_VIEW_IN_LIST` → `DISABLE_KANBAN_VIEW_IN_LIST`, puis suppression de l'ancienne

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- Sauvegarde module (`oblyon_bkup_module`)
- Restauration du thème par défaut (`MAIN_THEME` = `eldy`)
- Suppression du thème `htdocs/theme/oblyon/`, **uniquement si `htdocs/VERSION` est absent** (Dolibarr standard) ; ignorée en LTS
- Sauvegarde de `MAIN_MENU_INVERT` pour restauration future
- Nettoyage des constantes de menus forcés (`MAIN_MENU*_FORCED`)
- Nettoyage des constantes `THEME_ELDY_*` (couleurs Dolibarr)
- Nettoyage des constantes FontAwesome (`MAIN_FONTAWESOME_*`)

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `modOblyon.class.php` pour l'activation/désactivation du thème et la gestion du cycle de vie,
- `oblyon_menu.php` (`MenuManager`) pour le gestionnaire de menus complet (top + left),
- `oblyon.lib.php` (lib menus, ~2400 lignes) pour la construction des entrées de menus,
- `oblyon.lib.php` (lib admin) pour les onglets d'administration, backup/restore et helpers HTML,
- `inovea_common.lib.php` pour l'affichage du changelog via Parsedown,
- `actions_oblyon.class.php` pour les hooks (actuellement quasi vide, hook `addHtmlHeader` commenté),
- `themeoblyon/` pour l'ensemble du rendu CSS.

### Architecture du thème

Le thème est structuré en plusieurs couches :

1. **`style.css.php`** : point d'entrée principal, charge `theme_vars.inc.php` puis inclut tous les fichiers `.inc.php`
2. **`theme_vars.inc.php`** : lit les constantes `OBLYON_*` et `THEME_ELDY_*` pour définir les variables PHP utilisées dans le CSS
3. **`global.inc.php`** : point d'entrée de la feuille (3.7.0) : inclut dans l'ordre `core`, `tools`, `layout`, `cards`, `tables`, `widgets`, `public`, `fixes` (découpage de l'ancien fichier de 11 000 lignes, aux frontières de ses sections, feuille servie identique), puis les fichiers spécialisés
4. **Fichiers `.inc.php` spécialisés** : badges (inclus par core), main_menu_fa_icons / login / btn (inclus par cards), dropdown, touchmenu, flyoutmenu, info-box, progress, timeline, mobile, modules (inclus par global en fin)
6. **Règle « une règle, un endroit »** (3.7.0) : un sélecteur ne se définit qu'à un seul endroit pour un même contexte `@media` ; compléter une règle existante plutôt que la recopier plus loin. Motif : la copie tardive gagne la cascade et annule silencieusement les corrections faites sur la première (incidents 2026-09-18 : `.alogin:hover`, `div.login_block:hover:after` reprises par la copie de `login.inc.php`). Le lot 1 de 3.7.0 a retiré 160 copies identiques, 22 copies entièrement écrasées, 31 déclarations mortes, 30 règles vides et la copie de 250 lignes de `login.inc.php` ; les compléments partiels (une règle plus loin qui ajoute des propriétés) sont conservés. Chaque fichier porte un en-tête `Role / Inclus par / Garde / Regle`.
7. **Vérification** : `php dev/csscompare.php avant.css apres.css` sur deux feuilles servies enregistrées avec `curl` (paramètre `revision=` différent) : 0 différence attendue pour un refactor, seules les différences voulues pour une correction
5. **`custom.css.php`** : CSS personnalisé saisi par l'utilisateur (constante `OBLYON_CUSTOM_CSS`)

### Gestionnaire de menus

Le module remplace le gestionnaire de menus standard de Dolibarr :

- Classe `MenuManager` dans `oblyon_menu.php`
- Force les constantes `MAIN_MENU_STANDARD_FORCED`, `MAIN_MENUFRONT_STANDARD_FORCED`, `MAIN_MENU_SMARTPHONE_FORCED` → `oblyon_menu.php`
- Support du mode inversé (`MAIN_MENU_INVERT`) : le menu gauche passe en barre horizontale en haut
- Support du menu réduit (`OBLYON_REDUCE_LEFTMENU`) avec effets hover
- Support du menu caché (`OBLYON_HIDE_LEFTMENU`) avec effets slide/push (`OBLYON_EFFECT_LEFTMENU`)
- Support des **sous-menus en volets** (3.4.0) : troisième « effet d'ouverture » du menu réduit, `OBLYON_EFFECT_REDUCE_LEFTMENU = flyout` (à côté de `hover` et `only`), menus inversés uniquement. La barre du haut ne contient plus d'entrée de menu (`print_left_oblyon_menu(..., $onlyheader = 1)` n'imprime que logo/recherche/favoris) ; chaque icône de la barre latérale reçoit un volet `ul.oblyon-flyout` imprimé par `print_oblyon_flyout($idsel)` (appelée depuis `print_text_menu_entry()`), qui reconstruit l'arbre complet des sous-menus du module en appelant `print_left_oblyon_menu()` en mode silencieux (`$noout = 1`, `$forcemainmenu`, `$forceleftmenu = 'all'`, même mécanisme que le mode `jmobile`) puis le rend en listes imbriquées (`ul.oblyon-flyout__sub`, classes `has-children` et `is-active` = entrée dont la clé `leftmenu` est celle en session). Pas de titre dans le volet : le lien vers l'accueil du module reste sur l'icône. Le contexte (`tabMenu`, `type_user`) transite par `oblyon_flyout_context()`. Activation contrôlée par `oblyon_flyout_enabled()` : `MAIN_MENU_INVERT` + `OBLYON_REDUCE_LEFTMENU` + effet `flyout`, jamais sur petit écran ni avec `OBLYON_HIDE_LEFTMENU`. Coût mesuré : +5 ms par page (11 volets, 300 entrées). CSS dans `themeoblyon/flyoutmenu.inc.php` (variable `--oblyon-flyout` lue par `js/oblyon.js` : volet de niveau 1 en `position: fixed` quand la barre est collante, repli vers le haut / vers l'arrière en cas de débordement, tap-to-toggle en mode tactile)
- Bibliothèque complète des entrées de menus dans `oblyon.lib.php` (~2400 lignes) couvrant tous les modules Dolibarr

## Hooks et comportement (Hook behavior)

La classe `ActionsOblyon` (dans `class/actions_oblyon.class.php`) :

- Contexte déclaré : `main` (toutes les pages, entité `0`) ; les objets de ce contexte sont exécutés pour tous les hooks, quelle que soit la page
- `completeTabsHead()` (3.6.0) : sur la fiche utilisateur (`type = user`, `mode = add`), déplace l'onglet `oblyoncolors` ajouté en fin de liste par `complete_head_from_modules()` juste après l'onglet core `guisetup` (« Interface utilisateur ») ; `head` est reçu par référence dans `$parameters`, retour 0
- Hook `addHtmlHeader()` commenté (injectait le CSS personnalisé `OBLYON_CUSTOM_CSS`)
- La logique CSS personnalisé est désormais gérée directement par `custom.css.php` dans le thème

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. Toute la configuration est stockée dans `llx_const`.

Fichiers SQL (`sql/`) :

- `data.sql` : constantes initiales (~200), **exhaustif** (toutes les constantes lues par le thème sont semées) ; les couleurs par défaut correspondent au preset **« Oblyon Blue »**. Organisé en sections commentées (Menus, Couleurs, Tableau de bord, Options, Réglages complémentaires). `__ENTITY__` est remplacé par l'entité courante à l'exécution (`run_sql`). Ne sème qu'à l'installation (INSERT simples, doublons tolérés).
- `update_3.2.0_oblyon_to_eldy.sql` : **migration manuelle** (préfixe `update_` → non exécutée par `_load_tables`) recopiant les anciennes constantes `OBLYON_*` vers les nouvelles `THEME_ELDY_*` — voir « Migration de constantes (oblyon → eldy) ».

Le mécanisme de backup/restore sauvegarde/restaure les constantes du module dans `DOL_DATA_ROOT/<entity>/oblyon/sql/update.<entity>` ; la sauvegarde combine des préfixes LIKE et la liste exhaustive de `data.sql` (voir Notes techniques).

Fichiers de presets (3.6.0, hors base) : `presets/<clé>.json` dans le module (lecture seule, 5 fichiers livrés) et `DOL_DATA_ROOT/[<entity>/]oblyon/presets/<clé>.json` pour ceux de l'instance (créés par « Enregistrer sous » / « Importer », dossier créé à la volée par `dol_mkdir`). Format et sections : voir la note technique « Presets JSON ».

## Constantes de configuration (Key settings)

Le module utilise un grand nombre de constantes (~200, cf. `data.sql` exhaustif) organisées par catégorie :

### Menus

| Constante | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `MAIN_MENU_INVERT` | Menu inversé (horizontal) | `0` |
| `OBLYON_FULLSIZE_TOPBAR` | Barre supérieure pleine largeur | `0` |
| `MAIN_SHOW_LOGO` | Afficher le logo dans le menu | `0` |
| `OBLYON_STICKY_TOPBAR` → `THEME_STICKY_TOPMENU` | Barre supérieure collante (migrée, cf. groupe A) | `0` |
| `OBLYON_HIDE_TOPICONS` | Masquer les icônes du menu supérieur | `0` |
| `OBLYON_STICKY_LEFTBAR` | Menu gauche collant | `0` |
| `OBLYON_HIDE_LEFTMENU` | Masquer le menu gauche | `0` |
| `OBLYON_EFFECT_LEFTMENU` | Effet du menu caché (`slide`/`push`) | `slide` |
| `OBLYON_HIDE_LEFTICONS` | Masquer les icônes du menu gauche | `0` |
| `OBLYON_REDUCE_LEFTMENU` | Réduire le menu gauche | `0` |
| `OBLYON_EFFECT_REDUCE_LEFTMENU` | Effet du menu réduit : `only` (ne pas déployer), `hover` (déployé au survol), `flyout` (volets de sous-menus au survol, barre du haut sans menus, menus inversés uniquement) | `only` |
| `OBLYON_TOUCH_MENU` | Forcer le mode tactile des menus (tap-to-toggle) | `0` |
| `OBLYON_MOBILE_LAYOUT` | Disposition mobile (3.5.0) : sous 600 px de large, barre unique + tiroir des modules et contenu adapté, quel que soit le mode de menus ; l'agent utilisateur ne pilote plus le thème ni les menus. Absente = active (`getDolGlobalInt(..., 1)`) | `1` |

### Couleurs — Menus

- `OBLYON_COLOR_TOPMENU_BCKGRD`, `_BCKGRD_HOVER`, `_TXT`, `_TXT_ACTIVE`, `_TXT_HOVER`, `_BCKGRD_SEL`, `_TXT_SEL` (3.7.0 : entrée sélectionnée `li.tmenusel` / `.main-nav__item.tmenusel`, texte blanc codé en dur auparavant ; fond par défaut = `_BCKGRD_HOVER`, sinon `OBLYON_COLOR_MAIN`)
- `OBLYON_COLOR_LEFTMENU_BCKGRD`, `_BCKGRD_HOVER`, `_TXT`, `_TXT_ACTIVE`, `_TXT_HOVER` (les libellés `OBLYON_COLOR_LEFTMENU_BCKGRD_SEL` / `_TXT_SEL` n'existent que pour la permutation des libellés quand les menus sont inversés)

### Couleurs — Boutons

- Fond bouton d'action : `THEME_ELDY_BTNACTION` (ex-`OBLYON_COLOR_BUTTON_ACTION1`, migrée) et `OBLYON_COLOR_BUTTON_ACTION2`
- Texte bouton d'action : `THEME_ELDY_TEXTBTNACTION`
- Suppression : `OBLYON_COLOR_BUTTON_DELETE1`, `_DELETE2`

### Couleurs — Messages

- `OBLYON_COLOR_INFO_BORDER`, `_BCKGRD`, `_TEXT`
- `OBLYON_COLOR_WARNING_BORDER`, `_BCKGRD`, `_TEXT`
- `OBLYON_COLOR_ERROR_BORDER`, `_BCKGRD`, `_TEXT`
- `OBLYON_COLOR_NOTIF_*_BCKGRD`, `_TEXT` (info, warning, error)

### Couleurs — Options générales

- `OBLYON_COLOR_MAIN`, `_BCKGRD`, `_LOGO_BCKGRD`, `_LOGIN_BCKGRD`, `_OVERLAY_BCKGRD` (3.7.0 : fond des surfaces flottantes jusqu'ici blanches : barre de filtre des listes `.search_component_params`, modales `div.div-for-modal*`, survol du sélecteur de colonnes `.dropdown dd ul li a:hover`, édition en ligne `.editval_*`/`.viewval_hover`)
- ⚠️ `OBLYON_COLOR_MAIN` est aussi une **couleur de texte** (icônes de type de fichier, survol des onglets, `--oblyon-focus`) et le **fond des événements de l'agenda** (`table.cal_event`) : dans un preset sombre elle doit être claire (accent), pas une nuance du fond (incident fitantanana 2026-09-18 : `#303030` → agenda illisible). Depuis 3.7.0 le texte de survol des liens des menus et de la barre du haut suit `OBLYON_COLOR_TOPMENU_TXT_HOVER` / `LEFTMENU_TXT_HOVER` (11 règles de `global.inc.php`), plus `MAIN`
- `OBLYON_COLOR_BTITLE`, `_STITLE` (texte des titres migré vers `THEME_ELDY_TEXTTITLE`, ex-`OBLYON_COLOR_FTITLE`)
- `OBLYON_COLOR_BLINE`, `_FLINE`, `_FLINE_HOVER` (survol de ligne migré vers `THEME_ELDY_USE_HOVER`, + coché `THEME_ELDY_USE_CHECKED`, ex-`OBLYON_COLOR_BLINE_HOVER`)
- `OBLYON_COLOR_FDATE_DEFAULT`, `_FDATE_SELECTED` (le jour actif du calendrier est peint sur `THEME_ELDY_TOPMENU_BACK1`)
- `OBLYON_COLOR_CAL_EVENT_TXT`, `_CAL_WEEKEND_BCKGRD`, `_CAL_HOLIDAY_BCKGRD` (3.7.0, groupe « Agenda / calendriers » : texte des événements de l'agenda, fond des week-ends `td.weekend` et des congés `td.onholiday*` des feuilles de temps ; codés en dur `#111111` / `#eee` / `#f4eede` auparavant)
- `OBLYON_COLOR_AMOUNT_TEXT` (3.7.0, groupe Montants : texte de `span.amount`, codé en dur `#006666` auparavant), `_AMOUNT_REMAIN`, `_AMOUNT_PAID`, `_AMOUNT_UNPAID`
- `OBLYON_COLOR_STOCK_OK`, `_STOCK_LOW`, `_STOCK_EXIT` (3.7.0, groupe « Stock et mouvements » : colonne stock des lignes produit, flèches de mouvement ; codés en dur `#002000` / `#884400` / `#968822`)
- `OBLYON_COLOR_ICON_TEXT` (3.7.0, groupe Texte : pictos téléphone, mail, lien, corbeille, lecture, codés en dur `#440` / `#304` / `#555` / `#666` / `#444` dans `main_menu_fa_icons.inc.php` ; corbeille survolée = `--colorstatusdanger`)
- `OBLYON_COLOR_TIMELINE_BCKGRD`, `_TIMELINE_PRIVATE_BCKGRD` (3.7.0, groupe « Fil de discussion (tickets) », `timeline.inc.php` : fonds des messages ; textes, bordures, boutons et pastilles sur `--colortext`, `--oblyon-border*`, `--oblyon-neutral-bg`, `--oblyon-muted-text`)
- `OBLYON_COLOR_BADGE_DRAFT`, `_VALIDATED`, `_APPROVED`, `_WAITING`, `_ACTIVE`, `_CLOSED`, `_CANCELED`, `_ERROR`, `_DONE` (3.7.0, groupe « Badges de statut » : fond / bordure des familles `$badgeStatus*` de `theme_vars.inc.php`, rederivées dans `style.css.php` ; **texte calculé** par `oblyon_text_on()` (sombre `#1C1C1C` ou blanc selon le meilleur contraste), donc jamais à régler ; brouillon / fermé / en attente / actif (1b, 4b, 7, 10) restent en « bordure seule » sur le fond des lignes ; `.font-statusN` = couleur du statut)
- Jetons peints comme couleur CSS brute (les 22 ci-dessus) : lus par `oblyon_color_setting_hex()`, valeur `''` ou `#` = défaut du thème. `OBLYON_COLOR_FTITLE` (remplacée par `THEME_ELDY_TEXTTITLE`) n'est plus dans les presets
- `OBLYON_COLOR_TEXTTABACTIVE`, `_INPUT_BCKGRD`
- `OBLYON_COLOR_AUTOCOMPLETE_BCKGRD`, `_TEXT` (fond et texte de la ligne surlignée en autocomplétion produit — select2 `--highlighted` + autocomplétion jQuery UI `search-to-select`)
- `OBLYON_COLOR_RESULT_BCKGRD`, `_TEXT` (fond et texte des **étiquettes sélectionnées affichées dans le champ** multi-select — chips `.select2-selection__choice`, ex. tags/catégories ; le bouton × reprend la couleur du texte)
- `OBLYON_COLOR_CHIP_BCKGRD`, `_TEXT` (fond et texte des **options déjà sélectionnées dans la liste déroulante** de proposition — `.select2-results__option[aria-selected=true]`, remplacent le gris `#ddd` par défaut de select2)
- ⚠️ Noms contre-intuitifs (historique) : `RESULT_*` pilote les étiquettes **du champ**, `CHIP_*` pilote les options **déjà cochées de la liste**. Les options non sélectionnées de la liste gardent le rendu par défaut ; le survol reste géré par `OBLYON_COLOR_AUTOCOMPLETE_*`
- `OBLYON_COLOR_INFOBOX_BCKGRD1`, `_BCKGRD2`, `_BORDER_ACTIONCOLUMN`
- `THEME_INVERT_RATIO_FILTER`

### Couleurs — Dolibarr core (THEME_ELDY_*)

- `THEME_ELDY_TOPBORDER_TITLE1`, `_BACKTITLE1`, `_BACKTABACTIVE`, `_BACKTABCARD1` (fond de l'onglet actif d'une fiche, `.tabactive` ; ajouté à l'onglet Couleurs en 3.6.0)
- `THEME_ELDY_TOPMENU_BACK1`, `_VERMENU_BACK1`, `_BACKBODY`, `_TEXTTITLELINK` (groupe « Autres couleurs Dolibarr » de l'onglet Couleurs depuis 3.7.0, onglet utilisateur depuis 3.6.0 : jour actif du calendrier, listes select2, dialogues jQuery UI)
- **Format de stockage unique : `#RRGGBB`** (3.7.0). Les pages « Interface utilisateur » du core écrivent encore 14 `THEME_ELDY_*` en `r,g,b` : `oblyon_color_setting()` convertit à la lecture, `oblyon_colors_normalize_stored()` réécrit en base à l'ouverture des onglets Couleurs, les presets convertissent à l'import. `style.css.php` n'imprime plus de `rgb()` : les 20 couleurs Eldy sont normalisées par `oblyon_color_to_hex()` (repli `#585858` pour une valeur illisible, comme `colorStringToArray()`), `txt_color()` est alimentée par `oblyon_txt_color_hex()`. Ne jamais réintroduire de valeur `r,g,b` dans `data.sql`, les presets ou les défauts
- `THEME_ELDY_LINEPAIR1`, `_LINEPAIR2`, `_LINEIMPAIR1`, `_LINEIMPAIR2`, `_LINEBREAK`
- `THEME_ELDY_TEXTTITLENOTAB`, `_TEXTTITLE`, `_TEXT`, `_TEXTLINK`
- `THEME_ELDY_ENABLE_PERSONALIZED`

### Dashboard — Infobox

- `MAIN_DISABLE_GLOBAL_WORKBOARD`, `_GLOBAL_BOXSTATS`, `_METEO`
- `MAIN_DISABLE_BLOCK_*` (AGENDA, PROJECT, CUSTOMER, SUPPLIER, CONTRACT, BANK, ADHERENT, EXPENSEREPORT, HOLIDAY, TICKET, BOM)
- `THEME_INFOBOX_COLOR_ON_BACKGROUND`
- `OBLYON_INFOXBOX_SINGLE_WIDTH`
- `THEME_AGRESSIVENESS_RATIO`

### Dashboard — Couleurs infobox

- `OBLYON_INFOXBOX_BACKGROUND`, `_WEATHER_COLOR`
- `OBLYON_INFOXBOX_ACTION_COLOR`, `_PROJECT_COLOR`
- `OBLYON_INFOXBOX_CUSTOMER_PROPAL_COLOR`, `_ORDER_COLOR`, `_INVOICE_COLOR`
- `OBLYON_INFOXBOX_SUPPLIER_PROPAL_COLOR`, `_ORDER_COLOR`, `_INVOICE_COLOR`
- `OBLYON_INFOXBOX_CONTRAT_COLOR`, `_BANK_COLOR`, `_ADHERENT_COLOR`
- `OBLYON_INFOXBOX_EXPENSEREPORT_COLOR`, `_HOLIDAY_COLOR`, `_TICKET_COLOR`, `_MRP_COLOR`

### Options générales

| Constante | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `OBLYON_FONT_FAMILY` → `THEME_FONT_FAMILY` | Famille de police (migrée, cf. groupe A) | `Arial` |
| `OBLYON_FONT_SIZE` → `THEME_ELDY_FONT_SIZE1` | Taille de police (migrée, cf. groupe A) | `14` |
| `OBLYON_IMAGE_HEIGHT_TABLE` | Hauteur max des images dans les tableaux | `24` |
| `MAIN_MAXTABS_IN_CARD` | Nombre max d'onglets par fiche | — |
| `OBLYON_DISABLE_VERSION` | Masquer la version Dolibarr | `1` |
| `MAIN_STATUS_USES_IMAGES` | Utiliser des images pour les statuts | `0` |
| `MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN` | Menu rapide dropdown | `0` |
| `MAIN_USE_TOP_MENU_BOOKMARK_DROPDOWN` | Favoris dropdown | `0` |
| `OBLYON_PADDING_RIGHT_BOTTOM` | Padding en bas à droite | `1` |
| `MAIN_LOGIN_RIGHT` | Login à droite | `0` |
| `FIX_AREAREF_TABACTION` | Fixer la bannière de référence au scroll | `0` |
| `MAIN_CHECKBOX_LEFT_COLUMN` | Colonne de sélection à gauche | `0` |
| `FIX_STICKY_HEADER_CARD` | En-tête de tableau collant | `0` |
| `OBLYON_CUSTOM_CSS` | CSS personnalisé | — |
| `OBLYON_USER_COLORS` | **Paramètre utilisateur** (`llx_user_param`, pas `llx_const`) : `1` quand l'utilisateur a coché « Utiliser valeur personnalisée » dans l'onglet Couleurs de sa fiche (3.6.0) ; accompagné d'une ligne par constante de couleur (photo complète). Lu par `oblyon_color_setting()` | absent |
| `OBLYON_CURRENT_PRESET` | Clé du preset appliqué en dernier (3.6.0) : badge « actif » sur la carte, badge « modifié » si la base diffère du fichier. Écrite par `oblyon_apply_preset()` et « Enregistrer sous », semée par `oblyon_detect_current_preset()` à l'ouverture de l'onglet Couleurs si la base correspond exactement à un preset, supprimée avec le preset d'instance courant | `blue` (data.sql) |

### FontAwesome

| Constante | Description |
|-----------|-------------|
| `MAIN_FONTAWESOME_DIRECTORY` | Répertoire du pack FA (`/theme/common/fontawesome-N`) |
| `MAIN_FONTAWESOME_FAMILY` | Famille FA sélectionnée |
| `MAIN_FONTAWESOME_ICON_STYLE` | Style d'icônes (`fas`, `far`, `fal`, `fat`, `fad`) |
| `MAIN_FONTAWESOME_WEIGHT` | Poids de police FA (`100`-`900`) |

### CKEditor

- `FCKEDITOR_ALLOW_ANY_CONTENT`, `FCKEDITOR_ENABLE_SCAYT_AUTOSTARTUP`
- `MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML`
- `FCKEDITOR_SKIN` (3.5.0) : habillage de CKEditor (défaut `moono-lisa`, semé par `data.sql`). Sélecteur dans l'onglet Options, section K, construit à partir des dossiers `skins/` réellement installés (`public/includes/ckeditor/ckeditor/skins` à partir de Dolibarr 24, `includes/…` avant ; seuls les dossiers avec `editor.css`) ; affiché **seulement** s'il y a plusieurs habillages et que le moteur est CKEditor (`FCKEDITOR_EDITORNAME`). Une instance standard 22/23/24 ne livre que `moono-lisa` (pas de sélecteur) ; la LTS InfraS livre aussi `infras` et `moono`. Piège connu : la valeur `infras` semée par le module dolinfras sur un Dolibarr standard désigne un habillage absent → CKEditor reste invisible (`visibility: hidden` tant que l'habillage ne charge pas)

Point de vigilance : les constantes `OBLYON_*` sont très nombreuses (~80+) ; éviter les changements massifs sans test visuel.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4),
- pas de framework lourd / pas de Composer en core (Parsedown vendorisé dans `includes/`),
- entrées utilisateur via `GETPOST*` avec type approprié,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()`,
- protection XSS : `dol_escape_htmltag()` sur `$_SERVER['PHP_SELF']` dans les formulaires,
- validation whitelist sur les constantes modifiées via regex `set_(.*)`.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / thème CSS / menus / constantes :

1. Désactiver puis réactiver le module
2. Vérifier que le thème `oblyon` est bien actif (`MAIN_THEME`)
3. Vérifier les constantes de couleurs dans l'onglet Colors
4. Vider le cache navigateur (les CSS sont mis en cache)
5. Vérifier le rendu du menu (inversé / standard / réduit)
6. Vérifier le dashboard (couleurs infobox, blocs activés)
7. Vérifier la page de connexion
8. Si modification de `oblyon.lib.php` (menus) : tester toutes les entrées de menu principales

## Points d'attention (Watchpoints)

- Le thème est **servi depuis `htdocs/theme/oblyon/`** (Dolibarr résout `/theme/oblyon/style.css.php` via `dol_buildpath`, racine principale d'abord). Ce répertoire est une **copie** de la source éditable `custom/oblyon/themeoblyon/` : livré d'office en LTS, (re)créé par `init()` sur Dolibarr standard. ⚠️ Après édition de `themeoblyon/`, resynchroniser `theme/oblyon/` (sinon rien n'est servi) + vider le cache CSS (Ctrl+F5)
- La copie/suppression de `theme/oblyon/` par `init()`/`remove()` est **conditionnée à l'absence de `htdocs/VERSION`** : présent = LTS (thème pré-installé, on n'y touche pas) ; absent = Dolibarr standard (copie/suppression effectuées). Le user PHP‑FPM n'ayant pas les droits d'écriture dans `htdocs/theme/` en LTS, cette garde évite l'erreur de permission à l'activation
- Le fichier `global.inc.php` fait ~11000 lignes ; les modifications CSS doivent être ciblées
- La version est lue depuis le fichier `VERSION` à la racine du module (pas de `changelog.xml`)
- Le changelog est affiché via Parsedown (`CHANGELOG.md`)
- `css/oblyon.css` (cartes des presets, admin) est chargé par le core sans paramètre de révision : après modification, Ctrl+F5 côté navigateur (l'incrément de `MAIN_IHM_PARAMS_REV` ne couvre que `style.css.php`). Le JS du module porte la version dans son adresse (`oblyon.js?v=<version>`, liste stockée dans `MAIN_MODULE_OBLYON_JS` à l'activation) parce que le serveur met les `.js` en cache 30 jours
- Le module force le gestionnaire de menus (`MAIN_MENU*_FORCED` → `oblyon_menu.php`)
- La désactivation du module restaure le thème `eldy` et nettoie toutes les constantes de thème
- La sauvegarde (`oblyon_bkup_module`) couvre des préfixes LIKE (`OBLYON_%`, `THEME_%`, `MAIN_FONTAWESOME_%`, `FIX_*`, `MAIN_DISABLE_BLOCK_*`, …) **plus** la liste exhaustive des constantes déclarées dans `data.sql`
- L'extension CSS pour modules externes est dans `themeoblyon/modules/` (quicklist, scaninvoices, subtotal)
- Le backport `v21` contient des fonctions rétro-compatibles pour les anciennes versions de Dolibarr
- Compatibilité Easya : si `EASYA_VERSION >= 2024`, les versions min PHP/Dolibarr sont lues depuis `.easya_info.json`
- Le `config.php` remonte les répertoires parents pour trouver `main.inc.php` (compatibilité multi-déploiement)

## Dernières mises à jour (Recent updates)

- `3.7.0` (2026-09-18) : **jetons pour le sombre** issus de l'audit de contraste du preset d'instance `infras-dark` (fitantanana). 22 constantes `OBLYON_COLOR_AMOUNT_TEXT`, `_CAL_EVENT_TXT`, `_CAL_WEEKEND_BCKGRD`, `_CAL_HOLIDAY_BCKGRD`, `_OVERLAY_BCKGRD`, `_TOPMENU_BCKGRD_SEL`, `_TOPMENU_TXT_SEL`, `_STOCK_*` (3), `_ICON_TEXT`, `_TIMELINE_*` (2), `_BADGE_*` (9, texte calculé par `oblyon_text_on()`, `badges.inc.php`) (voir « Constantes de configuration ») remplaçant des couleurs codées en dur pour fond clair dans `global.inc.php` ; `oblyon_color_setting_hex()` ; `THEME_ELDY_LINEBREAK` / `_TEXTTITLELINK` normalisés dans `style.css.php` (hex imprimé dans `rgb()` = CSS invalide) ; `info-box.inc.php` lit `THEME_AGRESSIVENESS_RATIO` en base (forçait -50) ; contrôle de contraste étendu à 46 couples + 15 tuiles + détection des valeurs invalides (`0.0.0`…), `oblyon_contrast_issue_text()`, clé `OblyonPresetInvalidValue`, rapport aussi sur les couleurs en base dans l'onglet Couleurs ; **réorganisation du thème** : dédoublonnage (voir « Architecture du thème »), `global.inc.php` découpé en `core` / `tools` / `layout` / `cards` / `tables` / `widgets` / `public` / `fixes`, en-têtes uniformes, `dev/csscompare.php` ; **format unique hex** (`oblyon_color_to_hex()`, `oblyon_colors_normalize_stored()`, plus de `rgb()` dans `global.inc.php`) ; texte de survol des menus sur les jetons `*_TXT_HOVER` (plus `MAIN`) ; six presets du module recalibrés (0 alerte) et complétés des 16 couleurs de tuiles ; 16 clés en_US manquantes ajoutées. Fichiers : `themeoblyon/style.css.php`, `global.inc.php`, `info-box.inc.php`, `lib/oblyon_colors.lib.php`, `lib/oblyon_presets.lib.php`, `admin/colors.php`, `user/colors.php`, `sql/data.sql`, `presets/*.json` (version +1, texte des événements blanc quand la couleur principale est sombre), langs fr/en, `VERSION`, `CHANGELOG.md`
- `3.6.0` (2026-09), second lot : **couleurs par utilisateur** (voir la note technique « Couleurs par utilisateur »). Onglet « Couleurs » sur la fiche utilisateur (`user/colors.php`, déclaré par `$this->tabs`), stockage `llx_user_param` (drapeau `OBLYON_USER_COLORS` + photo complète des 116 couleurs), lecture du thème par `oblyon_color_setting()` (`lib/oblyon_colors.lib.php`, nouveau) dans `style.css.php`, `global.inc.php`, `info-box.inc.php` (121 lectures balisées), écriture inutile de `THEME_ELDY_ENABLE_PERSONALIZED` à chaque feuille retirée, 4 libellés Eldy ajoutés, chapitre `### User colors ###` des langs, CSS `.oblyon-color-swatch`, hook `completeTabsHead` (`class/actions_oblyon.class.php`) qui place l'onglet juste après « Interface utilisateur »
- `3.6.0` (2026-09) : **presets en fichiers JSON** (voir la note technique « Presets JSON »). Le tableau `$listtheme` de `admin/colors.php` (5 × 102 constantes) est remplacé par `presets/*.json` ; presets d'instance dans `documents/[entité/]oblyon/presets` ; cartes d'aperçu (capture `img/oblyon<clé>.png` pour les presets du module, dessin CSS pour ceux de l'instance) avec Appliquer / Annuler les modifications / Mettre à jour / Télécharger / Supprimer, formulaires « Enregistrer sous » et « Importer » ; un preset s'applique, se met à jour et s'enregistre toujours en entier (le choix de sections n'existe que dans la bibliothèque). Fichiers : `lib/oblyon_presets.lib.php` (nouveau), `admin/colors.php` (actions POST + jeton `apply_preset`, `save_preset`, `saveas_preset`, `delete_preset`, `import_preset`, GET `download_preset` ; cas `theme` du bloc `update_` supprimé), `css/oblyon.css` (cartes), `modOblyon.class.php` (`$this->dirs` + `/oblyon/presets`), `sql/data.sql` (`OBLYON_CURRENT_PRESET = blue`), langs (`OblyonPreset*`, `Oblyon<key>Desc`)
- `3.5.0` (2026-09) : **disposition mobile** (option `OBLYON_MOBILE_LAYOUT`, onglet Menus, active par défaut). Voir la note technique « Disposition mobile ». Fichiers : `themeoblyon/mobile.inc.php` (nouveau, inclus par `global.inc.php` avant `modules.inc.php`), `js/oblyon.js` (tiroir, accordéon, bouton Filtres des listes, garde `mobileActive()` sur les comportements bureau), `core/menus/standard/oblyon.lib.php` (`oblyon_mobile_nav_enabled()`, `oblyon_flyout_tree_enabled()`, bouton / en-tête / chevrons du tiroir ; `oblyon_flyout_enabled()`, `$usemenuhider` et le bouton pushy ignorent l'agent utilisateur quand l'option est active), `themeoblyon/style.css.php` (indicateur petit écran et `browser->layout` forcés à bureau pour la génération de la feuille), `core/modules/modOblyon.class.php` (version du module dans l'adresse des JS : le serveur met les `.js` en cache 30 jours), `admin/menus.php`, `sql/data.sql`, langs (`OblyonMobileLayout*`, `OblyonMobileMenu*`), `themeoblyon/modules/infrassearch.inc.php` (barre du haut + mobile). Règle CKEditor « picto menu masqué sous 768 px » retirée de `global.inc.php` (commentée, balise InfraS)
- `3.4.1` (2026-09) : **socle visuel moderne** appliqué à tous les presets sans nouvelle option (voir « Jetons de design » dans les notes techniques). Cartes arrondies + ombre légère (`div.tabBar`, `table.noborder` avec coins arrondis des cellules d'angle, popups, photo), onglets à liseré d'accent (doublons `a.tabTitle`/`td.tab`/`span.tabspan`/`div.tabsAction` supprimés), champs à bordure fine + anneau de focus couleur principale + `accent-color`, boutons/badges unifiés (`btn.inc.php` bloc `.button` mort réduit à la structure, `badges.inc.php` pilules), messages/infobulles/dropdowns sur les jetons, tableau de bord en cartes uniformes (`$borderwidth` 3 → 1 dans `theme_vars.inc.php`), page de connexion centrée avec fond `OBLYON_COLOR_LOGIN_BCKGRD` (variables `--login_bgcolor`/`--login_txtcolor`), typographie (pile système en repli, entrée « Police du système » = valeur `system-ui` dans `admin/options.php`, titres en `calc(var(--fontsize) …)`, `div.fiche { line-height: 1.45 }`), listes à densité unique compacte (`--oblyon-cell-py/px`, `--oblyon-row-lh`, `--oblyon-head-h`). Valeurs CSS invalides `#8888` / `#ffff` corrigées. Non traité (version suivante) : bloc dupliqué de ~590 lignes Markdown/JMobile/POS/Public/Ticket (`global.inc.php`, la seconde copie gagne)
- `3.4.0` (2026-09) : mode **sous-menus en volets** = troisième effet d'ouverture du menu réduit (`OBLYON_EFFECT_REDUCE_LEFTMENU = flyout`, onglet Menus, menus inversés + menu réduit). Voir « Gestionnaire de menus ». Fichiers : `core/menus/standard/oblyon.lib.php` (fonctions `oblyon_flyout_enabled()`, `oblyon_flyout_context()`, `oblyon_flyout_build_url()`, `print_oblyon_flyout()` ; paramètre `$onlyheader` de `print_left_oblyon_menu()` ; garde `empty($noout)` sur le bouton pushy et le nom de société, qui étaient imprimés même en mode silencieux), `oblyon_menu.php` (`showmenu('top')` passe `$onlyheader`), `themeoblyon/flyoutmenu.inc.php` (nouveau, inclus par `global.inc.php` après `touchmenu.inc.php`), `js/oblyon.js` (positionnement + tactile), `admin/menus.php` (3e bouton radio, libellé `OpenEffectReduce` enfin utilisé), langs fr/en (`EffectMicroMenuFlyout*`). Aucune nouvelle constante. Développement de test sur dolinfras24
- `3.3.1` (2026-07) : fix chevauchement du dropdown des boutons d'action (`dropdown.inc.php` : sélecteur `.dropdown-holder` sans son point initial, jamais appliqué ; `.dropdown-content` sans ancrage par défaut `bottom:0`/`transform:translateY(100%)` ni `z-index:5`, contrairement à eldy — le menu pouvait recouvrir les blocs « Fichiers joints » / « Derniers événements ») ; complément du correctif `FIX_ABSOLUTE_BUTTONS_ACTION_CARD` (`global.inc.php`) avec le `transform: translateY(-100%)` manquant pour que l'ouverture vers le haut de la barre d'action sticky fonctionne réellement
- `3.3.0` (2026-07) : regroupe l'ensemble des évolutions 2026-07 listées ci-dessous (options couleurs autocomplétion + multi-select, fix menu inversé sticky, fix fallback FontAwesome, migration constantes → eldy, consolidation CSS boutons, data.sql exhaustif). Version portée dans `VERSION` et `CHANGELOG.md`
- (2026-07) Migration des constantes propres au thème vers les constantes standard Dolibarr/Eldy (groupe A) : `OBLYON_FONT_FAMILY`→`THEME_FONT_FAMILY`, `OBLYON_FONT_SIZE`→`THEME_ELDY_FONT_SIZE1`, `OBLYON_STICKY_TOPBAR`→`THEME_STICKY_TOPMENU`, `OBLYON_COLOR_BUTTON_ACTION1`→`THEME_ELDY_BTNACTION`, `OBLYON_COLOR_FTITLE`→`THEME_ELDY_TEXTTITLE`, `OBLYON_COLOR_BLINE_HOVER`→`THEME_ELDY_USE_HOVER` (+ `_USE_CHECKED`) ; nouveau `sql/update_3.2.0_oblyon_to_eldy.sql`
- (2026-07) `data.sql` rendu **exhaustif** et **preset par défaut passé à « Oblyon Blue »**
- (2026-07) Consolidation du CSS des boutons dans `btn.inc.php` (`.button*`, `.butAction*` fusionnés, paiement, submit) ; retrait des blocs correspondants de `global.inc.php`
- (2026-07) Refactor : valeurs PHP réinjectées via variables CSS `:root` (`--fontsize`, `--colorButtonAction1/2`, `--colorTextButtonAction`, `--left`/`--right`, couleurs badges, etc.)
- (2026-07) Copie/suppression du thème réactivée dans `init()`/`remove()`, conditionnée à l'absence de `htdocs/VERSION`
- (2026-07) Migration de la constante kanban (`OBLYON_DISABLE_KANBAN_VIEW_IN_LIST` → `DISABLE_KANBAN_VIEW_IN_LIST`) dans `init()` + `admin/options.php`
- (2026-07) `oblyon_bkup_module` : sauvegarde exhaustive (préfixes LIKE + constantes de `data.sql`)
- (2026-07) `oblyon_restore_module` : sortie **silencieuse par défaut** (correction du `$silent` inversé de `run_sql`)
- (2026-07) Fix `Undefined variable $infras_radius` (calcul déplacé avant le bloc `:root`) — rétablit les arrondis
- (2026-07) Nouvelles options couleur `OBLYON_COLOR_AUTOCOMPLETE_BCKGRD` / `_TEXT` : pilotent le fond et le texte de la ligne surlignée en **autocomplétion produit** (select2) — remplacent le fond `var(--colorbackhmenu1)` et le texte `#fff` codés auparavant
- (2026-07) Règle CSS ciblée `.ui-autocomplete .ui-state-active` : applique aussi `OBLYON_COLOR_AUTOCOMPLETE_*` à l'**autocomplétion jQuery UI** (`PRODUIT_USE_SEARCH_TO_SELECT`), sans impacter les autres états `.ui-state-active`
- (2026-07) Nouvelles options couleur multi-select select2 (5 presets + data.sql + `oblyon_bkup_module()` via préfixe `OBLYON_%`) : `OBLYON_COLOR_RESULT_*` = **étiquettes sélectionnées dans le champ** (`.select2-selection__choice`, ex-fond info-box `var(--color1BckgrdInfobox)` sans couleur de texte) ; `OBLYON_COLOR_CHIP_*` = **options déjà sélectionnées dans la liste déroulante** (`.select2-results__option[aria-selected=true]`, remplace le gris `#ddd` de select2). Noms contre-intuitifs conservés (RESULT→champ, CHIP→liste) pour ne pas orpheliner les valeurs déjà en base ; mapping inversé après retour utilisateur, options non cochées de la liste laissées au rendu par défaut
- (2026-07) Fix fallback FontAwesome à la désactivation : `remove()` ne supprimait que `MAIN_FONTAWESOME_ICON_STYLE`/`_WEIGHT` (à `$conf->entity`), laissant `_DIRECTORY` et `_FAMILY` orphelines → le pack FA choisi (ex. `fontawesome-6p` Pro) restait actif au lieu de revenir au défaut natif du core (`/theme/common/fontawesome-5`, `fas`, `Font Awesome 5 Free`). Corrigé : suppression des **4** constantes `MAIN_FONTAWESOME_*` sur **toutes les entités** (`dolibarr_del_const(..., -1)`, le filtre entité n'est appliqué que si `entity >= 0`). NB : `init()` pose volontairement `_DIRECTORY` (et `style.css.php` `_FAMILY`) à l'**entité 0** = défaut global, écrasé par l'override par entité d'`icons.php` (précédence `conf.class.php` : `entity IN (0, courante) ORDER BY entity`) — ne pas basculer `init()` sur `$conf->entity` sous peine d'écraser le choix admin à chaque réactivation
- (2026-07) Fix menu inversé (`MAIN_MENU_INVERT` + `THEME_STICKY_TOPMENU`) : quand la barre haute `#tmenu_tooltipinvert` (`position: fixed`) déborde sur plusieurs lignes, le contenu (`#id-left`, `#id-right`→`.fiche`) gardait un `padding-top` codé en dur (40/52px) → haut du contenu masqué. Correctif **JS** dans `js/oblyon.js` : recalcul du `padding-top` de `#id-left`/`#id-right` selon la hauteur réelle de la barre (au chargement + `load` + `resize`). Le CSS n'est pas touché (clipper la barre est exclu : `overflow` couperait aussi les sous-menus `.sec-nav__sub-list` en `position: absolute` qui débordent sous la barre)
- `3.2.0` (2026-06) : mode tactile des menus (tap-to-toggle) — corrige le repli incontrôlé des dropdowns sur écran tactile (dépendance au `:hover`). Auto-détection + option `OBLYON_TOUCH_MENU` ; nouveaux fichiers `themeoblyon/touchmenu.inc.php` et `js/oblyon.js`
- `3.1.0` (2025-11) : compatibilité Dolibarr v21/v22/v23
- `3.1.0` (2025-11) : ajout de l'onglet « Icons » pour sélection du pack FontAwesome
- `3.1.0` (2025-11) : option de changement de famille de police (`OBLYON_FONT_FAMILY`)
- `3.1.0` (2025-11) : séparation des options de couleur titres principaux/titres de lignes
- `3.1.0` (2025-11) : suppression du CSS Cashdesk, passage à Dolibarr v18 minimum
- `3.1.0` (2025-11) : CSS fixes divers, déplacement menu catégories (v22 → outils)
- `3.0.6` (2024-09) : fix CSS badges, `FIX_AREAREF_TABACTION`, `print_oblyon_menu` avec `$noout=1`
- `3.0.5` (2024-09) : fix Z-index, ajout landing page spécifique, fix dropdown action
- `3.0.4` (2024-07) : CSS drag & drop, fix ordres menu, détection Easya `.easya_info.json`
- Entrées du changelog par version au format Keep a Changelog

## Notes techniques (Technical notes)

### Couleurs par utilisateur (3.6.0)

Besoin : utilisateurs daltoniens ou malvoyants. Chaque utilisateur (droit `user/self/write`) ou un gestionnaire d'utilisateurs (`user/user/write`, admin) règle des couleurs propres à un compte depuis l'onglet **Couleurs** de la fiche utilisateur (`user/colors.php`), présenté comme l'onglet core « Interface utilisateur » : tableau Paramètre / Valeur par défaut / case « Utiliser valeur personnalisée » (première ligne), une ligne par constante (groupes de `oblyon_user_colors_list()` = ceux de `admin/colors.php` + vignettes du tableau de bord + « Autres couleurs Eldy »), champs `jscolor` grisés tant que la case n'est pas cochée, bouton Modifier, `Form::buttonsSaveCancel()`.

- **Stockage** : `llx_user_param` via `dol_set_user_param($db, $conf, $object, $tab)` (4 arguments : signature 22 LTS). Case cochée → `OBLYON_USER_COLORS = 1` et **toutes** les couleurs (valeurs postées normalisées `#RRGGBB`, sinon valeur de l'instance : photo complète, l'utilisateur devient indépendant des presets appliqués ensuite à l'instance). Case décochée → `''` partout = lignes supprimées. Puis `MAIN_IHM_PARAMS_REV` + 1 (l'adresse de la feuille porte déjà `&userid=` et `&revision=` : cache navigateur par utilisateur, rechargé après enregistrement) et redirection.
- **Lecture** : `oblyon_color_setting($name, $default, $user)` (`lib/oblyon_colors.lib.php`, inclus par `style.css.php` juste après le backport) remplace `getDolGlobalString()` sur les 121 lectures de couleur de `style.css.php` (82), `global.inc.php` (21, dont le bloc `:root` l. 76-91) et `info-box.inc.php` (18). Valeur personnelle si `oblyon_user_colors_enabled()` (drapeau, ou ancien paramètre utilisateur `THEME_ELDY_ENABLE_PERSONALIZED`) **et** forme valide (`#hex`, `#` seul = hériter, `r,g,b`) ; sinon instance. Le core `user/param_ihm.php` écrit encore `THEME_ELDY_USE_HOVER/USE_CHECKED = 0/1` et `TOPMENU_BACK1/BACKTITLE1 = r,g,b` dans `llx_user_param` à chaque enregistrement : `0`/`1` ne sont pas des couleurs pour Oblyon → repli sur l'instance ; le prochain enregistrement de l'onglet Couleurs remet des hexadécimaux. `style.css.php` recharge `$user` depuis `$_SESSION['dol_login']` (l. 80-88) avant toute lecture ; sans session (connexion, mot de passe) → couleurs de l'instance.
- **Ratios exclus** du périmètre utilisateur : `THEME_INVERT_RATIO_FILTER`, `THEME_SATURATE_RATIO`, `THEME_AGRESSIVENESS_RATIO`.
- **Formes acceptées** (`oblyon_color_is_valid($value, $name)`) : `#RRGGBB` à 6 chiffres ou `#` seul pour toutes les constantes ; `r,g,b` (0-255) seulement pour celles que le thème normalise avec `colorStringToArray()` (`oblyon_colors_rgb_allowed()`). Un champ vide de l'onglet reprend la valeur de l'instance (une instance vide = contraste automatique, jamais stocké `#`). Décocher la case efface aussi l'ancien drapeau core `THEME_ELDY_ENABLE_PERSONALIZED`, que l'onglet et le thème honorent.
- **Contraste** : sous le tableau, `oblyon_check_preset_contrast(array('colors' => photo))` liste les couples < 4,5 de la palette personnelle.
- **Droit** : l'onglet exige `oblyon` / `usercolors` (condition de l'onglet et garde `accessforbidden()` en tête de page, message `OblyonUserColorsNoRight`) en plus des règles de la fiche (`user/self/write` sur soi, `user/user/write` sur autrui).
- **Presets de l'onglet** (section « Presets de couleurs » au-dessus du tableau, `oblyon_print_user_preset_cards()`) : presets du module de `scope` vide (les 5 Oblyon) puis de `scope = user` (`presets/accessible.json`, « Oblyon Accessibilité », palette Okabe-Ito sur fond clair, jamais proposé dans l'onglet Couleurs du module), puis les presets personnels de l'utilisateur (`DOL_DATA_ROOT/oblyon/userpresets/<id>/<clé>.json`, `$conf->oblyon->dir_output`, dossier créé par `dol_mkdir` à la première écriture, donc par PHP-FPM : ne pas le créer à la main). « Appliquer à mes couleurs » (`apply_user_preset`) = `oblyon_apply_preset_to_user()` : photo complète des 116 constantes (couleur du preset si présente dans ses sections `colors` / `dashboard`, sinon valeur de l'instance) + drapeau + révision CSS. « Enregistrer mes couleurs actuelles comme preset » (`save_user_preset`) = `oblyon_save_user_preset()` : couleurs affichées (`oblyon_user_current_colors()`), sections `colors` + `dashboard` seulement, clé validée, refus des clés du module (-3) et des doublons (-4), écriture atomique. `delete_user_preset`, `download_user_preset` (GET + jeton, fichier du dossier de l'utilisateur seulement). Les presets d'instance ne sont pas proposés ici.
- **Test hors ligne** : scratchpad `oblyon_user_colors_test.php` (modes `user` / `set` / `css <login>` / `clean`, une génération de feuille par processus car `style.css.php` déclare des fonctions) enchaîné par `user_colors_test.sh <htdocs> <base>` ; `oblyon_user_page_test.php <htdocs> <id> [edit]` rend l'onglet.
- Piste : « enregistrer mes couleurs comme preset » = `oblyon_write_preset_file()` avec la photo utilisateur à la place de `oblyon_presets_current_values()`.

### Presets JSON (3.6.0)

Un preset = un fichier `<clé>.json` (`clé` : `[a-z0-9][a-z0-9_-]{1,39}`) : `{ "name", "description", "author", "version", "scope" (facultatif : `user` = proposé seulement sur l'onglet Couleurs de la fiche utilisateur), <sections> }`. `name`/`description` sont des clés de langue quand elles existent (`Oblyon<key>`, `Oblyon<key>Desc` pour le module), sinon du texte brut (presets d'instance). Sections facultatives, chacune avec sa liste blanche dans `oblyon_presets_sections()` : `colors` (`OBLYON_COLOR_*`, `THEME_ELDY_*` de couleur, `THEME_INVERT_RATIO_FILTER`, `THEME_SATURATE_RATIO`), `typography`, `menus`, `general`, `lists_cards`, `dashboard` (`MAIN_DISABLE_BLOCK_*`, `OBLYON_INFOXBOX_*`…), `custom_css` (valeur scalaire = `OBLYON_CUSTOM_CSS`). Exclus volontairement : `MAIN_FONTAWESOME_*` (dépend des dossiers installés, écrit à l'entité 0), `FCKEDITOR_*`, `MAIN_SECURITY_*`, menus forcés. Conventions conservées : `#` = hériter, `''` = supprimer la constante (`dolibarr_set_const`). Les couleurs hex sont normalisées en majuscules à la lecture.

- Dossiers : module `presets/` (lecture seule) puis instance `DOL_DATA_ROOT/[<entité>/]oblyon/presets` (même logique d'entité que la sauvegarde `oblyon_bkup_module`) ; un preset d'instance de même clé remplace celui du module. Cache statique par requête, `oblyon_get_presets_reset()` après écriture.
- « Modifié » = comparaison base ↔ fichier sur les sections du preset (`oblyon_preset_modified_sections()`, constante absente en base = `''`). `OBLYON_CURRENT_PRESET` désigne le preset courant ; si elle manque (instance mise à jour par copie de fichiers), `oblyon_detect_current_preset()` (appelée à l'ouverture de l'onglet Couleurs) la sème avec le premier preset identique à la base, sinon aucune carte n'est « Actuel ». Le bouton Enregistrer du formulaire des couleurs n'écrit jamais de fichier ; seuls « Enregistrer sous » (nouveau preset d'instance) et « Mettre à jour » (preset d'instance existant, version +1) écrivent.
- Application : une transaction, `dolibarr_set_const` par constante (note `Oblyon preset <clé>`), `oblyon_apply_menu_rules()` si la section `menus` est appliquée (mêmes règles que `admin/menus.php`), `OBLYON_CURRENT_PRESET`, `MAIN_IHM_PARAMS_REV` + 1. Codes retour : 1, -1 (rien écrit), -2 inconnu ; export : -2 clé invalide, -3 clé du module, -4 doublon ; import : -5 fichier invalide (256 Ko max).
- Contraste : `oblyon_check_preset_contrast()` (luminance WCAG 2, 52 couples texte/fond tels que le thème les peint (21 d'origine + 31 en 3.7.0 : stock, pictos et fil de discussion sur `BLINE` / `TEXT`, `MAIN` en icônes de type de fichier / texte de survol des onglets (seuil propre 3) et en fond des événements agenda, montants sur `BLINE`, `FLINE_HOVER` sur `USE_HOVER`/`USE_CHECKED`, notifications, natures, adhérents, entrée de menu sélectionnée, surfaces flottantes / week-ends / congés sous `THEME_ELDY_TEXT`, `TEXTTITLENOTAB` sur `BCKGRD`, `FDATE_SELECTED` sur `TOPMENU_BACK1`, `TEXT` sur `BACKBODY`) + 15 tuiles du tableau de bord (couleur du module en icône sur `BLINE`, ou sous une icône blanche si `THEME_INFOBOX_COLOR_ON_BACKGROUND` ; la section `dashboard` est fusionnée pour ce contrôle) + valeurs de couleur illisibles par le thème (`0.0.0`, `25.5.45`… → entrée `invalid`, libellé `OblyonPresetInvalidValue`). Les six presets du module passent le contrôle étendu (2026-09-18) et portent désormais les 16 couleurs de tuiles : `FLINE` sur `BLINE` et sur l'onglet actif `BACKTABCARD1`, `THEME_ELDY_TEXT` sur les lignes `LINEPAIR1`/`LINEIMPAIR1` et les champs `INPUT_BCKGRD`, `TEXTLINK` sur lignes et cartes, `TEXTTITLE` sur `BTITLE`, menus, boutons, messages, select2 ; seuil 4,5), avertissement sur la carte seulement. Les cinq presets du module passent le contrôle (version 2, 2026-09) ; Night est entièrement sombre (lignes, champs, onglet actif). Deux règles de `global.inc.php` hors bandeau de titre (crayon d'édition au survol, `.alilevel0`) utilisent `--colortext` et non `--colortexttitle`, ce qui autorise un texte de bandeau blanc.
- Les presets du module sont générés depuis l'ancien tableau par un script jetable avec contrôle d'égalité ; pour en ajouter un, déposer un fichier dans `presets/` (et ses clés de langue).
- Sécurité (audit 2026-09) : `oblyon_preset_value_is_valid()` valide chaque valeur importée selon son type (couleur `#RRGGBB` / `#RGB` / `#` / `r,g,b`, nombre, texte libre sans `<>"'\` ni caractère de contrôle, `custom_css` sans `<`) car les valeurs finissent dans la feuille CSS et dans les champs de l'onglet Couleurs ; `oblyon_print_input()` échappe la valeur des champs. Écriture des fichiers atomique (`.tmp` + `rename`), détection du preset courant une fois par session (`$_SESSION['oblyon_current_preset_checked']`), clé refusant un `\n` final.

### Disposition mobile (3.5.0)

Principe : **la largeur d'écran décide, jamais l'agent utilisateur**. Toute la disposition est dans `themeoblyon/mobile.inc.php`, en `@media (max-width: 600px)` (téléphone) et `(max-width: 900px)` (tablette : colonnes de fiche empilées, boutons repliés). Le core décide « téléphone » d'après le navigateur (`$conf->browser->layout`, `$conf->dol_optimize_smallscreen`) : quand l'option est active, `style.css.php` force ces deux indicateurs à « bureau » pour la génération de la feuille (les ~30 branches PHP de `global.inc.php` produisent la feuille bureau) et `oblyon.lib.php` ne s'en sert plus pour le bouton pushy, les sous-menus dans la barre ni la désactivation des volets. Ce que le core imprime lui-même selon l'agent (une seule tête d'onglet, loupe de prévisualisation absente, lien « Rechercher… ») reste du core ; les onglets regroupés sont remis dans la bande par CSS.

- **Barre et tiroir** : `#id-top` devient une barre fixe de 48 px (bouton `.oblyon-mnav-btn` imprimé par le gestionnaire de menus, logo, icônes du bloc de connexion) ; `nav.main-nav` (dans la barre en menus classiques, dans la colonne gauche en menus inversés) devient le tiroir (`position: fixed`, `body.oblyon-mnav-open`), avec en-tête `.oblyon-mnav-head` et, sous chaque module, l'arbre `ul.oblyon-flyout` du mode volets (imprimé dès que l'option est active, `oblyon_flyout_tree_enabled()`) rendu en accordéon (`.oblyon-mnav-toggle`, classe `.is-mobile-open`). En menus classiques le JS déplace recherche et favoris de la colonne gauche dans le tiroir. `html, body { overflow-x: hidden }` : sans cela un contenu plus large élargit la fenêtre de rendu mobile et la barre fixe suit.
- **Fiches** : bandeau en grille (navigation / photo + référence / statut), onglets en bande défilante (`+N` du core réinjecté), tableaux `tableforfield*` en blocs (sélecteurs `div.fiche table…` pour l'emporter sur la hauteur 40 px forcée sous 570 px), boutons d'action empilés (liste déroulante fixée en bas), `#tablelines` et tableaux ≥ 6 colonnes à 640 px minimum dans leur cadre, colonne description 320 px (CKEditor), bloc `table.liste.formdoc` exclu des règles de liste.
- **Listes** : `overflow-x: auto !important` sur `div-table-responsive` (le thème l'annule partout ailleurs), première colonne collante (fond page + dégradé de rayure hérité : les rayures sont des `background-image` sur la ligne), cellules `nowrap` avec coupure, ligne de filtres repliée derrière un bouton inséré par `js/oblyon.js` (libellé via `--oblyon-lbl-filters`).
- **Formulaires / dialogues** : champs `.flat` des tableaux de saisie à 100 % (exceptions dates, montants, heures), 16 px (pas de zoom iOS), `div.center:has(.button)` en flex ; `.ui-dialog:not(.highlight):not(.select2-dropdown)` en feuille ancrée en bas (le core donne la classe `ui-dialog` aux listes select2 : exclusion indispensable) ; jNotify pleine largeur.
- **Tableau de bord / connexion** : vignettes en flex sur une colonne (icône étirée), colonnes de widgets empilées, carte de connexion pleine largeur (colonnes `#login_left/right` et champ plafonné à 140 px du thème neutralisés).
- **Jetons** : `--oblyon-mobile` (lu par le JS), `--oblyon-mobile-bp` (600), `--oblyon-mobile-bar-h` (48px), `--oblyon-touch-target` (44px), `--oblyon-mobile-gutter` (12px).
- **Test hors ligne** : le rendu se vérifie sans session avec des pages reconstruites (`oblyon_local_build.php` + `build_local.py` + `shot.js` du scratchpad de session : page + CSS générées avec constantes forcées, ressources téléchargées, capture Puppeteer à 400 / 800 / 1280 px). Les anciens points de rupture du thème (570/767/768/905/1000/1024/1170/1200/1400) sont conservés : ils servent les largeurs 600–1400 px du bureau.

### Jetons de design (3.4.1)

Déclarés dans le bloc `:root` de `global.inc.php` (fin du bloc, balise `InfraS add`), calculés dans `style.css.php` juste avant l'inclusion de `global.inc.php` :

| Jeton | Valeur | Usage |
|---|---|---|
| `--oblyon-radius`, `-radius-sm`, `-radius-pill` | `--infras_radius` (option « Rayon des arrondis », forcée à 6 si 0), sa moitié, 999px | cartes, tableaux, champs, boutons, badges |
| `--oblyon-shadow-sm/md/lg` | 3 niveaux d'ombre neutres | cartes / survol et popups / dropdowns, notifications, carte de connexion |
| `--oblyon-border`, `-border-strong`, `-neutral-bg`, `-muted-text` | mélanges fond des lignes ↔ texte des lignes (14 %, 30 %, 5 %, 40 %) via `oblyon_mix_colors()` | séparateurs, cadres, fonds discrets, textes secondaires — corrects en preset sombre comme clair |
| `--oblyon-input-border` | `-border` ou `-border-strong` selon `THEME_SHOW_BORDER_ON_INPUT` | bordure des champs et select2 (toujours complète, plus marquée avec l'option) |
| `--oblyon-focus`, `--oblyon-transition` | couleur principale, `.15s ease-in-out` | focus (bordure + halo + `:focus-visible`), transitions |
| `--login_bgcolor`, `--login_txtcolor` | `OBLYON_COLOR_LOGIN_BCKGRD`, texte blanc ou texte des lignes selon la clarté du fond | page de connexion |
| `--oblyon-cell-py/px`, `--oblyon-row-lh`, `--oblyon-head-h` | 5px / 8px, 1.5em, 34px | densité unique des tableaux (`table.noborder`, `table.liste`, `.pair/.impair`, `dataTable`, en-têtes de liste) |

Règles : toute nouvelle règle CSS du thème doit consommer ces jetons plutôt qu'une valeur en dur (gris, rayon, ombre) ; les couleurs « métier » restent celles des constantes du preset. Après édition de `themeoblyon/`, resynchroniser `htdocs/theme/oblyon/` (`rsync -rlt --inplace`).

### Mécanisme de thème

Le descripteur force `MAIN_THEME=oblyon`. Dolibarr construit `$conf->css = "/theme/oblyon/style.css.php"` et le résout via `dol_buildpath(..., 1)`, qui teste **d'abord la racine principale** → c'est donc `htdocs/theme/oblyon/` qui est servi, **pas** la source `custom/oblyon/themeoblyon/`. Le décalage de nom (`themeoblyon` ≠ `theme/oblyon`) empêche le mécanisme « alt‑root » de servir la source directement : un `theme/oblyon/` physique est requis (fourni en LTS, ou (re)créé par `init()` sur Dolibarr standard). La source `themeoblyon/` reste le répertoire à éditer, puis à resynchroniser vers `theme/oblyon/`.

### Flux de chargement CSS

```
Dolibarr charge le thème actif
    ↓
style.css.php est appelé (NOLOGIN, NOCSRFCHECK, NOTOKENRENEWAL)
    ↓
theme_vars.inc.php lit les constantes OBLYON_* et THEME_ELDY_*
    → Définit les variables PHP ($colorbackhmenu1, $fontlist, etc.)
    ↓
global.inc.php inclut dans l'ordre (3.7.0) :
    → core.inc.php (variables CSS :root, styles par défaut ; inclut badges.inc.php)
    → tools, layout, cards (inclut main_menu_fa_icons, login, btn), tables, widgets, public, fixes
    → dropdown, touchmenu, flyoutmenu, info-box, progress, timeline, mobile, modules (CSS des modules tiers activés)
    → queue conditionnelle (fond des champs, saturation des tuiles, bordures des tables…)
    ↓
custom.css.php injecte le CSS personnalisé (OBLYON_CUSTOM_CSS)
```

### Mécanisme de backup/restore

Le module implémente un système de sauvegarde/restauration des constantes :

1. **Sauvegarde** (`oblyon_bkup_module`) : génère un dump `INSERT ... ON DUPLICATE KEY UPDATE` dans `DOL_DATA_ROOT/<entity>/oblyon/sql/update.<entity>`. La sélection combine des **préfixes LIKE** (`OBLYON_%`, `THEME_%`, `MAIN_FONTAWESOME_%`, `FIX_*`, `MAIN_DISABLE_BLOCK_*`, `MAIN_USE_TOP_MENU_%`, …) **et** la **liste exhaustive lue dans `data.sql`** (clause `name IN (…)`) → toute constante du module est sauvegardée.
2. **Restauration** (`oblyon_restore_module`) : exécute le fichier via `run_sql()`, **silencieux par défaut** (`$silent = !MAIN_DISPLAY_SQL_INSTALL_LOG`) ; activer `MAIN_DISPLAY_SQL_INSTALL_LOG` pour afficher le détail SQL.
3. Une copie horodatée est conservée dans `DOL_DATA_ROOT/<entity>/admin/`.

### Migration de constantes (oblyon → eldy)

Certaines constantes propres au thème ont été remplacées par les constantes standard Dolibarr/Eldy (réutilisation du socle natif) :

| Ancienne (`OBLYON_*`) | Nouvelle | Portée |
|---|---|---|
| `OBLYON_FONT_FAMILY` | `THEME_FONT_FAMILY` | police |
| `OBLYON_FONT_SIZE` | `THEME_ELDY_FONT_SIZE1` | taille de police |
| `OBLYON_STICKY_TOPBAR` | `THEME_STICKY_TOPMENU` | barre haute collante |
| `OBLYON_COLOR_BUTTON_ACTION1` | `THEME_ELDY_BTNACTION` | fond bouton d'action |
| `OBLYON_COLOR_FTITLE` | `THEME_ELDY_TEXTTITLE` | texte des titres |
| `OBLYON_COLOR_BLINE_HOVER` | `THEME_ELDY_USE_HOVER` (+ `_USE_CHECKED`) | survol / coché des lignes |
| `OBLYON_DISABLE_KANBAN_VIEW_IN_LIST` | `DISABLE_KANBAN_VIEW_IN_LIST` | masquage vue kanban |

- Le code lit désormais les constantes de droite (repli sur les défauts de `theme_vars.inc.php`).
- `sql/update_3.2.0_oblyon_to_eldy.sql` recopie l'ancienne valeur vers la nouvelle à l'upgrade d'une instance existante (**manuel** : `mariadb … alarmexpo < sql/update_3.2.0_oblyon_to_eldy.sql`).
- La constante kanban est migrée automatiquement par `init()` et à l'ouverture de `admin/options.php`.

### CSS des boutons (consolidé dans `btn.inc.php`)

Tout le CSS des boutons est regroupé dans le fichier dédié `btn.inc.php` : boutons génériques `.button*`, boutons d'action `.butAction*` (fusion de l'ancienne implémentation de `global.inc.php`), boutons form/submit (`input.button`, `input[type=submit]`) et boutons de paiement (`.buttonpayment*`). Les couleurs passent par les variables CSS `--colorButtonAction1/2`, `--colorTextButtonAction`, `--colorButtonDelete1/2` (issues des constantes migrées). Restent en place (contextuels, non déplacés) : `.websitebar .button`, `.searchpage .button`, `.liste_titre input[type=submit]`, `th .button`, `.ui-state-*`, `cke_*`. Les boutons de paiement conservent un style propre (`#9999bb`, page publique `public/payment/newpayment.php`).

### Gestionnaire de menus (MenuManager)

Le `MenuManager` Oblyon remplace le gestionnaire standard de Dolibarr :

- **Classe** : `MenuManager` dans `core/menus/standard/oblyon_menu.php`
- **Chargement** : `loadMenu()` charge les menus depuis la base via `Menubase` et `require_once` la bibliothèque `oblyon.lib.php`
- **Rendu top** : `showmenu()` génère le menu horizontal supérieur avec support dropdown
- **Rendu left** : affiche le menu latéral avec support des niveaux 0-3
- **Bibliothèque** : `oblyon.lib.php` (~2400 lignes) définit toutes les entrées de menus (home, thirdparties, products, commercial, compta, bank, projects, HRM, tools, members, admin)
- Le menu respecte les droits utilisateur (`$user->hasRight(...)`) et les modules activés (`isModEnabled(...)`)

### Pages d'administration

Toutes les pages d'administration suivent le même pattern :

1. Inclusion de `config.php` → charge `main.inc.php`
2. Contrôle d'accès : `if (!$user->admin) accessforbidden();`
3. Actions : `GETPOST('action', 'alpha')` avec support backup/restore, on/off (`set_*`), update (`update_*`)
4. Whitelist sur les constantes modifiables : `preg_match('/^(OBLYON_|THEME_|MAIN_|FIX_|DISABLE_)/', $confkey)`
5. Reset du cache : `$_SESSION['dol_resetcache']`
6. Rendu : `llxHeader()`, onglets via `oblyon_admin_prepare_head()`, `llxFooter()`

Cas particulier de `admin/colors.php` (3.6.0) : un bloc d'actions presets est traité **avant** le bloc générique `update_` — `download_preset` en GET (jeton, envoie le fichier JSON puis `exit`), puis `apply_preset`, `save_preset` (mise à jour), `saveas_preset`, `delete_preset`, `import_preset` en POST + jeton, chacun suivi de `setEventMessages()` et d'une redirection vers la page (motif Post/Redirect/Get : F5 ne rejoue pas l'action). Les cartes et les deux formulaires repliés (`<details>`) sont imprimés par `oblyon_print_preset_cards()` / `oblyon_print_preset_forms()` au-dessus du formulaire des couleurs. Le tableau `$listtheme` et le cas `theme` du bloc `update_` n'existent plus.

### Helpers HTML de la bibliothèque admin

La bibliothèque `lib/oblyon.lib.php` fournit des fonctions utilitaires pour les pages d'administration :

| Fonction | Description |
|----------|-------------|
| `oblyon_admin_prepare_head()` | Génère les onglets (Options, Menus, Icons, Colors, Dashboard, Custom CSS, About, Changelog) |
| `oblyon_bkup_module($name)` | Sauvegarde les constantes module en SQL |
| `oblyon_bkup_table($table, ...)` | Génère le SQL de backup d'une table |
| `oblyon_restore_module($name)` | Restaure les constantes depuis le fichier SQL |
| `oblyon_print_backup_restore()` | Affiche la section backup/restore HTML |
| `oblyon_print_colgroup($metas)` | Affiche un `<colgroup>` HTML |
| `oblyon_print_liste_titre($metas)` | Affiche un titre de liste HTML |
| `oblyon_print_btn_action($action)` | Affiche un bouton d'action (submit) |
| `oblyon_print_hr($cs1)` | Affiche un séparateur horizontal |
| `oblyon_print_final($cs1)` | Affiche une ligne finale |
| `oblyon_print_input($confkey, $tag, ...)` | Affiche un champ de formulaire (on/off, input, textarea, color, select, range) |

### Bibliothèque des couleurs par utilisateur (`lib/oblyon_colors.lib.php`, 3.6.0)

| Fonction | Rôle |
|----------|------|
| `oblyon_user_colors_enabled($user)` | Drapeau `OBLYON_USER_COLORS` (ou ancien `THEME_ELDY_ENABLE_PERSONALIZED` utilisateur) ; faux sans utilisateur chargé |
| `oblyon_color_is_valid($value, $name)` / `oblyon_colors_rgb_allowed()` | `#RRGGBB` (6 chiffres) ou `#` seul ; `r,g,b` seulement pour les constantes normalisées par le thème |
| `oblyon_color_setting($name, $default, $user)` | Valeur personnelle valide si drapeau, sinon `getDolGlobalString()` : **seul point de lecture des couleurs du thème** |
| `oblyon_color_setting_hex($name, $default, $user)` | Idem, mais renvoie `$default` si la valeur n'est pas `#RRGGBB` (3.7.0 : jetons imprimés comme couleur CSS brute) |
| `oblyon_text_on($background, $dark, $light)` | Texte sombre ou blanc selon le meilleur contraste WCAG sur le fond (3.7.0 : badges de statut) |
| `oblyon_color_to_hex($value, $fallback)` | `r,g,b` → `#RRGGBB`, hex → hex majuscule, sinon `$fallback` ou la valeur (3.7.0 : **format unique hex**) |
| `oblyon_colors_normalize_stored($user)` | Réécrit en hex les constantes (ou les couleurs personnelles de `$user`) encore en `r,g,b` ; appelée à l'ouverture des onglets Couleurs ; incrémente la révision CSS (3.7.0) |
| `oblyon_user_colors_list()` / `oblyon_user_colors_keys()` | Groupes → constantes de l'onglet (menus haut/gauche permutés si `MAIN_MENU_INVERT`), liste plate (116, 138 depuis 3.7.0) |
| `oblyon_user_color_label($name)` | Libellé (clé de langue = nom de la constante, TOP/LEFT permutés) |
| `oblyon_color_swatch($value)` | Pastille + code pour le mode lecture |
| `oblyon_user_presets_dir($userid)` / `oblyon_get_user_presets($userid)` | Dossier et liste des presets personnels (fichiers JSON, source `user`) |
| `oblyon_get_presets_for_user($userid)` | Presets proposés sur l'onglet : module (`scope` vide puis `user`) + personnels |
| `oblyon_preset_user_colors($preset)` / `oblyon_user_current_colors($object)` | Couleurs d'un preset limitées aux 116 constantes ; couleurs affichées à l'utilisateur (personnelles valides sinon instance) |
| `oblyon_apply_preset_to_user($preset, $object)` | Photo complète + drapeau + révision CSS ; 1 / -1 |
| `oblyon_save_user_preset($object, $key, $name, $desc)` / `oblyon_delete_user_preset($object, $key)` | Preset personnel : -2 clé invalide, -3 clé du module, -4 doublon, -1 écriture ; suppression -2 inconnu |
| `oblyon_print_user_preset_cards($object, $canedit)` | Cartes de l'onglet + formulaire replié « Enregistrer mes couleurs actuelles comme preset » |

### Bibliothèque des presets (`lib/oblyon_presets.lib.php`, 3.6.0)

Incluse par `admin/colors.php` (`dol_include_once`). Toutes les fonctions sont préfixées `oblyon_preset(s)_` ; les codes retour négatifs sont traduits en messages par `colors.php` (`OblyonPresetError*`).

| Fonction | Rôle |
|----------|------|
| `oblyon_presets_sections()` | Définition des sections et de leurs listes blanches (`names` exacts, `patterns` préfixe `*`, `scalar`) |
| `oblyon_presets_section_of($name)` | Section d'une constante (`''` = non gérée) |
| `oblyon_presets_dirs()` | Dossiers `module` et `instance` (logique d'entité de `oblyon_bkup_module`) |
| `oblyon_preset_key_is_valid($key)` | Clé `[a-z0-9][a-z0-9_-]{1,39}` |
| `oblyon_preset_file_path($key, $source)` | Chemin du fichier |
| `oblyon_load_preset_file($file, $source)` | Lit et normalise un fichier (256 Ko max, `json_last_error()`), `null` si invalide |
| `oblyon_normalize_preset_data($data)` | Filtre par listes blanches, hex en majuscules, méta nettoyées |
| `oblyon_get_presets()` / `oblyon_get_preset($key)` / `oblyon_get_presets_reset()` | Liste (cache statique par requête, l'instance écrase le module à clé égale), un preset, purge du cache après écriture |
| `oblyon_presets_current_values($sections)` | Valeurs en base des constantes des sections (une requête, entité 0 puis courante) ; liste vide = toutes |
| `oblyon_preset_modified_sections($preset)` | Sections où la base diffère du fichier (absent en base = `''`) |
| `oblyon_detect_current_preset()` | Sème `OBLYON_CURRENT_PRESET` si absente et qu'un preset est identique à la base |
| `oblyon_apply_menu_rules()` | Règles de cohérence de l'onglet Menus (rejouées après application de la section `menus`) |
| `oblyon_apply_preset($key, $sections)` | Transaction, `dolibarr_set_const` par constante, `OBLYON_CURRENT_PRESET`, `MAIN_IHM_PARAMS_REV`+1 ; 1 / -1 / -2 inconnu |
| `oblyon_build_preset_data($name, $description, $sections, $author)` | Tableau prêt à écrire depuis la base (version 1, auteur = société) |
| `oblyon_write_preset_file($key, $data)` | Écrit `<instance>/<clé>.json` (dossier créé) ; 1 / -1 |
| `oblyon_export_preset($key, $name, $description, $sections, $makecurrent)` | « Enregistrer sous » ; -2 clé invalide, -3 clé du module, -4 existe déjà |
| `oblyon_update_preset($key, $sections, $name, $description)` | « Mettre à jour » un preset d'instance : fichier reconstruit depuis la base, version +1 ; -2 inconnu ou module |
| `oblyon_delete_preset($key)` | Supprime un preset d'instance (et `OBLYON_CURRENT_PRESET` si c'était lui) ; -2 inconnu ou module |
| `oblyon_import_preset($tmpfile, $key, $replace)` | Fichier téléversé → normalisé → écrit ; -5 fichier invalide, -3 / -4 comme l'export |
| `oblyon_color_luminance($hex)` / `oblyon_contrast_ratio($a, $b)` | Luminance relative et rapport de contraste WCAG 2 |
| `oblyon_check_preset_contrast($preset, $threshold)` | Couples texte/fond sous le seuil (52 couples + 15 tuiles du tableau de bord, tels que le thème les peint ; un couple peut porter son propre seuil en 3e élément, un nom commençant par `#` est une couleur littérale) + valeurs de couleur illisibles par le thème (entrée `invalid`, 3.7.0) |
| `oblyon_contrast_issue_text($issue, $labelfn)` | Une ligne du rapport de contraste (couple ou valeur invalide), partagée par les cartes, l'onglet utilisateur et ses presets (3.7.0) |
| `oblyon_presets_section_label($section)` / `oblyon_preset_text($text)` / `oblyon_preset_color($preset, $name)` | Libellé de section, texte traduit si clé de langue, couleur d'aperçu (`#CCCCCC` si absente) |
| `oblyon_preset_card_preview($preset, $key, $source)` | Aperçu d'une carte (capture `img/oblyon<clé>.png` si `source = module` et fichier présent, sinon dessin), partagé avec l'onglet utilisateur |
| `oblyon_print_preset_cards()` | Cartes (module puis instance) : capture `img/oblyon<clé>.png` ou dessin CSS, nom + badges, icône contraste, boutons Appliquer / Mettre à jour / Télécharger / Supprimer |
| `oblyon_print_preset_forms()` | Formulaires repliés « Enregistrer sous » (clé avec `textwithpicto`, nom, description) et « Importer » |

L'écran applique, met à jour et enregistre toujours un preset **en entier** ; le paramètre `$sections` n'est utilisé que par des appels programmés (liste vide = tout).

### Fonctions du gestionnaire de menus ajoutées en 3.4.0 / 3.5.0 (`core/menus/standard/oblyon.lib.php`)

| Fonction | Rôle |
|----------|------|
| `oblyon_flyout_enabled()` | Volets actifs : menus inversés + menu réduit + effet `flyout`, pas de menu caché ; petit écran ignoré quand la disposition mobile est active |
| `oblyon_mobile_nav_enabled()` | `OBLYON_MOBILE_LAYOUT` (défaut 1) et pas en mode impression |
| `oblyon_flyout_tree_enabled()` | L'arbre des sous-menus doit être imprimé (volets **ou** disposition mobile) |
| `oblyon_mobile_nav_button()` / `oblyon_mobile_nav_head()` / `oblyon_mobile_nav_toggle()` | Bouton hamburger de la barre, en-tête du tiroir (nom de société + fermer), chevron d'accordéon |
| `oblyon_flyout_context($set)` | Transmet `tabMenu` / `type_user` à `print_oblyon_flyout()` |
| `oblyon_flyout_build_url($entry)` | Adresse d'une entrée de sous-menu |
| `print_oblyon_flyout($idsel)` | Reconstruit l'arbre complet d'un module (`print_left_oblyon_menu()` silencieux) en `ul.oblyon-flyout` |

### js/oblyon.js

Un seul fichier, chargé sur toutes les pages (`module_parts['js']`, adresse versionnée). Il lit les indicateurs exposés par le CSS du thème (`--oblyon-touchmenu-forced` et `--oblyon-reduce-hover` de `touchmenu.inc.php`, `--oblyon-flyout` de `flyoutmenu.inc.php`, `--oblyon-mobile` et `--oblyon-mobile-bp` de `mobile.inc.php`) et n'active que les blocs correspondants :

- décalage du contenu sous la barre haute inversée collante (hauteur réelle de la barre) ;
- tiroir mobile : ouverture/fermeture (`body.oblyon-mnav-open`, overlay, Échap), accordéon des sous-menus (`.is-mobile-open`), déplacement de la recherche et des favoris dans le tiroir en menus classiques ;
- listes sur téléphone : bouton « Filtres » inséré avant le tableau, compteur de filtres actifs, classe `table.oblyon-mfilter-open` ;
- mode tactile : tap-to-toggle des menus inversés, du menu réduit `hover` et des volets ; fermeture au tap extérieur ;
- positionnement des volets (niveau 1 fixe sous barre collante, repli en cas de débordement).

Tous les comportements bureau sont gardés par `mobileActive()` (largeur ≤ point de rupture) pour ne pas entrer en conflit avec le tiroir.
