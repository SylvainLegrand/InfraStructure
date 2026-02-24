# CLAUDE.md — Contexte module infrassearch

## Aperçu

`infrassearch` est un module externe Dolibarr de recherche avancée multi-objets :

- recherche simultanée sur plusieurs modules Dolibarr,
- intégration dans le menu haut et/ou remplacement de la recherche standard,
- page de recherche dédiée,
- fil d’Ariane des derniers objets consultés.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `550080`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `22.0.4`
- Compatibilité PHP : `7.4` à `8.4`
- Emplacement : `htdocs/custom/infrassearch/`

## Structure (résumé)

```text
htdocs/custom/infrassearch/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrassearchsetup.php
├── class/
│   └── actions_infrassearch.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrassearch.lib.php
│   │   └── infrassearchAdmin.lib.php
│   └── modules/
│       └── modinfrassearch.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrassearch.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── js/
│   └── jquery.tile.min.js
├── langs/
│   ├── en_US/infrassearch.lang
│   ├── es_ES/infrassearch.lang
│   └── fr_FR/infrassearch.lang
├── script/
│   └── interface.php
├── search.php
└── sql/
		├── data.sql
		├── llx_infrassearch_history.sql
		└── update_data.sql
```

## Descripteur module (`modinfrassearch`)

Dans `core/modules/modinfrassearch.class.php` :

- **Module parts** :
	- hooks : `adminmodules`, `searchform`, `toprightmenu`
	- CSS : `/infrassearch/css/infrassearch.css.php`
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 3 permissions
	- `paramMenu`
	- `paramInfraSSearch`
	- `paramBkpRest`

### Initialisation (`init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrassearch/sql/')`)
2. Restauration des constantes module (`infrassearch_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASSEARCH_LISTTOBJECTTYPE`
	 - `INFRASSEARCH_DOL_VERSION`
	 - `INFRASSEARCH_MAIN_VERSION`
4. Migration de compatibilité des anciennes constantes `INFRASSEARCH_*` vers `INFRASSEARCH_MOD_*`

### Désactivation (`remove()`)

`remove()` supprime les constantes `INFRASSEARCH_%` de l’entité courante après sauvegarde module.

## Mécanismes de recherche

Le module propose 4 points d’intégration :

1. zone de recherche dans le menu haut (`INFRASSEARCH_ON_TOP_MENU`),
2. remplacement de la recherche standard (`INFRASSEARCH_REPLACE_STD`),
3. ajout d’une entrée `infrassearch` dans la recherche standard,
4. page dédiée `search.php` (menu Outils).

Le moteur AJAX est implémenté dans `script/interface.php`.

## Hooks et comportement

La classe `Actionsinfrassearch` gère principalement :

- `printTopRightMenu` : zone de recherche + dropdown fil d’Ariane,
- `printSearchForm` : remplacement/complément du formulaire standard,
- `addSearchEntry` : ajout du provider de recherche,
- `doActions` (`adminmodules`) : nettoyage des constantes module désactivé,
- `printCommonFooter` : historisation des objets visités.

## Données / SQL

Table principale :

- `llx_infrassearch_history`

Colonnes principales : `rowid`, `entity`, `element`, `fk_element`, `fk_user`, `tms`.

Le nettoyage de l’historique est effectué dans le hook `printCommonFooter` (conservation glissante).

## Constantes de configuration (principales)

Constantes actives usuelles :

- `INFRASSEARCH_ON_TOP_MENU`
- `INFRASSEARCH_REPLACE_STD`
- `INFRASSEARCH_BREADCRUMB`
- `INFRASSEARCH_NB_BREADCRUMB`
- `INFRASSEARCH_NB_CAR`
- `INFRASSEARCH_NB_SEC`
- `INFRASSEARCH_NB_ROWS`
- `INFRASSEARCH_ONLY_IN_ENTITY`
- `INFRASSEARCH_SORT`
- `INFRASSEARCH_ORDER`
- `INFRASSEARCH_SHOW_FIND_FIELD`
- `INFRASSEARCH_MOD_<TYPE>`
- `INFRASSEARCH_POS_<TYPE>`

Valeurs seed `sql/data.sql` à connaître :

- `INFRASSEARCH_SORT = DESC`
- `INFRASSEARCH_ORDER = 1`

## Conventions de développement

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels

Si modification SQL / descripteur / permissions / constantes / hooks :

1. Désactiver puis réactiver le module
2. Vérifier création/mise à jour table `llx_infrassearch_history`
3. Vérifier les constantes module (`INFRASSEARCH_*`)
4. Tester les 4 points d’entrée de recherche
5. Tester le fil d’Ariane et le nettoyage historique

## Points d’attention

- La version locale est lue depuis `docs/changelog.xml` (`infrassearch_getLocalVersionMinDoli`)
- L’extension PHP XML est nécessaire
- Le module déclenche un avertissement si la version Dolibarr dépasse la version max supportée
- La recherche téléphone a des règles spécifiques (normalisation et conversions local/international)