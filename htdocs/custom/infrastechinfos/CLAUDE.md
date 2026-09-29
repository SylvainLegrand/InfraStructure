# CLAUDE.md — Contexte module infrastechinfos

## Aperçu (Overview)

`infrastechinfos` est un module externe Dolibarr orienté informations techniques produits/services :

- affichage des dimensions, poids, volume et surface des produits sur les documents commerciaux,
- totalisation des durées de services avec conversions d'unités intelligentes,
- tableau technique repliable injecté via hook sur les fiches documents.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS - Sylvain Legrand
- Numéro module : `500060`
- Position dans la famille (`module_position`) : `100020` — famille `DOLINFRAS_FAMILY` « Dolibarr LTS by InfraS » quand dolinfras est activé
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `24.x.x`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.2.4` (2026-09)
- Dépendance obligatoire : aucune (extension PHP `xml` requise)
- Emplacement : `htdocs/custom/infrastechinfos/`

Convention de lecture du descripteur :

- Explications fonctionnelles en français
- Identifiants techniques conservés en anglais (`hooks`, classes, méthodes, constantes, clés de configuration)

## Structure (Summary)

```text
htdocs/custom/infrastechinfos/
├── CLAUDE.md
├── LICENSE
├── README.md
├── admin/
│   ├── about.php
│   ├── changelog.php
│   └── infrastechinfossetup.php
├── class/
│   └── actions_infrastechinfos.class.php
├── config.php
├── core/
│   ├── lib/
│   │   ├── infrastechinfos.lib.php
│   │   └── infrastechinfosAdmin.lib.php
│   └── modules/
│       └── modinfrastechinfos.class.php
├── css/
│   ├── NeuropolRegular.ttf
│   ├── infrastechinfos.css.php
│   └── puentebold.ttf
├── docs/changelog.xml
├── img/
├── langs/
│   ├── en_US/infrastechinfos.lang
│   ├── es_ES/infrastechinfos.lang
│   └── fr_FR/infrastechinfos.lang
└── sql/
    └── data.sql
```

## Descripteur module (Module descriptor : `modinfrastechinfos`)

Dans `core/modules/modinfrastechinfos.class.php` :

- **Module parts** :
	- hooks : `login`, `propalcard`, `ordercard`, `expeditioncard`, `supplier_proposalcard`, `ordersuppliercard`
	- CSS : `/infrastechinfos/css/infrastechinfos.css.php`
- **Dépendances** : aucune dépendance module (extension PHP `xml` requise)
- **Dictionnaires** : aucun dictionnaire
- **Boxes** : aucune
- **Cron** : aucune tâche
- **ExtraFields** : aucun
- **Constantes** : aucune constante prédéfinie (les constantes sont créées via `data.sql` et `init()`)
- **Permissions** : 4 permissions
	- `InfraSTechInfosParamMenu` (défaut : activée)
	- `paramBkpRest`
	- `InfraSTechInfosParamSpecif` (défaut : activée)
	- `InfraSTechInfosView`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrastechinfos/sql/')`) — exécute `data.sql`
2. Restauration des constantes module (`infrastechinfos_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASTECHINFOS_DOL_VERSION`
	 - `INFRASTECHINFOS_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue :

- sauvegarde module (`infrastechinfos_bkup_module`),
- suppression des constantes `INFRASTECHINFOS_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrastechinfos.class.php` pour les hooks d'injection du tableau technique sur les documents,
- `infrastechinfos.lib.php` pour les fonctions de calcul et d'affichage (`showDurationAndUnit`, `showDimInBestUnit`),
- `infrastechinfosAdmin.lib.php` pour les fonctions admin (onglets, changelog, backup/restore, vérification de mise à jour, UI helpers).

### Types de données techniques

Deux catégories de données sont traitées :

1. **Produits** (`product_type=0`) : dimensions (L×l×H), surface, volume et poids — avec calcul du total par quantité et agrégation par document
2. **Services** (`product_type=1`) : durées — avec conversion en secondes et totalisation par document

### Conversions d'unités (Unit conversion)

Produits — unités exotiques auto-converties en SI avant agrégation :

| Grandeur | Unité exotique | Code | Facteur de conversion |
|----------|---------------|------|----------------------|
| Poids | Ounce | 98 | 0.0283495 kg |
| Poids | Pound | 99 | 0.45359237 kg |
| Volume | Cubic foot | 88 | 0.028316846592 m³ |
| Volume | Cubic inch | 89 | 0.000016387064 m³ |
| Volume | Fluid ounce | 98 | 0.0000284130625 m³ |
| Volume | Gallon | 99 | 0.00454609 m³ |
| Surface | Square foot | 98 | 0.09290304 m² |
| Surface | Square inch | 99 | 0.00064516 m² |

Unités standard (code < 50) : facteur = `pow(10, code)` (puissance de 10 de l'unité officielle).

Services — conversion de durées en secondes :

| Unité | Code | Multiplicateur |
|-------|------|---------------|
| Heures | `h` | 3600 |
| Jours | `d` | `MAIN_DURATION_OF_WORKDAY` (défaut : 28800 s) |
| Semaines | `w` | `MAIN_DURATION_OF_WORKDAY` × `INFRASTECHINFOS_DURATION_OF_WORKWEEK` |
| Mois | `m` | 0 (non convertible — avertissement affiché) |
| Années | `y` | 0 (non convertible — avertissement affiché) |

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrastechinfos` (dans `class/actions_infrastechinfos.class.php`) intervient sur les contextes `propalcard`, `ordercard`, `expeditioncard`, `supplier_proposalcard`, `ordersuppliercard` :

| Hook | Contexte | Retour | Rôle |
|------|----------|--------|------|
| `afterLogin` | `login` | 0 | Vérifie la version max Dolibarr supportée via `explode('.', DOL_VERSION)[0]` vs `explode('.', maxVersion)[0]` et affiche un avertissement si dépassement (sauf si `INFRASTECHINFOS_DISABLE_CHECK_VERSION_MAX` activé) |
| `addMoreActionsButtons` | `propalcard`, `ordercard`, `expeditioncard`, `supplier_proposalcard`, `ordersuppliercard` | 0 | Injection d'un tableau technique repliable en bas des fiches documents (conditionné par : lignes > 0, permission `InfraSTechInfosView`, élément dans `['propal', 'commande', 'shipping', 'supplier_proposal', 'order_supplier']`) |

### Flux des hooks (Hook workflow)

```
L'utilisateur accède à une fiche document (devis/commande/expédition/demande prix fournisseur/commande fournisseur)
    ↓
afterLogin() : vérifie la version max Dolibarr supportée
    via explode('.', DOL_VERSION)[0] vs explode('.', maxVersion)[0]
    ↓
addMoreActionsButtons() : injecte le tableau technique repliable
    (conditionné par : lignes > 0, permission 'InfraSTechInfosView',
     élément dans ['propal', 'commande', 'shipping', 'supplier_proposal', 'order_supplier'])
    ↓
Pour chaque ligne du document :
    → Ignore les lignes sans fk_product
    → Charge le produit/service via Product::fetch()
    → Si service (type=1) : calcule la durée unitaire et totale,
                             avertissement si unité mois/année
    → Si produit (type=0) : extrait dimensions, surface, volume, poids,
                             convertit les unités exotiques en SI,
                             cumule les totaux document
    ↓
Affichage du tableau HTML avec jQuery toggle (.foldable_ti)
    → Section produits : N°, Réf, Dimensions, Surface, Volume, Poids, Qté, Totaux
    → Section services : N°, Réf, Durée, Qté, Durée totale
    → Ligne de totaux document (surface, volume, poids, durée)
```

### Modes d'affichage des durées

Trois modes contrôlés par constantes :

| Mode | Constante | Comportement |
|------|-----------|-------------|
| Détaillé | `INFRASTECHINFOS_ONLY_TOTAL_TIME = 0` | Tableau complet avec chaque ligne de service |
| Total seul | `INFRASTECHINFOS_ONLY_TOTAL_TIME = 1` | Une seule ligne avec la liste des N° de lignes et le total |
| En jours | `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS = 1` | Total affiché en semaines/jours/heures au lieu d'heures/minutes |

## Données / SQL (Data model)

Le module ne crée aucune table SQL propre. Toute la configuration est stockée dans `llx_const`.

Fichier SQL :
- `data.sql` : constantes initiales (`INFRASTECHINFOS_DURATION_OF_WORKWEEK`, `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS`, `INFRASTECHINFOS_ONLY_TOTAL_TIME`)

Le module est stateless et lit uniquement les données produit/service existantes de Dolibarr (`llx_product`).

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASTECHINFOS_DURATION_OF_WORKWEEK` — jours ouvrés par semaine (défaut : 5)
- `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS` — afficher le total temps en jours au lieu d'heures
- `INFRASTECHINFOS_ONLY_TOTAL_TIME` — afficher uniquement le total sans détail par ligne
- `INFRASTECHINFOS_DOL_VERSION` — version Dolibarr au moment de l'activation
- `INFRASTECHINFOS_MAIN_VERSION` — version module au moment de l'activation
- `INFRASTECHINFOS_DISABLE_CHECK_VERSION_MIN` — désactiver le contrôle de version minimale (debug)
- `INFRASTECHINFOS_DISABLE_CHECK_VERSION_MAX` — désactiver le contrôle de version maximale (debug)
- `MAIN_DURATION_OF_WORKDAY` — constante Dolibarr utilisée pour la conversion durées (défaut : 28800 s = 8 h)

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
2. Vérifier les constantes module (`INFRASTECHINFOS_*`)
3. Vérifier l'affichage du tableau technique sur un devis avec produits et services
4. Vérifier les conversions d'unités et les totaux
5. Vérifier les pages admin (setup, changelog, about)

## Points d'attention (Watchpoints)

- La version locale est lue depuis `docs/changelog.xml` (`infrastechinfos_getLocalVersionMinDoli`)
- L'extension PHP XML est nécessaire pour parser le changelog
- Le module est auto-désactivé si la version Dolibarr est inférieure au minimum requis
- Un avertissement s'affiche à la connexion si Dolibarr dépasse la version max supportée
- Les durées en mois/années ne sont pas convertibles en secondes et affichent un avertissement
- Le menu InfraS parent est créé automatiquement si aucun autre module InfraS ne l'a déjà fait (`infrastechinfos_no_topmenu()`)
- Les lignes sans `fk_product` sont ignorées (lignes libres sans référence produit)

## Dernières mises à jour (Recent updates)

Voir `docs/changelog.xml` pour l'historique complet des versions.

## Notes techniques (Technical notes)

### Moteur de calcul technique (Technical calculation engine)

Le fichier `infrastechinfos.lib.php` contient les deux fonctions de calcul/affichage du module :

**`infrastechinfos_showDurationAndUnit($duration, $unit)`** :
- Formate une durée avec son libellé d'unité localisé
- Gère automatiquement le singulier/pluriel (`Hour` vs `Hours`)
- Unités supportées : `i` (minutes), `h` (heures), `d` (jours), `w` (semaines), `m` (mois), `y` (années)

**`infrastechinfos_showDimInBestUnit($dimension, $unit, $type, $outputlangs, $round, $forceunitoutput)`** :
- Convertit et affiche une dimension dans l'unité la plus lisible
- Utilise `measuring_units_string()` de Dolibarr pour les labels d'unités
- Utilise `price()` pour le formatage numérique localisé

Logique de sélection automatique de l'unité d'affichage :

```
dimension < 0.0001       → micro (×1 000 000, unit -6)
dimension < 0.1          → milli (×1 000, unit -3)  [surface: unit -2]
dimension > 100 000 000  → méga  (÷1 000 000, unit +6)
dimension > 100 000      → kilo  (÷1 000, unit +3)  [surface: ÷10 000, unit +4]
sinon                    → unité de base
```

### Flux de calcul dans le hook (Calculation flow in hook)

Le hook `addMoreActionsButtons` parcourt toutes les lignes du document :

**Pour les produits (type=0)** :
1. Charge le produit via `Product::fetch($idprod)`
2. **Dimensions** : concatène L×l×H avec l'unité (`measuring_units_string`)
3. **Poids** : calcule le poids unitaire × quantité, convertit les unités exotiques (codes 98/99) en kg
4. **Volume** : calcule le volume unitaire × quantité, convertit les unités exotiques (codes 88/89/98/99) en m³
5. **Surface** : calcule la surface unitaire × quantité, convertit les unités exotiques (codes 98/99) en m²
6. Cumule les totaux document pour poids, volume et surface

**Pour les services (type=1)** :
1. Charge le service via `Product::fetch($idprod)`
2. Convertit la durée unitaire en secondes selon le multiplicateur d'unité
3. Si unité mois (`m`) ou année (`y`) : multiplicateur = 0, avertissement affiché en rouge
4. Cumule le total document en secondes
5. Affiche via `convertSecondToTime()` de Dolibarr (format `allhourmin` ou `all` selon configuration)

### Affichage du changelog (`infrastechinfos_getChangeLog`)

La fonction génère un HTML complet comprenant :

1. **Bannière de support** : header avec logo InfraS, liens vers le wiki, le store, le dolistore, le formulaire de support (pré-rempli avec module/version/PHP/Dolibarr), et le badge Dolibarr Preferred Partner
2. **Tableau comparatif** : trois cas d'affichage selon la comparaison local vs téléchargé :
   - **Nouvelles versions disponibles** (`count(downloaded) > count(local)`) : fond orange (`.infrastechinfoschangelogbgorange`) pour les versions non installées
   - **Version en avance** (`count(downloaded) < count(local)`) : fond vert (`.infrastechinfoschangelogbggreen`) pour les versions en avance
   - **À jour ou pas de connexion** : affichage simple sans coloration
3. **Bouton de vérification** : soumission de formulaire pour déclencher `infrastechinfos_dwnChangelog()` (masqué si `INFRAS_SKIP_CHECKVERSION` est activé)

### Page de configuration (Setup page)

`admin/infrastechinfossetup.php` gère les paramètres du module :

| Action | Constantes modifiées |
|--------|---------------------|
| `bkupParams` | Sauvegarde toutes les constantes `INFRASTECHINFOS_%` dans un fichier SQL |
| `restoreParams` | Restaure les constantes depuis le fichier de sauvegarde |
| `set_{confkey}` | Active/désactive un toggle on/off |
| `update_Gen` | Met à jour `MAIN_DURATION_OF_WORKDAY` et `INFRASTECHINFOS_DURATION_OF_WORKWEEK` |

Paramètres configurables :

| N° | Constante | Type | Valeur | Description |
|----|-----------|------|--------|-------------|
| 2 | `MAIN_DURATION_OF_WORKDAY` | number (3600–86400, step 3600) | 28800 | Durée d'un jour ouvré en secondes |
| 3 | `INFRASTECHINFOS_DURATION_OF_WORKWEEK` | number (4–7) | 5 | Nombre de jours ouvrés par semaine |
| 4 | `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS` | on/off | 0 | Afficher le total en jours |
| 5 | `INFRASTECHINFOS_ONLY_TOTAL_TIME` | on/off | 0 | Afficher uniquement le total |

### Gestion des menus (Menu management)

Le module gère sa propre entrée dans le menu « Outils » :

1. `infrastechinfos_no_topmenu()` vérifie si un menu InfraS existe déjà (`SELECT rowid FROM llx_menu WHERE mainmenu = "tools" AND leftmenu = "infras"`)
2. Si aucun menu InfraS n'existe : crée l'entrée parent « InfraS » sous « Outils »
3. Crée les sous-entrées : titre module, lien changelog, lien paramètres
4. Le lien paramètres est conditionné par les permissions `InfraSTechInfosParamMenu` ET `InfraSTechInfosParamSpecif`

### Structure du changelog (Changelog structure)

```xml
<changelog>
    <Version Number="15.2.3" MonthVersion="2026-06">
      <change type='add'>Added feature description.</change>
      <change type='chg'>Changed feature description.</change>
      <change type='fix'>Fixed bug description.</change>
    </Version>
    <InfraS Downloaded="20260619"/>
    <Dolibarr minVersion="15.0.0" maxVersion="24.x.x"/>
    <PHP minVersion="7.4" maxVersion="8.4"/>
</changelog>
```

La fonction `infrastechinfos_getLocalVersionMinDoli()` parse ce XML et retourne un tableau :
```php
[
    0 => "15.2.3",           // Version courante
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
1. Chargement des tables SQL (`_load_tables('/infrastechinfos/sql/')`) — exécute `data.sql`
2. Restauration des paramètres sauvegardés (`infrastechinfos_restore_module`)
3. Enregistrement de `INFRASTECHINFOS_DOL_VERSION` et `INFRASTECHINFOS_MAIN_VERSION`
4. Appel de `$this->_init()` standard

**`remove()`** effectue :
1. Sauvegarde des paramètres (`infrastechinfos_bkup_module`) dans `DOL_DATA_ROOT/{entity}/infrastechinfos/sql/update.{entity}`
2. Nettoyage SQL : suppression des constantes `INFRASTECHINFOS_%` de l'entité courante
3. **Note** : le module ne crée pas de tables SQL, donc aucun DROP TABLE nécessaire

**`getLocalVersion()`** effectue :
1. Vérifie l'extension PHP XML via `INFRAS_PHP_EXT_XML`
2. Parse `docs/changelog.xml` via `infrastechinfos_getLocalVersionMinDoli()`
3. Définit `need_dolibarr_version`, `phpmin`, `phpmax`
4. Désactive le module si `DOL_VERSION < minVersion` (sauf si `INFRASTECHINFOS_DISABLE_CHECK_VERSION_MIN`)
5. Retourne le numéro de version courante

### Sauvegarde et restauration (Backup and restore)

Le mécanisme de backup/restore permet de préserver les paramètres lors de la désactivation/réactivation :

**`infrastechinfos_bkup_module()`** :
1. Crée le répertoire `DOL_DATA_ROOT/{entity}/infrastechinfos/sql/` si nécessaire
2. Écrit un dump SQL des constantes `INFRASTECHINFOS_%` dans `update.{entity}`
3. Copie le fichier de sauvegarde horodaté dans `DOL_DATA_ROOT/{entity}/admin/`
4. Utilise `__ENTITY__` comme placeholder pour la portabilité multi-entité
5. Gère les conflits via `ON DUPLICATE KEY UPDATE`

**`infrastechinfos_restore_module()`** :
1. Cherche le fichier `update.{entity}` dans le répertoire SQL
2. Le copie avec extension `.sql` puis l'exécute via `run_sql()`
3. Supprime le fichier temporaire `.sql` après exécution

### Intégration avec les données Dolibarr (Dolibarr data integration)

Le module lit les données techniques directement depuis les objets Dolibarr :

| Donnée | Champ produit | Champ unité | Type d'objet |
|--------|--------------|-------------|-------------|
| Poids | `weight` | `weight_units` | Produit |
| Volume | `volume` | `volume_units` | Produit |
| Surface | `surface` | `surface_units` | Produit |
| Longueur | `length` | `length_units` | Produit |
| Largeur | `width` | `length_units` | Produit |
| Hauteur | `height` | `length_units` | Produit |
| Durée | `duration_value` | `duration_unit` | Service |

Les dimensions du document sont basées sur `$object->lines[$i]->ref`, `$object->lines[$i]->qty` et `$object->lines[$i]->fk_product`.

**Flux d'extraction des données** :
```php
// Pour chaque ligne du document
foreach ($object->lines as $line) {
    if (empty($line->fk_product)) continue; // Ignore lignes libres
    
    $product = new Product($db);
    $product->fetch($line->fk_product);
    
    if ($product->type == 1) { // Service
        $duration_seconds = $line->duration_value * $unit_multiplier;
        $total_seconds += $duration_seconds;
    } else { // Produit
        $weight_total = $product->weight * $line->qty * $unit_factor;
        $volume_total = $product->volume * $line->qty * $unit_factor;
        // etc.
    }
}
```

### Sécurité (Security)

Le module implémente les protections standards Dolibarr :

- **CSRF** : token Dolibarr (`newToken()`) inclus dans tous les formulaires
- **XSS** : toutes les sorties utilisateur échappées via `dol_escape_htmltag()`
- **PHP_SELF** : échappé via `dol_escape_htmltag()` dans les attributs `action` des formulaires
- **XXE** : `simplexml_load_string()` appelé avec `LIBXML_NONET` pour bloquer les entités externes lors du parsing du changelog
- **Contrôle d'accès** : permission `InfraSTechInfosView` requise pour afficher le tableau technique
- **Injection SQL** : non applicable (le module ne fait pas de requêtes SQL custom, utilise uniquement l'ORM Dolibarr)

### Limitations connues (Known limitations)

1. **Durées en mois/années** — ne peuvent pas être converties en secondes de manière fiable (longueur variable), avertissement affiché en rouge
2. **Unités exotiques hors ensemble prédéfini** — agrégées en unité de base sans facteur de conversion
3. **Pas de cache** — les informations techniques sont recalculées à chaque chargement de page
4. **Pas d'intégration PDF** — le tableau n'apparaît que dans l'interface web, pas dans les documents PDF générés
5. **Lignes sans référence produit** — les lignes libres (sans `fk_product`) sont ignorées

### Cas d'usage courants (Common use cases)

#### Cas 1 : Consultation du poids total d'une commande

1. Ouvrir une commande avec des produits ayant un poids renseigné
2. Le tableau technique apparaît automatiquement en bas de page (replié par défaut)
3. Cliquer sur la barre de titre pour déplier
4. Lire le poids par ligne (unitaire × quantité) et le total document
5. Les unités exotiques (lb, oz) sont automatiquement converties en kg

#### Cas 2 : Totalisation des durées de services sur un devis

1. Créer un devis avec plusieurs lignes de services ayant des durées différentes (heures, jours, semaines)
2. Le tableau calcule le total en secondes en utilisant les facteurs de conversion configurés
3. Le total est affiché en heures:minutes (ou semaines/jours/heures si `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS=1`)
4. Si une ligne utilise des mois ou années, un avertissement rouge est affiché

#### Cas 3 : Configuration des durées de travail

1. Accéder à l'admin du module (Outils → InfraS → Paramètres)
2. Ajuster la durée de la journée de travail (`MAIN_DURATION_OF_WORKDAY`, en secondes)
3. Ajuster le nombre de jours par semaine (`INFRASTECHINFOS_DURATION_OF_WORKWEEK`)
4. Les totaux de durées sont recalculés selon ces paramètres
