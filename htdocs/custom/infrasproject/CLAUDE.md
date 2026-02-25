# CLAUDE.md — Contexte module infrasproject

## Aperçu (Overview)

`infrasproject` est un module externe Dolibarr orienté gestion avancée de projets :

- consommation de stock sur projets,
- calcul de marge provisionnelle (revenus, coûts, droits de dépense),
- répartition de lignes de factures fournisseur sur plusieurs projets,
- substitution de pages Dolibarr par version,
- comportements automatiques (création/validation de projet).

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `500055`
- Licence : GPL v3+
- Compatibilité Dolibarr : `18.0.0` à `22.0.4`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `18.8.0` (2026-02)
- Dépendances obligatoires : `modProjet`, `modStock`
- Emplacement : `htdocs/custom/infrasproject/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrasproject/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   ├── generalproject.php
│   └── infrasprojectsetup.php
├── class/
│   ├── actions_infrasproject.class.php
│   ├── html.forminfrasproject.class.php
│   ├── infrasproject.class.php
│   └── infrasprojectsupplierinvoiceline.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrasproject.lib.php
│   │   └── infrasprojectAdmin.lib.php
│   ├── modules/
│   │   └── modinfrasproject.class.php
│   ├── tpl/              (templates versionnés : *_18, *_19, *_20, *_21, *_22, *_22-Easya)
│   │   ├── objectline_create*.tpl.php
│   │   ├── objectline_edit*.tpl.php
│   │   ├── objectline_title*.tpl.php
│   │   └── objectline_view*.tpl.php
│   └── triggers/
│       └── interface_98_modinfrasproject_infrasprojecttrigger.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrasproject.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── infrasproject_tab.php
├── langs/
│   ├── en_US/infrasproject.lang
│   ├── es_ES/infrasproject.lang
│   └── fr_FR/infrasproject.lang
├── sql/
│   ├── data.sql
│   └── update.sql
└── substitutionpages/
    ├── dlb180x/
    ├── dlb180x-Easya/
    ├── dlb190x/
    ├── dlb200x/
    ├── dlb210x/
    └── dlb220x/
```

## Descripteur module (Module descriptor : `modinfrasproject`)

Dans `core/modules/modinfrasproject.class.php` :

- **Module parts** :
	- `tpl`, `triggers`
	- hooks : `main`, `login`, `projectOverview`, `projectcard`, `invoicesuppliercard`
	- CSS : `/infrasproject/css/infrasproject.css.php`
- **Dépendances** : `modProjet`, `modStock`
- **Onglet additionnel** : onglet « Consommation Stock » sur la fiche projet
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 6 permissions
	- `paramMenu`, `paramDolibarr`, `paramSetup`, `paramBkpRest`
	- `readproject`, `writeproject`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrasproject/sql/')`)
2. Restauration des constantes module (`infrasproject_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASPROJECT_DOL_VERSION`
	 - `INFRASPROJECT_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue sauvegarde module (`infrasproject_bkup_module`), nettoyage des constantes `INFRASPROJECT_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrasproject.class.php` pour les hooks de substitution de pages et d'enrichissement de l'overview projet,
- `infrasproject.class.php` (étend `Project`) pour la consommation stock (`correct_stock()`) et le calcul de marge (`calcul_marge_fourn()`),
- `infrasprojectsupplierinvoiceline.class.php` pour la répartition multi-projets de lignes de factures fournisseur,
- `html.forminfrasproject.class.php` pour les formulaires de consommation stock (sélection entrepôt, lot/série),
- `infrasproject.lib.php` pour la logique transverse (substitution de pages, calcul de profit, intégration ContactTracking),
- `infrasprojectAdmin.lib.php` pour l'administration (onglets, changelog, backup/restore, vérification de mise à jour),
- le trigger `interface_98_modinfrasproject_infrasprojecttrigger.class.php` (création automatique de projet à la signature de devis),
- `infrasproject_tab.php` pour l'onglet de consommation stock sur la fiche projet,
- les templates `core/tpl/` versionnés par version Dolibarr (18, 19, 20, 21, 22, 22-Easya).

## Hooks et comportement (Hook behavior)

La classe `ActionsInfrasproject` intervient principalement sur :

- `afterLogin` / `updateSession` : redirection vers les pages de substitution selon constantes de configuration,
- `projectOverview` (`printFieldListOption1/2`, `printFieldListValue1/2`) : injection du tableau de marge provisionnelle et infos ContactTracking,
- `projectcard` : ajout de l'onglet consommation stock, personnalisation de l'affichage projet,
- `invoicesuppliercard` : répartition multi-projets des lignes de factures fournisseur.

## Données / SQL (Data model)

Pas de tables personnalisées — utilise les mouvements de stock Dolibarr (`llx_stock_mouvement`) avec des codes inventaire personnalisés.

Les mouvements sont identifiés par :

- préfixe de code inventaire (`INFRASPROJECT_CODE_MVT_STOCK`),
- mode de recherche (`INFRASPROJECT_SEARCH_MODE_STOCK` : 0=réf projet, 1=préfixe, 2=mixte),
- libellé contenant la référence du projet.

Fichiers SQL :

- `data.sql` (constantes module),
- `update.sql` (évolutions).

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- **Consommation stock** : `INFRASPROJECT_CODE_MVT_STOCK`, `INFRASPROJECT_SEARCH_MODE_STOCK`, `INFRASPROJECT_ADD_STOCK_CONSUMPTION`, `INFRASPROJECT_DEFAULT_WAREHOUSE`, `INFRASPROJECT_LINK_TO_USER`, `INFRASPROJECT_CATEG_PRODUCT_CONSUMPTION`, `INFRASPROJECT_DATE_MVT_STOCK`
- **Marge provisionnelle** : `INFRASPROJECT_SHOW_PROVISIONAL_PROFIT`, `INFRASPROJECT_PROVISIONAL_PROFIT_FIRST`, `INFRASPROJECT_ELEMENTS_PROFIT_ADDITION`, `INFRASPROJECT_ELEMENTS_PROFIT_DEDUCTION`, `INFRASPROJECT_UNLINKED_SUPPLIER_INVOICE`, `INFRASPROJECT_EXCLUDED_TYPES_NDFP`
- **Taux de marque** : `INFRASPROJECT_TAUX_MARQUE_PRODUCT`, `INFRASPROJECT_TAUX_MARQUE_SERVICE`, `INFRASPROJECT_TAUX_MARQUE_MIXTE`
- **Masquage de listes** : `INFRASPROJECT_HIDE_EMPTY_LISTS`, `INFRASPROJECT_HIDE_ORDER_LIST`, `INFRASPROJECT_HIDE_INVOICE_TEMPLATE_LIST`, `INFRASPROJECT_HIDE_SUPPLIER_PROPOSAL_LIST`, `INFRASPROJECT_HIDE_SHIPMENT_LIST`, `INFRASPROJECT_HIDE_TIME_SPENT_LIST`
- **Comportements automatiques** : `INFRASPROJECT_PROJECT_ON_SIGN_PROPAL`, `INFRASPROJECT_VALIDATE_PROJECT_ON_CREATE`, `INFRASPROJECT_PROJECT_OPEN_TAB`
- **ContactTracking** : `INFRASPROJECT_CT_LAST_EXCHANGE`, `INFRASPROJECT_CT_NEXT_ACTION`
- **Substitution de pages** : `INFRASPROJECT_SUBSTITUTE_PROJECT_OVERVIEW`, `INFRASPROJECT_SUBSTITUTE_PROJECT_TASKS`, etc.
- **Constantes Dolibarr masquées** (onglet Général) : `PROJECT_*` (commentaires, opportunités, tâches, etc.)
- **Constantes de versions/migrations** : `INFRASPROJECT_DOL_VERSION`, `INFRASPROJECT_MAIN_VERSION`

Point de vigilance : les constantes `INFRASPROJECT_*` sont nombreuses ; éviter les changements massifs sans test fonctionnel.

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / templates / trigger :

1. Désactiver puis réactiver le module
2. Vérifier constantes (`INFRASPROJECT_*`)
3. Vérifier l'onglet consommation stock sur une fiche projet
4. Vérifier les hooks de substitution (`afterLogin`, `projectOverview`, `projectcard`, `invoicesuppliercard`)
5. Vérifier un cas d'affichage réel de l'overview projet avec marge provisionnelle activée
6. Vérifier les templates TPL pour la version Dolibarr en cours

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrasproject_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module applique des substitutions de pages selon version Dolibarr (répertoire `substitutionpages/`)
- Chaque version Dolibarr a son propre répertoire de substitution et ses propres TPL — maintenir toutes les versions indépendamment
- Le module auto-désactive si la version Dolibarr est inférieure au minimum requis
- L'intégration ContactTracking est optionnelle et dégradée gracieusement si le module est désactivé

## Dernières mises à jour (Recent updates)

- `18.8.0` (2026-02) : ajout du fichier CLAUDE.md pour l'intégration avec Claude Code (IA)
- `18.8.0` (2026-02) : audit de sécurité : ~30 corrections (SQL injection, XSS, CSRF, typage des variables)
- `18.8.0` (2026-02) : correction du nom de constante PHP XML (`INFRASPROJECT_PHP_EXT_XML` → `INFRAS_PHP_EXT_XML`)
- `18.8.0` (2026-02) : correction des classes CSS résiduelles d'un autre module dans les pages de paramètres
- `18.8.0` (2026-02) : ajout des includes manquants (FormCompany, FormOther) dans la bibliothèque Admin
- `18.8.0` (2026-02) : remplacement des balises HTML obsolètes (`<FONT>`) par du CSS
- `18.8.0` (2026-02) : alignement du code avec les autres modules InfraS (infraspackplus, infraswidgets, infrassearch)
- `18.8.0` (2026-02) : amélioration de la documentation PHPDoc
