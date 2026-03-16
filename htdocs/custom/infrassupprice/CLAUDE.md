# CLAUDE.md — Contexte module infrassupprice

## Aperçu (Overview)

`infrassupprice` est un module externe Dolibarr de gestion avancée des tarifs fournisseurs :

- mise à jour des prix fournisseurs depuis les documents commerciaux (demandes de prix, commandes, factures),
- affichage d'un tableau de comparaison prix document / prix fournisseur en base,
- support multi-devises complet,
- option de gestion de la quantité minimum.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500056`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.3.3` (2026-03)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
- Emplacement : `htdocs/custom/infrassupprice/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrassupprice/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrassuppricesetup.php
├── class/
│   ├── actions_infrassupprice.class.php
│   └── infrassupprice.class.php
├── config.php
├── core/
│   ├── lib/
│   │   └── infrassuppriceAdmin.lib.php
│   └── modules/
│       └── modinfrassupprice.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrassupprice.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
├── langs/
│   ├── en_US/infrassupprice.lang
│   ├── es_ES/infrassupprice.lang
│   └── fr_FR/infrassupprice.lang
├── script/
│   ├── interface.php
│   └── message.php
└── sql/
    └── data.sql
```

## Descripteur module (Module descriptor : `modinfrassupprice`)

Dans `core/modules/modinfrassupprice.class.php` :

- **Module parts** :
	- hooks : `supplier_proposalcard`, `ordersuppliercard`, `invoicesuppliercard`
	- CSS : `/infrassupprice/css/infrassupprice.css.php`
- **Dépendances** : aucune dépendance module ; extension PHP `xml` requise
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **Permissions** : 4 permissions
	- `paramMenu` (défaut : activée)
	- `paramInfraSSupPrice`
	- `paramBkpRest`
	- `update`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrassupprice/sql/')`)
2. Restauration des constantes module (`infrassupprice_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASSUPPRICE_DOL_VERSION`
	 - `INFRASSUPPRICE_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- sauvegarde module (`infrassupprice_bkup_module`),
- suppression des constantes `INFRASSUPPRICE_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module opère en trois étapes depuis les fiches documents fournisseur validés :

1. **Affichage** — Le hook injecte un tableau replié listant chaque ligne produit du document avec valeurs courantes et valeurs en base (tarif fournisseur existant, affiché en tooltip).

2. **Sélection** — L'utilisateur coche les produits à mettre à jour, avec possibilité de modifier la référence fournisseur et la quantité minimum.

3. **Mise à jour** — Appels AJAX séquentiels vers `script/interface.php` qui appelle `InfraSSupPrice->InfraS_update_buyprice()` pour créer ou mettre à jour le tarif dans `llx_product_fournisseur_price`.

Le module s'appuie sur :

- `actions_infrassupprice.class.php` pour l'injection du tableau via hook,
- `infrassupprice.class.php` (extends `Product`) pour la logique CRUD des prix fournisseurs,
- `infrassuppriceAdmin.lib.php` pour les fonctions d'administration (onglets, version, backup/restore, changelog XML).

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrassupprice` intervient sur les contextes `supplier_proposalcard`, `ordersuppliercard`, `invoicesuppliercard` :

- `afterLogin` : vérifie la version max Dolibarr supportée (avertissement si version non supportée),
- `addMoreActionsButtons` : injection du tableau de comparaison des tarifs fournisseurs sur les fiches document.

### Conditions d'affichage du tableau

Le tableau n'est affiché que si **toutes** les conditions sont réunies :

- Le document contient au moins une ligne (`count($object->lines) > 0`),
- Le document est validé (`$object->statut >= 1`),
- L'utilisateur a la permission `update` (`$user->hasRight('infrassupprice', 'update')`).

### Construction du tableau

Pour chaque ligne produit du document, le hook :

1. Récupère les données du document : référence, prix unitaire, TVA, quantité, remise, devise
2. Interroge `llx_product_fournisseur_price` pour trouver le tarif fournisseur existant en base (même ref, même quantité, même fournisseur)
3. Affiche les valeurs en base en tooltip sur les champs (prix, quantité min, remise, TVA)
4. Génère un formulaire avec case à cocher par ligne et bouton de mise à jour globale

Le hook construit un tableau HTML avec JavaScript jQuery intégré pour :

- case à cocher globale (`#all_up_qp`),
- bouton de mise à jour (`#bt_add_sp`),
- appels AJAX séquentiels (`async: false`) vers `script/interface.php`,
- affichage des messages via `script/message.php` + rechargement de page.

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. Toute la configuration est stockée dans `llx_const`.

Tables Dolibarr core utilisées :

| Table | Usage |
|-------|-------|
| `llx_product_fournisseur_price` | Stockage des tarifs fournisseur par produit/fournisseur/quantité (INSERT ou UPDATE) |
| `llx_product` | Référence produit (lecture via `Product::fetch()`) |
| `llx_const` | Constantes de configuration module |

Le fichier `sql/data.sql` contient uniquement la valeur par défaut de `INFRASSUPPRICE_QTE_NOT_VALUE_MIN`.

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASSUPPRICE_QTE_NOT_VALUE_MIN` : force la quantité minimum à 1 au lieu de la quantité de la ligne document (défaut : `0`)
- `INFRASSUPPRICE_DOL_VERSION` : version Dolibarr lors de l'activation
- `INFRASSUPPRICE_MAIN_VERSION` : version module lors de l'activation
- `INFRASSUPPRICE_DISABLE_CHECK_VERSION_MIN` : désactive le contrôle de version minimum Dolibarr
- `INFRASSUPPRICE_DISABLE_CHECK_VERSION_MAX` : désactive le contrôle de version maximum Dolibarr
- `INFRAS_PHP_EXT_XML` : statut de l'extension PHP XML (`1` = chargée, `-1` = manquante)

## Conventions de développement (Development conventions)

Respecter les règles Dolibarr du dépôt parent :

- compatibilité PHP (code base : 7.1–8.4 ; module : 7.4–8.4 selon changelog),
- pas de framework lourd / pas de Composer en core,
- entrées utilisateur via `GETPOST*`,
- constantes via `getDolGlobalString()`, `getDolGlobalInt()`, `getDolGlobalBool()`,
- SQL sécurisé : cast `int`, échappement `$db->escape()` / `$db->escapeforlike()`,
- gestion multi-entité via `entity` / `getEntity()` selon les objets.

## Workflow recommandé après changements structurels (Recommended workflow)

Si modification SQL / descripteur / permissions / hooks / constantes :

1. Désactiver puis réactiver le module
2. Vérifier les constantes module (`INFRASSUPPRICE_*`)
3. Vérifier les permissions (4 permissions : `paramMenu`, `paramInfraSSupPrice`, `paramBkpRest`, `update`)
4. Tester la mise à jour de prix depuis une commande fournisseur validée
5. Tester le support multi-devises si le module multicurrency est actif
6. Vérifier que le tableau ne s'affiche pas sur les documents en brouillon (statut < 1)

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrassupprice_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis
- Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée
- Les scripts AJAX (`interface.php`, `message.php`) nécessitent `NOTOKENRENEWAL` et un contrôle d'accès
- Le hook n'affiche le tableau que sur documents validés (statut ≥ 1) et si l'utilisateur a le droit `update`

## Dernières mises à jour (Recent updates)

- `15.2.0` (2026-02) : corrections d'injections SQL dans la classe métier et le hook (cast int/float, `$db->escape()`, guillemets simples)
- `15.2.0` (2026-02) : ajout du contrôle d'accès et de `NOTOKENRENEWAL` sur les endpoints AJAX
- `15.2.0` (2026-02) : échappement des URLs de formulaires basées sur `PHP_SELF` (durcissement XSS)
- `15.2.0` (2026-02) : suppression de l'exposition des requêtes SQL dans les messages d'erreur
- `15.2.0` (2026-02) : remplacement de `now()` SQL par `$db->idate(dol_now())`
- `15.2.0` (2026-02) : validation GETPOST avec types appropriés sur tous les paramètres non typés
- `15.2.0` (2026-02) : normalisation `elseif` (PSR-12) et harmonisation de la librairie admin
- `15.2.0` (2026-02) : ajout du fichier CLAUDE.md
- `15.3.0` (2026-03) : amélioration du descripteur CLAUDE.md : ajout des Notes techniques
- `15.3.0` (2026-03) : ajout d'un test de comparaison de la version majeure Dolibarr (avertissement si version non supportée)
- `15.3.1` (2026-03) : compatibilité avec PHP 8.4
- `15.3.2` (2026-03) : ajout de l'affichage de la version Dolinfras dans `infrassupprice_getSupportInformation()`
- `15.3.2` (2026-03) : ajout de la clé de traduction `InfraSSupPriceParamDolinfrasVersion` (fr_FR, en_US, es_ES)
- `15.3.3` (2026-03) : ajout d'une nouvelle famille dynamique dédiée aux modules d'hébergement (branding « Dolibarr LTS by InfraS »)
- Entrées du changelog par version (types : `add`, `chg`, `fix`)

Le module se désactive automatiquement si la version Dolibarr est inférieure au minimum requis. Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée.

## Notes techniques (Technical notes)

### Classe métier `InfraSSupPrice` (Business class)

Fichier : `class/infrassupprice.class.php` — étend `Product`

La classe ne possède qu'une seule méthode publique :

**`InfraS_update_buyprice($fourn, $id_prod, $ref_fourn, $tva_tx, $currency_tx, $currency_code, $currency_unitbuyprice, $unitbuyprice, $qty, $currency_buyprice, $buyprice, $remise_percent)`**

Algorithme en trois étapes :

1. **Recherche** : interroge `llx_product_fournisseur_price` pour trouver un tarif existant (même entité, fournisseur, référence fournisseur, quantité, et devise si multicurrency activé)
2. **Mise à jour** (`UPDATE`) : si un tarif existant est trouvé ET que le prix, la TVA, la remise ou la devise ont changé → mise à jour du tarif
3. **Création** (`INSERT`) : si aucun tarif existant n'est trouvé → insertion d'un nouveau tarif avec `supplier_reputation = "FAVORITE"` et `delivery_time_days = 0`

Codes retour :

| Retour | Signification |
|--------|---------------|
| `0` | Tarif créé ou mis à jour avec succès |
| `1` | Tarif identique, aucune modification nécessaire |
| `-1` | Erreur lors du UPDATE |
| `-2` | Erreur lors de l'INSERT |
| `-3` | Erreur lors du SELECT de recherche |

### Gestion multi-devises (Multi-currency management)

Le module supporte le multi-devises lorsque le module Dolibarr `multicurrency` est activé :

- La recherche de tarif existant inclut un filtre sur `fk_multicurrency` (via `MultiCurrency::getIdFromCode()`)
- Le UPDATE/INSERT inclut les colonnes `multicurrency_tx`, `multicurrency_price`, `multicurrency_unitprice`
- Le tableau du hook affiche les prix en devise étrangère si `$object->multicurrency_tx != 1`
- Colonnes multi-devises concernées : `fk_multicurrency`, `multicurrency_code`, `multicurrency_tx`, `multicurrency_price`, `multicurrency_unitprice`

### Scripts AJAX (`script/interface.php` et `script/message.php`)

Les deux scripts AJAX constituent le mécanisme de communication entre le tableau HTML (côté client) et la logique métier (côté serveur).

**`interface.php`** — Point d'entrée AJAX pour la mise à jour des prix :

```
Requête POST avec put=updateprice
    ↓
Contrôle d'accès : $user->hasRight('infrassupprice', 'update')
    ↓
Instanciation de InfraSSupPrice et Product::fetch($id_prod, $ref_search)
    ↓
Instanciation de Fournisseur et fetch($fk_supplier)
    ↓
Appel InfraS_update_buyprice() avec tous les paramètres GETPOST
    ↓
Retour JSON : { id: <code_retour>, desc: <description_erreur> }
```

**`message.php`** — Affichage des notifications Dolibarr après mise à jour :

| Valeur `msg` | Type notification | Traduction |
|--------------|-------------------|------------|
| `Ok` | `mesgs` (succès) | `InfraSSupPriceMajOk` |
| `Idem` | `warnings` | `InfraSSupPriceMajIdem` |
| `Ko` | `errors` | `InfraSSupPriceMajKo` |
| `noCheck` | `errors` | `InfraSSupPriceMajNoCheck` |
| autre | `errors` | `InfraSSupPriceMajKoElse` (avec message serveur) |

Les deux scripts définissent `NOTOKENRENEWAL` pour éviter l'invalidation du token CSRF lors d'appels AJAX séquentiels, et implémentent un contrôle d'accès via `accessforbidden()`.

### Flux des hooks (Hook workflow)

La classe `Actionsinfrassupprice` intervient sur les contextes `supplier_proposalcard`, `ordersuppliercard`, `invoicesuppliercard` selon ce flux :

```
L'utilisateur accède à une fiche document fournisseur (demande de prix/commande/facture)
    ↓
afterLogin() : vérifie la version max Dolibarr supportée
    via explode('.', DOL_VERSION)[0] vs explode('.', maxVersion)[0]
    ↓
addMoreActionsButtons() : injecte le tableau de comparaison des tarifs
    (conditionné par : statut ≥ 1, au moins une ligne produit, permission 'update')
    ↓
Pour chaque ligne produit du document :
    → Récupère ref, prix unitaire, TVA, quantité, remise, données devise
    → Interroge llx_product_fournisseur_price (même ref, quantité, fournisseur, devise)
    → Affiche les valeurs existantes en tooltip sur chaque champ
    ↓
L'utilisateur coche les lignes et clique sur "Mise à jour"
    ↓
JavaScript : pour chaque ligne cochée, appel AJAX séquentiel vers interface.php
    → POST { put: 'updateprice', idprod, ref, refFour, tvatx, qty, prix, devise... }
    ↓
interface.php : InfraSSupPrice->InfraS_update_buyprice()
    → Retour JSON { id: 0|1|-1|-2|-3, desc: '...' }
    ↓
JavaScript : appel AJAX vers message.php avec le code retour
    → setEventMessages() côté serveur pour notification Dolibarr
    → location.reload() pour rafraîchir la page
```

### Librairie d'administration (`infrassuppriceAdmin.lib.php`)

Le fichier `core/lib/infrassuppriceAdmin.lib.php` contient les fonctions transverses d'administration :

**Fonctions de navigation** :
- `infrassupprice_admin_prepare_head()` — prépare les onglets admin (Paramètres, À propos, Changelog)
- `infrassupprice_no_topmenu()` — vérifie si le menu InfraS existe déjà dans le top menu Outils

**Fonctions de version et changelog** :
- `infrassupprice_test_php_ext()` — teste la disponibilité de l'extension PHP XML, stocke le résultat dans `INFRAS_PHP_EXT_XML`
- `infrassupprice_getLocalVersionMinDoli($appliname)` — parse `docs/changelog.xml` et retourne les informations de version
- `infrassupprice_getChangelogFile($appliname, $from)` — charge et parse un fichier XML changelog (local ou téléchargé)
- `infrassupprice_dwnChangelog($appliname)` — télécharge le dernier changelog depuis `infras.fr` pour comparaison
- `infrasdiscount_getLocalVersionMinDoli($appliname)` — récupère les informations de version du module infrasdiscount (retourne un tableau vide en cas d'échec, depuis 15.3.2)

**Fonctions de sauvegarde/restauration** :
- `infrassupprice_bkup_module($appliname)` — sauvegarde les constantes `INFRASSUPPRICE_%` dans un fichier SQL sous `DOL_DATA_ROOT/{entity}/infrassupprice/sql/update.{entity}`
- `infrassupprice_bkup_table($table, $sql, $listeCols, $duplicate, $truncate, $add)` — génère le SQL d'INSERT pour un jeu de résultats avec support `ON DUPLICATE KEY UPDATE` et remplacement d'entité par `__ENTITY__`
- `infrassupprice_restore_module($appliname)` — restaure les constantes depuis le fichier de sauvegarde via `run_sql()`

**Fonctions d'affichage admin** :
- `infrassupprice_print_backup_restore()` — section HTML sauvegarde/restauration
- `infrassupprice_load_title()` — titre avec picto pour les sections admin
- `infrassupprice_print_colgroup()` — génère les balises `<colgroup>` pour le dimensionnement des colonnes
- `infrassupprice_print_liste_titre()` — en-têtes de tableau admin
- `infrassupprice_print_btn_action()` — bouton d'action avec description
- `infrassupprice_print_hr()` — ligne de séparation
- `infrassupprice_print_subTitle()` — sous-titre de section
- `infrassupprice_print_final()` — ligne de fin de tableau
- `infrassupprice_print_input()` — entrée de formulaire polymorphe (`on_off`, `on_off2`, `input`, `input2`, `radio`, `textarea`, `color`, `select`, `select_produits`, `select_types_paiements`, `selectTypeContact`, `select_type_actions`, `editor`)
- `infrassupprice_print_line_inputs()` — ligne multi-champs avec toggle on/off
- `infrassupprice_getChangeLog()` — affichage HTML complet du changelog avec comparaison version locale/téléchargée (versions nouvelles en orange, versions avancées en vert)
- `infrassupprice_getSupportInformation()` — tableau d'informations de support (versions Dolibarr, Dolinfras, module, PHP, BDD, serveur web)

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="15.3.3" MonthVersion="2026-03">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="20260301"/>
    <Dolibarr minVersion="15.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

La fonction `infrassupprice_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "15.3.3",           // Version courante
    1 => "15.0.0",           // Version min Dolibarr
    2 => 0,                  // Flag erreur (-1 = KO, 0 = OK)
    3 => <SimpleXMLElement>, // Liste des versions (ou message d'erreur)
    4 => "24.x.x",           // Version max Dolibarr
    5 => "7.4",              // Version min PHP
    6 => "8.4"               // Version max PHP
]
```

### Branding dynamique (Dynamic branding)

Le module implémente le système de branding centralisé InfraS :

1. **Détection du module dolinfras** : dans le constructeur, le descripteur vérifie si le module `dolinfras` est activé via `isModEnabled('dolinfras')`
2. **Choix de la famille** :
   - Si `dolinfras` est activé : utilise `getDolGlobalString('DOLINFRAS_FAMILY')` qui contient le HTML de branding « Dolibarr LTS by InfraS » avec polices personnalisées
   - Sinon : utilise la famille standard « Modules InfraS »
3. **Avantages** : branding cohérent sur tous les modules InfraS d'une instance, personnalisation centralisée, affichage enrichi avec polices InfraS

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="15.3.0" MonthVersion="2026-03">
        <change type='chg'>Amélioration du descripteur CLAUDE.md : ajout des Notes Techniques</change>
        <change type='add'>Ajout d'un test de comparaison de la version majeur de Dolibarr supportée</change>
    </Version>
    <InfraS Downloaded="20260301"/>
    <Dolibarr minVersion="15.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

### Cycle de vie du module (Module lifecycle)

**`init()`** effectue dans l'ordre :
1. Chargement des tables SQL (`_load_tables('/infrassupprice/sql/')`) — exécute `data.sql`
2. Restauration des paramètres sauvegardés (`infrassupprice_restore_module`)
3. Initialisation des constantes `INFRASSUPPRICE_DOL_VERSION` et `INFRASSUPPRICE_MAIN_VERSION`

**`remove()`** effectue :
1. Sauvegarde des paramètres (`infrassupprice_bkup_module`)
2. Suppression des constantes `INFRASSUPPRICE_%` de l'entité courante (`DELETE FROM llx_const WHERE name LIKE 'INFRASSUPPRICE\_%' AND entity = ...`)

### Page de configuration (`admin/infrassuppricesetup.php`)

La page d'administration propose :

- **Sauvegarde / Restauration** (visible si `paramBkpRest` ou `admin`) : boutons de sauvegarde et restauration des constantes module
- **Comportement général** : tableau des options avec toggle on/off
  - Option `INFRASSUPPRICE_QTE_NOT_VALUE_MIN` : force la quantité minimum à 1

Le contrôle d'accès est à deux niveaux :
- Niveau 2 (`admin` ou `paramBkpRest`) : accès complet avec sauvegarde/restauration
- Niveau 1 (`paramInfraSSupPrice`) : accès aux paramètres uniquement
- Niveau 0 : accès refusé (`accessforbidden()`)

Les actions `set_*` sont gérées par regex sur le paramètre `action` : `preg_match('/set_(.*)/', $action, $reg)` pour basculer n'importe quelle constante on/off.

### Cas d'usage courants (Common use cases)

#### Cas 1 : Mise à jour d'un tarif fournisseur depuis une commande

1. Ouvrir une commande fournisseur validée (statut ≥ 1)
2. Le tableau de comparaison s'affiche en bas de la fiche (replié par défaut)
3. Déplier le tableau : chaque ligne produit affiche le prix du document et le prix en base (tooltip)
4. Cocher les lignes à mettre à jour
5. Cliquer sur le bouton « Mise à jour »
6. Les tarifs sont créés ou mis à jour dans `llx_product_fournisseur_price`

#### Cas 2 : Mise à jour avec modification de référence fournisseur

1. Si aucun tarif n'existe en base pour un produit, le champ « Réf. fournisseur » est éditable
2. Saisir ou modifier la référence fournisseur
3. Cocher la ligne et lancer la mise à jour
4. Un nouveau tarif fournisseur est créé avec cette référence

#### Cas 3 : Utilisation en multi-devises

1. Le module `multicurrency` Dolibarr doit être activé
2. Sur une commande fournisseur en devise étrangère (`multicurrency_tx != 1`)
3. Le tableau affiche automatiquement les prix en devise étrangère
4. La mise à jour prend en compte le taux de change et les montants en devise étrangère
5. Les colonnes multicurrency de `llx_product_fournisseur_price` sont mises à jour
