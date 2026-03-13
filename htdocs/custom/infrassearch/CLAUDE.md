# CLAUDE.md — Contexte module infrassearch

## Aperçu (Overview)

`infrassearch` est un module externe Dolibarr de recherche avancée multi-objets :

- recherche simultanée sur plusieurs modules Dolibarr,
- intégration dans le menu haut et/ou remplacement de la recherche standard,
- page de recherche dédiée,
- fil d’Ariane des derniers objets consultés.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `550080`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.4.6` (2026-03)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
- Emplacement : `htdocs/custom/infrassearch/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

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
├── img/
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

## Descripteur module (Module descriptor : `modinfrassearch`)

Dans `core/modules/modinfrassearch.class.php` :

- **Module parts** :
	- hooks : `adminmodules`, `searchform`, `toprightmenu`
	- CSS : `/infrassearch/css/infrassearch.css.php`
- **Dépendances** : aucune dépendance obligatoire
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 3 permissions
	- `paramMenu` (par défaut : activée)
	- `paramInfraSSearch`
	- `paramBkpRest`
- **Famille** : `Dolibarr LTS by InfraS` (branding dynamique si module dolinfras activé) ou `Modules Recherche`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrassearch/sql/')`)
2. Restauration des constantes module (`infrassearch_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASSEARCH_LISTTOBJECTTYPE`
	 - `INFRASSEARCH_DOL_VERSION`
	 - `INFRASSEARCH_MAIN_VERSION`
4. Migration de compatibilité des anciennes constantes `INFRASSEARCH_*` vers `INFRASSEARCH_MOD_*`

### Désactivation (Lifecycle : `remove()`)

`remove()` supprime les constantes `INFRASSEARCH_%` de l’entité courante après sauvegarde module.
### Gestion de version (Lifecycle : `getLocalVersion()`)

Lecture depuis `docs/changelog.xml` via `infrassearch_getLocalVersionMinDoli()` :

- renseigne `need_dolibarr_version`, `phpmin`, `phpmax`,
- désactive automatiquement le module si la version Dolibarr est inférieure au minimum requis.

### Menus (Menu structure)

Le module crée deux niveaux de menus dans le menu Outils :

1. **Menu principal InfraS** (si aucun autre module InfraS n'a créé le menu) :
   - Position : 50 dans le menu Outils
   - Condition : `infrassearch_no_topmenu()` retourne `true`
   - Permission : aucune (visible pour tous)

2. **Sous-menu InfraSSearch** (trois entrées) :
   - **Titre module** (position 65) : accès à la configuration
     - Permission : `$user->hasRight('infrassearch', 'paramMenu')`
   - **Changelog** (position 66) : historique des versions
     - URL : `/infrassearch/admin/changelog.php`
     - Permission : `$user->hasRight('infrassearch', 'paramMenu')`
   - **Paramètres** (position 67) : configuration du module
     - URL : `/infrassearch/admin/infrassearchsetup.php`
     - Permission : `$user->hasRight('infrassearch', 'paramInfraSSearch')`
   - **À propos** (position 68) : présentation et informations
     - URL : `/infrassearch/admin/about.php`
     - Permission : `$user->hasRight('infrassearch', 'paramMenu')`
   - **Recherche** (position 100) : page de recherche dédiée
     - URL : `/infrassearch/search.php`
     - Icône : `object_search@infrassearch`
     - Permission : `$user->hasRight('infrassearch', 'paramMenu')`
## Fonctionnement principal (Core behavior)

Le module propose 4 points d’intégration :

1. zone de recherche dans le menu haut (`INFRASSEARCH_ON_TOP_MENU`),
2. remplacement de la recherche standard (`INFRASSEARCH_REPLACE_STD`),
3. ajout d’une entrée `infrassearch` dans la recherche standard,
4. page dédiée `search.php` (menu Outils).

Le moteur AJAX est implémenté dans `script/interface.php`.

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrassearch` gère principalement :

- `printTopRightMenu` : zone de recherche + dropdown fil d’Ariane,
- `printSearchForm` : remplacement/complément du formulaire standard,
- `addSearchEntry` : ajout du provider de recherche,
- `doActions` (`adminmodules`) : nettoyage des constantes module désactivé,
- `printCommonFooter` : historisation des objets visités.

## Trigger (Trigger behavior)

Le module ne possède pas de trigger. Aucun événement n'est écouté (pas de répertoire `core/triggers/` avec trigger actif). L'historisation des objets consultés se fait via le hook `printCommonFooter`.

## Données / SQL (Data model)

Table principale :

- `llx_infrassearch_history`

Colonnes principales : `rowid`, `entity`, `element`, `fk_element`, `fk_user`, `tms`.

Le nettoyage de l’historique est effectué dans le hook `printCommonFooter` (conservation glissante).
## Fonctions utilitaires (Library functions)

### `infrassearch.lib.php`

Bibliothèque principale contenant les fonctions de recherche et d'affichage :

| Fonction | Description |
|----------|-------------|
| `getobjectclass($element)` | Résout le classpath, classname et classfile d'un type d'objet |
| `_search($type, $keyword, $ajax)` | Moteur de recherche dynamique multi-tables avec introspection SQL |
| `printDropdownBreadCrumb()` | Affiche le dropdown fil d'Ariane dans le menu haut |
| `handleInfraSearchError($error, $context, $objecttype)` | Gestion centralisée des erreurs avec journalisation |
| `infrassearch_load_title()` | Génère un titre de section avec icône et classe CSS |

### `infrassearchAdmin.lib.php`

Bibliothèque d'administration contenant les fonctions de paramétrage :

| Fonction | Description |
|----------|-------------|
| `infrassearch_admin_prepare_head()` | Génère les onglets admin (Paramètres, À propos, Changelog) |
| `infrassearch_no_topmenu()` | Vérifie si le menu InfraS existe dans le menu Outils |
| `infrassearch_test_php_ext()` | Vérifie la présence de l'extension PHP XML |
| `infrassearch_getLocalVersionMinDoli($appliname)` | Parse le changelog XML local et retourne [version, minDoli, errFlag, versionsArray, maxDoli, minPHP, maxPHP] |
| `infrassearch_dwnChangelog($appliname)` | Télécharge le changelog depuis infras.fr |
| `infrassearch_getChangeLog($appliname, $version, $resversion, $tblversions, $dwn)` | Affichage HTML du changelog avec comparaison local/téléchargé |
| `infrassearch_getSupportInformation($version)` | Affichage HTML des infos techniques (versions) |
| `infrassearch_bkup_module($appliname)` | Sauvegarde des paramètres en SQL |
| `infrassearch_restore_module($appliname)` | Restauration des paramètres depuis le fichier SQL |
| `infrassearch_num_pos()` | Génère les options HTML de numérotation de position (tri des modules) |
| `infrassearch_print_*()` | Fonctions d'affichage HTML pour les tableaux admin |
## Constantes de configuration (Key settings)

Constantes système utilisées par le module :

| Constante | Type | Description |
|-----------|------|-------------|
| `INFRASSEARCH_ON_TOP_MENU` | bool | Affiche la zone de recherche dans le menu haut (1=actif) |
| `INFRASSEARCH_REPLACE_STD` | bool | Remplace la recherche standard par la recherche InfraS (1=actif) |
| `INFRASSEARCH_BREADCRUMB` | bool | Active le fil d'Ariane dans le menu haut (1=actif) |
| `INFRASSEARCH_NB_BREADCRUMB` | int | Nombre d'objets à afficher dans le fil d'Ariane (défaut : 5) |
| `INFRASSEARCH_NB_CAR` | int | Nombre minimum de caractères pour déclencher la recherche (défaut : 3) |
| `INFRASSEARCH_NB_SEC` | int | Délai en millisecondes avant déclenchement de la recherche (défaut : 500) |
| `INFRASSEARCH_NB_ROWS` | int | Nombre maximum de résultats par type d'objet (défaut : 10) |
| `INFRASSEARCH_ONLY_IN_ENTITY` | bool | Restreint la recherche à l'entité courante (1=actif) |
| `INFRASSEARCH_SORT` | bool | Active le tri des résultats par date (1=actif) |
| `INFRASSEARCH_ORDER` | int | Ordre de tri : 1=DESC (décroissant), 0=ASC (croissant) |
| `INFRASSEARCH_SHOW_FIND_FIELD` | bool | Affiche le champ contenant l'expression recherchée (1=actif) |
| `INFRASSEARCH_MOD_<TYPE>` | bool | Active la recherche sur le type d'objet (1=actif, 0=inactif) |
| `INFRASSEARCH_POS_<TYPE>` | int | Position d'affichage du type d'objet dans les résultats (ordre croissant) |
| `INFRASSEARCH_LISTTOBJECTTYPE` | string | Liste CSV des types d'objets disponibles |
| `INFRASSEARCH_DOL_VERSION` | string | Version de Dolibarr lors de l'activation du module |
| `INFRASSEARCH_MAIN_VERSION` | string | Version du module lors de l'activation |
| `INFRASSEARCH_DISABLE_CHECK_VERSION_MIN` | bool | Désactive la vérification de version minimum Dolibarr |
| `INFRASSEARCH_DISABLE_CHECK_VERSION_MAX` | bool | Désactive l'avertissement de version max Dolibarr |
| `INFRAS_PHP_EXT_XML` | int | État de l'extension PHP XML (1=ok, -1=absente) |
| `MAIN_USE_TOP_MENU_SEARCH_DROPDOWN` | bool | Affiche la recherche standard comme dropdown dans le menu haut (native Dolibarr) |

Valeurs seed `sql/data.sql` à connaître :

- `INFRASSEARCH_SORT = DESC`
- `INFRASSEARCH_ORDER = 1`

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / constantes / hooks :

1. Désactiver puis réactiver le module
2. Vérifier création/mise à jour table `llx_infrassearch_history`
3. Vérifier les constantes module (`INFRASSEARCH_*`)
4. Tester les 4 points d’entrée de recherche
5. Tester le fil d’Ariane et le nettoyage historique

## Points d’attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrassearch_getLocalVersionMinDoli`)
- L’extension PHP XML est nécessaire
- Le module déclenche un avertissement si la version Dolibarr dépasse la version max supportée
- La recherche téléphone a des règles spécifiques (normalisation et conversions local/international)
- Le moteur de recherche utilise l'introspection SQL (`DESCRIBE`) avec mise en cache statique
- Le fil d'Ariane conserve l'historique pendant environ 1 mois (nettoyage glissant au 1er de chaque mois)
- La recherche téléphone est calibrée pour Madagascar (indicatif +261), adapter pour autres pays
- Les constantes `INFRASSEARCH_MOD_*` sont automatiquement nettoyées lors de la désactivation d'un module
- La position d'affichage `INFRASSEARCH_POS_*` est recalculée automatiquement lors de l'ajout/suppression d'un type
- Le cookie `infrassearch_tblPSexp` est isolé pour éviter les collisions avec d'autres modules InfraS
- La recherche peut générer des requêtes SQL complexes — surveiller les performances sur de gros volumes
- Le hook `printCommonFooter` s'exécute sur toutes les pages — attention aux impacts de performance

## Dernières mises à jour (Recent updates)

- `15.4.2` (2026-02) : harmonisation de la documentation `CLAUDE.md` et des tags de traductions `###...###`
- `15.4.2` (2026-02) : échappement de l'affichage de `SERVER_SOFTWARE`
- `15.4.2` (2026-02) : échappement des URLs de formulaires basées sur `PHP_SELF` (durcissement XSS)
- `15.4.3` (2026-02) : variable `cookieName` déplacée au scope script pour corriger la persistance de l'état des panneaux après soumission de formulaire
- `15.4.3` (2026-02) : isolation du cookie JS de l'état des panneaux (`infrassearch_tblPSexp` au lieu de `tblPSexp`) pour éviter les collisions inter-modules
- `15.4.4` (2026-03) : correction de la comparaison de version max Dolibarr — utilisation du numéro de branche majeur uniquement (`explode()` au lieu de `strstr()`)
- `15.4.4` (2026-03) : Documentation : enrichissement des Notes Techniques du descripteur CLAUDE.md
- `15.4.5` (2026-03) : amélioration de l'affichage lors de la recherche (gif loading)
- `15.4.6` (2026-03) : amélioration de la compatibilité avec le module externe et thème Oblyon
- Entrées du changelog par version (types : `add`, `chg`, `fix`)

Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis. Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée.

## Notes techniques (Technical notes)

### Moteur de recherche AJAX (`script/interface.php`)

Le moteur de recherche est un endpoint AJAX appelé par jQuery UI Autocomplete. Il reçoit deux modes :

- `get=search-all` (menu haut) : recherche sur **tous** les modules activés, retourne un JSON groupé par catégorie
- `get=search` (page dédiée) : recherche sur **un seul** type d'objet, affiche le HTML directement

**Flux de la recherche `search-all`** :
```
Saisie utilisateur dans le champ de recherche (≥ INFRASSEARCH_NB_CAR caractères)
    ↓
Attente du délai INFRASSEARCH_NB_SEC (ms) sans nouvelle frappe
    ↓
Appel AJAX vers script/interface.php?get=search-all&keywords=...
    ↓
Lecture de INFRASSEARCH_LISTTOBJECTTYPE (liste CSV des types d'objets)
    ↓
Filtrage : seuls les types où INFRASSEARCH_MOD_<TYPE> == 1 sont conservés
    ↓
Tri par INFRASSEARCH_POS_<TYPE> (ordre personnalisé croissant, 999 si non défini)
    ↓
Pour chaque type d'objet : appel de _search($type, $keyword, true)
    ↓
Résultat JSON : { "NomModule": [ {categorie, label, label_clean, url, desc, statut}, ... ], ... }
    ↓
jQuery UI Autocomplete _renderItem affiche les résultats groupés avec en-têtes en gras
```

### Algorithme de recherche dynamique (`_search()`)

La fonction `_search()` effectue une recherche en profondeur sur toutes les colonnes de toutes les tables liées à un type d'objet :

1. **Configuration** : un `switch` définit pour chaque type d'objet (`propal`, `facture`, `commande`, etc.) :
   - `$tables` : liste des tables à inspecter (table principale + extrafields + lignes + jointures)
   - `$sql_join` : jointures LEFT JOIN vers les tables liées (produits, tiers, contacts)
   - `$id_field` : champ identifiant pour le `SELECT DISTINCT`
   - `$order_field` : champ de tri (date du document)
   - `$objname` : nom de la classe PHP pour `fetch()` + `getNomUrl()`
   - `$complete_label` : champ de complément d'information (ex. `ref_client`, `label`)

2. **Introspection dynamique** : pour chaque table de `$tables`, un `DESCRIBE` (mis en cache statique `$describeCache`) récupère les colonnes et leurs types

3. **Construction du WHERE** :
   - Colonnes `varchar`/`text` → `LIKE "%keyword%"`
   - Colonnes `int`/`double`/`float` → `= (int) keyword` (si valeur numérique, ≤ 2147483647)
   - Colonnes `date`/`time` → `LIKE "keyword%"` (si la saisie est une date valide)
   - Colonnes techniques exclues : `rowid`, `entity`, `import_key`, `model_pdf`, `last_main_doc`, `tms`, `fk_*`, etc.

4. **Recherche croisée** :
   - Produits : `product.ref LIKE "%keyword%"` ajouté si la table `product` est jointe
   - Contacts : `CONCAT_WS(" ", firstname, lastname)` dans les deux ordres (prénom+nom et nom+prénom)

5. **Compatibilité modules externes** :
   - `customtabs` (Patas-Monkey) : ajout dynamique des tables d'extrafields personnalisées
   - `infraspackplus` : jointure sur `infraspackplus_societe_address` si module actif et version ≥ 15.6.1
   - Modules tiers supportés : `contacttracking`, `domain`, `hosting`, `ticketsup`, `propalehistory`, `rmindr`, `factory`, `equipement`, `ndfp`

### Normalisation téléphone (Phone normalization)

Lorsque le mot-clé ressemble à un numéro de téléphone (≥ 5 chiffres, uniquement `+`, chiffres, espaces, tirets, points, parenthèses, `/`), le moteur :

1. **Extrait les chiffres** : `preg_replace('/[^\d]/', '', $keyword)` → `$keywordPhoneDigits`
2. **Calcule la variante locale/internationale** (Madagascar) :
   - `00261 34...` ou `+261 34...` → `034...` (format local)
   - `034...` → `26134...` (format international sans `+`)
3. **Recherche normalisée** : sur les champs dont le nom contient `phone`, `fax`, `mobile` ou `tel`, le moteur compare en chiffres uniquement via `REPLACE()` imbriqués (suppression espaces, tirets, points, parenthèses, `/`, `+`)
4. **Recherche croisée** : les deux variantes (locale et internationale) sont testées simultanément

### Résolution de classes (`getobjectclass()`)

La fonction `getobjectclass()` dans `infrassearch.lib.php` traduit un type d'objet (`element`) en :
- **classpath** : chemin vers le fichier de classe (ex. `comm/propal/class`)
- **classname** : nom de la classe PHP (ex. `Commande`, `FactureFournisseur`)
- **classfile** : nom du fichier (ex. `fournisseur.facture`)

Trois séries de `if/elseif` gèrent les cas non standards de Dolibarr où le classpath, le classname ou le classfile ne suivent pas la convention `element/class/element.class.php` :

```php
// Exemples de mappings non triviaux :
'propal'             → comm/propal/class/propal.class.php → Propal
'facturerec'         → compta/facture/class/facture-rec.class.php → FactureRec
'order_supplier'     → fourn/class/fournisseur.commande.class.php → CommandeFournisseur
'invoice_supplier'   → fourn/class/fournisseur.facture.class.php → FactureFournisseur
'shipping'           → expedition/class/expedition.class.php → Expedition
'action'             → comm/action/class/actioncomm.class.php → ActionComm
'member'             → adherents/class/adherent.class.php → Adherent
```

### Fil d'Ariane — historisation (`printCommonFooter` hook)

Le hook `printCommonFooter` de `Actionsinfrassearch` enregistre chaque objet consulté :

1. **Nettoyage glissant** : suppression des entrées antérieures au 1er du mois précédent (conservation ≈ 1 mois)
2. **Dédoublonnage** : suppression de l'entrée existante pour le même couple (`element`, `fk_element`)
3. **Insertion** : nouvel enregistrement dans `llx_infrassearch_history` (l'horodatage `tms` se met à jour automatiquement)
4. **Filtrage** : éléments `commonsign` exclus ; l'objet doit avoir un `id` ou `rowid` non vide

L'affichage du fil d'Ariane (`printDropdownBreadCrumb()`) :
- Clone le `$hookmanager` pour éviter les effets de bord avec multicompany
- Charge les N derniers objets (`INFRASSEARCH_NB_BREADCRUMB`, défaut 5) triés par `tms DESC`
- Utilise `getobjectclass()` pour instancier et `fetch()` chaque objet
- Affiche via `getNomUrl(1)` avec troncature à 30 caractères pour certains types (`commande`, `contact`, `facture`, `product`, etc.)
- Gestion des erreurs HTTP 500 via `handleInfraSearchError()` avec affichage d'un avertissement dans le dropdown

### Gestion des erreurs (`handleInfraSearchError()`)

Fonction centralisée de gestion des erreurs dans `infrassearch.lib.php` :
- Classe les erreurs en 3 niveaux : HTTP 500 (critique), HTTP 4xx-5xx (critique), générales
- Journalise via `dol_syslog()` avec le niveau approprié (`LOG_ERR`, `LOG_WARNING`, `LOG_INFO`)
- Retourne un tableau structuré (`is_http_500`, `is_critical`, `message`, `code`, `context`, `objecttype`)

### Hooks — récapitulatif des comportements

| Hook | Contexte | Retour | Rôle |
|------|----------|--------|------|
| `afterLogin` | `login` | 0 | Avertissement si version Dolibarr > max supportée |
| `printTopRightMenu` | `toprightmenu` | 0 | Injecte la zone de recherche autocomplete + dropdown fil d'Ariane |
| `printSearchForm` | `searchform` | 1 si remplacement, 0 sinon | Remplace le formulaire de recherche standard si `INFRASSEARCH_REPLACE_STD` |
| `addSearchEntry` | `searchform` | 0 | Ajoute une entrée de recherche dans la recherche standard |
| `doActions` | `adminmodules` | 0 | Nettoie `INFRASSEARCH_MOD_*` et `INFRASSEARCH_POS_*` lors de la désactivation d'un module |
| `printCommonFooter` | global | 0 | Historise l'objet courant dans `llx_infrassearch_history` |

### Providers de recherche (Search providers)

Chaque type d'objet est contrôlé par deux constantes :
- `INFRASSEARCH_MOD_<TYPE>` : `1` = activé, `0` = désactivé
- `INFRASSEARCH_POS_<TYPE>` : ordre d'affichage (entier croissant, `0` = fin de liste)

La liste complète des types est stockée dans `INFRASSEARCH_LISTTOBJECTTYPE` (CSV) et comprend :

| Type | Classe | Modules natifs |
|------|--------|----------------|
| `societe` | `Societe` | Tiers |
| `contact` | `Contact` | Contacts |
| `product` | `Product` | Produits/Services |
| `propal` | `Propal` | Propositions commerciales |
| `commande` | `Commande` | Commandes clients |
| `facture` | `Facture` | Factures clients |
| `contrat` | `Contrat` | Contrats |
| `ficheinter` | `Fichinter` | Interventions |
| `projet` | `Project` | Projets |
| `task` | `Task` | Tâches |
| `expedition` | `Expedition` | Expéditions |
| `expensereport` | `ExpenseReport` | Notes de frais |
| `commandefournisseur` | `CommandeFournisseur` | Commandes fournisseurs |
| `facturefournisseur` | `FactureFournisseur` | Factures fournisseurs |
| `supplier_proposal` | `SupplierProposal` | Demandes de prix fournisseurs |
| `agenda` | `ActionComm` | Agenda/Événements |
| `categorie` | `Categorie` | Catégories |
| `knowledgemanagement` | `KnowledgeRecord` | Base de connaissances |

Types issus de modules externes : `contacttracking`, `domain`, `hosting`, `ticketsup`, `propalehistory`, `rmindr`, `factory`, `equipement`, `ndfp`.

Lors de la désactivation d'un module Dolibarr, le hook `doActions` (contexte `adminmodules`) supprime automatiquement les constantes `INFRASSEARCH_MOD_*` et `INFRASSEARCH_POS_*` du module désactivé.

### Sauvegarde/restauration des paramètres

Le module dispose d'un mécanisme de sauvegarde/restauration des paramètres accessible en administration :

**Sauvegarde** (`infrassearch_bkup_module()`) :
1. Crée un fichier SQL dans `DOL_DATA_ROOT/{entity}/infrassearch/sql/update.{entity}`
2. Exporte les constantes `INFRASSEARCH_%` de `llx_const` avec `ON DUPLICATE KEY UPDATE`
3. Exporte les données de `llx_infrassearch_history`
4. Copie de sécurité horodatée dans `DOL_DATA_ROOT/{entity}/admin/`

**Restauration** (`infrassearch_restore_module()`) :
1. Lit le fichier SQL de sauvegarde
2. Exécute via `run_sql()` avec gestion multi-entité (`__ENTITY__`)

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="15.4.6" MonthVersion="2026-03">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="20260301"/>
    <Dolibarr minVersion="15.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

La fonction `infrassearch_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "15.4.6",           // Version courante
    1 => "15.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :
1. Chargement des tables SQL (`_load_tables('/infrassearch/sql/')`)
2. Restauration des paramètres sauvegardés (`infrassearch_restore_module`)
3. Initialisation de `INFRASSEARCH_LISTTOBJECTTYPE` (liste CSV des types d'objets disponibles)
4. Enregistrement de `INFRASSEARCH_DOL_VERSION` et `INFRASSEARCH_MAIN_VERSION`
5. Migration de compatibilité des anciennes constantes `INFRASSEARCH_*` vers `INFRASSEARCH_MOD_*`
6. Appel de `$this->_init()` standard

**`remove()`** effectue :
1. Sauvegarde des paramètres (`infrassearch_bkup_module`)
2. Suppression des constantes `INFRASSEARCH_%` de l'entité courante
3. Appel de `$this->_remove()` standard

### Page de recherche dédiée (`search.php`)

La page accessible depuis le menu Outils propose :
- Un champ de saisie libre
- Un bouton « Rechercher » + prise en charge de la touche Entrée
- L'exécution parallèle d'un appel AJAX par type d'objet activé (`get=search`)
- Affichage des résultats dans des blocs HTML tuilés via `jquery.tile.min.js`

### Bibliothèque admin (`infrassearchAdmin.lib.php`)

Fonctions utilitaires pour les pages d'administration :

| Fonction | Rôle |
|----------|------|
| `infrassearch_admin_prepare_head()` | Génère les onglets admin (Paramètres, À propos, Changelog) |
| `infrassearch_no_topmenu()` | Vérifie si le menu InfraS existe dans le menu Outils |
| `infrassearch_test_php_ext()` | Vérifie la présence de l'extension PHP XML |
| `infrassearch_getLocalVersionMinDoli()` | Parse le changelog XML local |
| `infrassearch_dwnChangelog()` | Télécharge le changelog depuis infras.fr |
| `infrassearch_getChangeLog()` | Affichage HTML du changelog avec comparaison local/téléchargé |
| `infrassearch_getSupportInformation()` | Affichage HTML des infos techniques (versions) |
| `infrassearch_bkup_module()` | Sauvegarde des paramètres en SQL |
| `infrassearch_restore_module()` | Restauration des paramètres depuis le fichier SQL |
| `infrassearch_num_pos()` | Génère les options HTML de numérotation de position (tri des modules) |
| `infrassearch_print_*()` | Fonctions d'affichage HTML pour les tableaux admin |

## Cas d'usage courants (Common use cases)

### Cas 1 : Activation de la recherche avancée dans le menu haut

1. Activer le module InfraSSearch
2. Aller dans les paramètres du module
3. Activer `INFRASSEARCH_ON_TOP_MENU`
4. Cocher les types d'objets à inclure (`INFRASSEARCH_MOD_SOCIETE`, `INFRASSEARCH_MOD_FACTURE`, etc.)
5. Configurer l'ordre d'affichage via `INFRASSEARCH_POS_*` (les résultats Tiers apparaîtront en premier si position = 1)
6. Taper ≥ 3 caractères dans le champ de recherche du menu haut → résultats en autocomplete

### Cas 2 : Recherche d'un numéro de téléphone

1. Saisir un numéro (ex. `034 12 345 67`)
2. Le moteur normalise en chiffres : `0341234567`
3. Recherche croisée sur les formats : `0341234567` (local) et `2611234567` (international)
4. Tous les tiers/contacts dont le champ `phone`, `fax` ou `mobile` contient une variante sont retournés

### Cas 3 : Ajout d'un nouveau module externe à la recherche

1. Ajouter le type d'objet dans `INFRASSEARCH_LISTTOBJECTTYPE` (CSV)
2. Ajouter le `case` correspondant dans le `switch` de `_search()` (dans `interface.php`)
3. Ajouter le mapping dans `getobjectclass()` si le classpath/classname est non standard
4. Créer la constante `INFRASSEARCH_MOD_<TYPE>` et `INFRASSEARCH_POS_<TYPE>`
5. Ajouter le `require_once` conditionnel dans les `$classPaths` de `interface.php`