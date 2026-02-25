# CLAUDE.md — Contexte module infrastechinfos

## Aperçu (Overview)

`infrastechinfos` est un module externe Dolibarr orienté informations techniques produits/services :

- affichage des dimensions, poids, volume et surface des produits sur les documents commerciaux,
- totalisation des durées de services avec conversions d'unités intelligentes,
- tableau technique repliable injecté via hook sur les fiches documents.

Informations module (issues du code et du changelog local) :

- Éditeur : InfraS
- Numéro module : `500060`
- Licence : GPL v3+
- Compatibilité Dolibarr : `15.0.0` à `22.0.4`
- Compatibilité PHP : `7.4` à `8.4`
- Dernière version locale : `15.1.0` (2026-02)
- Dépendance obligatoire : extension PHP `xml`
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
- **Permissions** : 4 permissions
	- `InfraSTechInfosParamMenu` (défaut : activée)
	- `paramBkpRest`
	- `InfraSTechInfosParamSpecif` (défaut : activée)
	- `InfraSTechInfosView`

### Initialisation (Lifecycle : `init()`)

`init()` effectue :

1. Chargement SQL (`_load_tables('/infrastechinfos/sql/')`)
2. Restauration des constantes module (`infrastechinfos_restore_module`)
3. Initialisation de constantes clés :
	 - `INFRASTECHINFOS_DOL_VERSION`
	 - `INFRASTECHINFOS_MAIN_VERSION`

### Désactivation (Lifecycle : `remove()`)

`remove()` effectue sauvegarde module, puis suppression des constantes `INFRASTECHINFOS_%` de l'entité courante.

## Fonctionnement principal (Core behavior)

Le module s'appuie sur :

- `actions_infrastechinfos.class.php` pour le hook d'injection du tableau technique sur les documents,
- `infrastechinfos.lib.php` pour les fonctions d'affichage (`showDurationAndUnit`, `showDimInBestUnit`),
- `infrastechinfosAdmin.lib.php` pour l'administration (tabs, version, backup/restore, changelog XML, UI helpers).

### Conversions d'unités

Produits (unités exotiques auto-converties en SI avant agrégation) :
- **Poids** : Ounce → 0.0283495 kg, Pound → 0.45359237 kg
- **Volume** : Cubic foot → 0.028317 m³, Cubic inch → 0.000016 m³, Fluid ounce → 0.0000284 m³, Gallon → 0.004546 m³
- **Surface** : Square foot → 0.092903 m², Square inch → 0.000645 m²

Services : durées en mois/années affichent un avertissement (longueur variable non convertible).

## Hooks et comportement (Hook behavior)

La classe `Actionsinfrastechinfos` intervient sur :

- **`addMoreActionsButtons`** : injection d'un tableau technique repliable (jQuery `.foldable_ti`) sur les fiches documents (devis, commandes, expéditions, demandes prix fournisseur, commandes fournisseur)
- **`login`** (via le descripteur) : avertissement version si Dolibarr hors plage supportée

Le tableau affiche les données produits (dimensions, surface, volume, poids) et services (durées) avec totaux par document et conversions d'unités intelligentes.

## Données / SQL (Data model)

Aucune table propre au module. Le module est stateless et lit uniquement les données produit/service existantes de Dolibarr.

Fichier SQL :
- `data.sql` : données initiales (configuration minimale)

## Constantes de configuration (Key settings)

Constantes actives usuelles :

- `INFRASTECHINFOS_DURATION_OF_WORKWEEK` : jours ouvrés par semaine (défaut : 5)
- `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS` : afficher le total temps en jours au lieu d'heures
- `INFRASTECHINFOS_ONLY_TOTAL_TIME` : afficher uniquement le total sans détail par ligne
- `INFRASTECHINFOS_DOL_VERSION` : version Dolibarr au moment de l'activation
- `INFRASTECHINFOS_MAIN_VERSION` : version module au moment de l'activation
- `INFRASTECHINFOS_DISABLE_CHECK_VERSION_MIN` : désactiver le contrôle de version minimale (debug)
- `MAIN_DURATION_OF_WORKDAY` : constante Dolibarr utilisée pour la conversion durées (défaut : 28800 s)

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
- Le module auto-désactive si la version Dolibarr est inférieure à la version minimale requise
- Les durées en mois/années ne sont pas convertibles en secondes et affichent un avertissement
- Le menu InfraS parent est créé automatiquement si aucun autre module InfraS ne l'a déjà fait (`infrastechinfos_no_topmenu()`)

## Dernières mises à jour (Recent updates)

- `15.1.0` (2026-02) : correction XSS sur `$_SERVER['PHP_SELF']` dans les formulaires (about, changelog, setup, lib)
- `15.1.0` (2026-02) : correction XSS sur `$_SERVER['SERVER_SOFTWARE']` dans getSupportInformation
- `15.1.0` (2026-02) : correction injection SQL (cast int sur entity) dans la désinstallation module
- `15.1.0` (2026-02) : typage `GETPOSTINT('value')` dans infrastechinfossetup
- `15.1.0` (2026-02) : remplacement `$user->rights->` par `$user->hasRight()` (setup, hook, descriptor)
- `15.1.0` (2026-02) : remplacement `$conf->global->` par `getDolGlobalInt()` / `getDolGlobalString()` (setup, hook, lib)
- `15.1.0` (2026-02) : remplacement des balises HTML `<FONT>` par `<span>` avec classes CSS
- `15.1.0` (2026-02) : remplacement `<body>` par `<tbody>` dans le hook addMoreActionsButtons
- `15.1.0` (2026-02) : normalisation `else if` → `elseif` conforme PSR-12
- `15.1.0` (2026-02) : alignement des fonctions lib admin sur infraspackplus, infraswidgets et infrassearch (27 corrections)

### Known Limitations

1. **Month/year durations** - Cannot accurately convert to seconds (variable length) - displays warning
2. **Exotic units outside predefined set** - Will aggregate in base unit without conversion factor
3. **No caching** - Technical info recalculated on every document page load
4. **No PDF integration** - Table only appears in web UI, not in generated PDFs

## Calculation Flow Examples

### Product Dimensions and Volume Calculation

1. Order contains 3 product lines:
   - Line 1: 10 boxes of 50×40×30 cm each
   - Line 2: 5 pallets of 120×80×15 cm each
   - Line 3: 2 containers of 2×1.5×1 m each

2. Module calculates individual line volumes:
   - Line 1: 10 × (0.5 × 0.4 × 0.3) = 0.6 m³
   - Line 2: 5 × (1.2 × 0.8 × 0.15) = 0.72 m³
   - Line 3: 2 × (2 × 1.5 × 1) = 6 m³

3. **Total volume**: 0.6 + 0.72 + 6 = 7.32 m³

4. Display uses intelligent unit selection:
   - Small volume (< 0.1 m³) → displays in L or dm³
   - Medium volume (0.1-100 m³) → displays in m³
   - Large volume (> 100 m³) → keeps in m³

### Service Duration Aggregation with Conversion

1. Project proposal with mixed service durations:
   - Service A: 3 days × 2 units = 6 days
   - Service B: 16 hours × 1 unit = 16 hours
   - Service C: 2 weeks × 1 unit = 2 weeks

2. Configuration:
   - `MAIN_DURATION_OF_WORKDAY` = 28800 (8 hours)
   - `INFRASTECHINFOS_DURATION_OF_WORKWEEK` = 5 days

3. Conversion to common unit (hours):
   - 6 days × 8 hours/day = 48 hours
   - 16 hours = 16 hours
   - 2 weeks × 5 days/week × 8 hours/day = 80 hours

4. **Total**: 48 + 16 + 80 = 144 hours

5. Optional display as workdays:
   - `INFRASTECHINFOS_TOTAL_TIME_IN_DAYS` = 1
   - Display: 144 hours ÷ 8 = 18 workdays

### Weight Conversion with Exotic Units

1. Supplier order with international products:
   - Product A: 50 kg × 10 units = 500 kg
   - Product B: 10 lb × 5 units = 50 lb → 22.68 kg
   - Product C: 200 oz × 3 units = 600 oz → 17.01 kg

2. Conversion to base unit (kg):
   - 1 lb = 0.45359237 kg
   - 1 oz = 0.0283495 kg

3. **Total weight**: 500 + 22.68 + 17.01 = 539.69 kg

4. Display with 2 decimal precision: **539.69 kg**

### Surface Calculation with Mixed Units

1. Quote for flooring materials:
   - Tile A: 30 cm × 30 cm × 100 units = 90,000 cm²
   - Tile B: 1 m × 0.5 m × 20 units = 10 m²
   - Total: 90,000 cm² + 10 m² = 9 m² + 10 m² = 19 m²

2. Intelligent unit display:
   - Original: 190,000 cm² (hard to read)
   - Converted: **19.00 m²** (readable)

## Common Functions Reference

### Display Functions (`core/lib/infrastechinfos.lib.php`)

```php
// Format and display a duration with proper unit label
infrastechinfos_showDurationAndUnit($duration, $unit)
// Parameters:
//   $duration - numeric duration value
//   $unit - 'i'=minutes, 'h'=hours, 'd'=days, 'w'=weeks, 'm'=months, 'y'=years
// Returns: Formatted string (e.g., "5 Hours", "1 Day")
// Note: Handles singular/plural forms automatically

// Display dimension in best readable unit with automatic conversion
infrastechinfos_showDimInBestUnit($dimension, $unit, $type, $outputlangs, $round = -1, $forceunitoutput = 'no')
// Parameters:
//   $dimension - numeric value in base unit
//   $unit - current unit scale (0=base, -3=milli, 3=kilo, etc.)
//   $type - 'weight', 'volume', 'surface'
//   $outputlangs - Translate object for localization
//   $round - decimal places (-1=auto, 0-9=fixed)
//   $forceunitoutput - 'no' for auto, or numeric scale to force
// Returns: Formatted string with best unit (e.g., "15.50 kg", "2.30 m³")
// Logic:
//   - dimension < 0.0001 → convert to micro units (-6)
//   - dimension < 0.1 → convert to milli units (-3)
//   - dimension > 100,000,000 → convert to mega units (+6)
//   - dimension > 100,000 → convert to kilo units (+3)
```

### Admin Functions (`core/lib/infrastechinfosAdmin.lib.php`)

```php
// Build admin page tabs array
infrastechinfos_admin_prepare_head()
// Returns: Array of tab definitions for dol_get_fiche_head()

// Check if InfraS top menu already exists
infrastechinfos_no_topmenu()
// Returns: 0 if menu exists, 1 if needs creation

// Test required PHP extensions (xml)
infrastechinfos_test_php_ext()
// Side effect: Sets INFRAS_PHP_EXT_XML constant (1=OK, -1=missing)

// Get local version from changelog.xml
infrastechinfos_getLocalVersionMinDoli($appliname)
// Returns: Array [version, minDolibarr, errorFlag, versionsList, maxDolibarr, minPHP, maxPHP]

// Parse changelog.xml file
infrastechinfos_getChangelogFile($appliname, $from = '')
// Returns: SimpleXMLElement object or false on error

// Download changelog from remote server
infrastechinfos_dwnChangelog($appliname)
// Returns: Download status message

// Backup module configuration to SQL file
infrastechinfos_bkup_module($appliname)
// Side effect: Creates backup SQL file in DOL_DATA_ROOT/admin/infrastechinfos/

// Restore module configuration from SQL file
infrastechinfos_restore_module($appliname)
// Side effect: Executes SQL from backup file if exists

// UI helper - print title section
infrastechinfos_load_title($titre, $morehtmlright = '', $picto = 'generic', ...)

// UI helper - print form input field
infrastechinfos_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', ...)
// Supported tags: 'on_off', 'text', 'select', 'multiselect', 'textarea'
```

## Integration with Dolibarr Core

The module extends core Dolibarr functionality:

- **Product data**: Reads `weight`, `weight_units`, `length`, `width`, `height`, `length_units`, `surface`, `surface_units`, `volume`, `volume_units` from `llx_product`
- **Service data**: Reads `duration_value`, `duration_unit` from `llx_product` for services
- **Document lines**: Accesses `$object->lines` array with product/service references and quantities
- **Unit system**: Uses `measuring_units_string()` from `/core/lib/product.lib.php` for unit labels
- **Multi-entity**: Respects entity boundaries when fetching product data
- **Permissions**: Integrates with Dolibarr rights system (`$user->rights->infrastechinfos->InfraSTechInfosView`)

## Technical Notes

- **Unit conversion accuracy**: All conversions use exact SI conversion factors (no approximations)
- **Performance**: Product data fetched once per document load (not cached) - consider enabling Dolibarr's object cache for large orders
- **jQuery dependency**: Requires jQuery (standard in Dolibarr) for collapsible table toggle
- **Responsive design**: CSS adapts column widths for mobile/tablet viewing
- **Rounding precision**: Line-by-line values rounded to raw precision, totals to 2 decimals for readability
- **Empty value handling**: Lines with empty/zero technical fields are not displayed (reduces clutter)
- **Document types**: Only works on documents with line items (not applicable to third parties, projects, etc.)
- **PDF limitation**: Technical info table is HTML-only - PDF generation hooks not yet implemented
- **Month/year warning**: Red caution message appears when services use monthly/yearly durations (cannot convert to seconds reliably)
- **Multi-currency**: Module is currency-agnostic (works with all currencies since it only displays physical units)
- **Backward compatibility**: Maintains compatibility with Dolibarr 15+ through conditional function checks
