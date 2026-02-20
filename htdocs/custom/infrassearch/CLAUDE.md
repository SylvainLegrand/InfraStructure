# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**InfraSSearch** is a Dolibarr extension that provides advanced cross-module search capabilities. It allows simultaneous searching across all Dolibarr object types (third parties, contacts, products/services, invoices, orders, proposals, contracts, interventions, projects, tasks, events, etc.) including their extra fields. It also provides a breadcrumb navigation history feature.

- **Module ID:** 550080
- **Dolibarr Compatibility:** 15 - 22
- **PHP Compatibility:** 7.4 - 8.4
- **License:** GPL v3+
- **Dependencies:** Requires PHP `xml` extension for changelog parsing
- **Author:** InfraS - Sylvain Legrand

## Development Commands

No local test runner. Version is managed in `docs/changelog.xml` (XML format, read by module descriptor via `infrassearch_getLocalVersionMinDoli()`).

## Architecture

### Core Classes

| File | Purpose |
|------|---------|
| `core/modules/modinfrassearch.class.php` | Module descriptor (ID 550080) - permissions, menus, hooks, table creation, version management from changelog.xml |
| `class/actions_infrassearch.class.php` | Hook handler for `searchform`, `toprightmenu`, `adminmodules` - injects search UI, breadcrumb dropdown, manages module disable cleanup |
| `core/lib/infrassearch.lib.php` | Search helper functions - `printDropdownBreadCrumb()` for history, `getobjectclass()` for dynamic object type resolution |
| `core/lib/infrassearchAdmin.lib.php` | Admin functions - tab builder, version/changelog management, backup/restore, changelog XML parsing, download update check |
| `script/interface.php` | AJAX endpoint for search queries - handles both `search` (per-type, from tools page) and `search-all` (all types, from top menu autocomplete) |

### Search Modes

The module offers three search integration points:

1. **Top menu input** (`INFRASSEARCH_ON_TOP_MENU`) - An autocomplete text field in the top-right menu bar with jQuery UI autocomplete, grouped results by object type
2. **Left menu replacement** (`INFRASSEARCH_REPLACE_STD`) - Replaces the standard Dolibarr search form in the left menu with InfraSSearch autocomplete
3. **Search entry addition** - Adds an InfraSSearch entry to the standard Dolibarr search dropdown (when not replacing it)
4. **Tools page** (`search.php`) - Full search page under Tools menu with per-module AJAX results displayed in tile layout

### Hook Integration

The `Actionsinfrassearch` class hooks into:
- **`toprightmenu`** (`printTopRightMenu`): Injects search input and/or breadcrumb dropdown in the top-right bar
- **`searchform`** (`printSearchForm`): Replaces or augments the standard search form
- **`searchform`** (`addSearchEntry`): Adds InfraSSearch as a search provider in the dropdown
- **`adminmodules`** (`doActions`): Cleans up module-specific search constants when a module is disabled
- **`commonFooter`** (`printCommonFooter`): Records breadcrumb history (visited objects) per user

### Database

Single table `llx_infrassearch_history` stores breadcrumb navigation history:

| Column | Type | Purpose |
|--------|------|---------|
| `rowid` | INTEGER AUTO_INCREMENT | Primary key |
| `entity` | INTEGER | Multi-company entity ID |
| `element` | VARCHAR(64) | Object type (e.g., 'societe', 'facture') |
| `fk_element` | INTEGER | Object ID |
| `fk_user` | INTEGER | User ID |
| `tms` | TIMESTAMP | Auto-updated timestamp |

History is auto-cleaned monthly (keeps only last month's entries).

Schema in `sql/llx_infrassearch_history.sql`, initial data in `sql/data.sql`, migrations in `sql/update_data.sql`.

### Search Engine (`script/interface.php`)

The `_search()` function builds dynamic SQL queries per object type:
- Joins main table with extrafields, line items (for orders/invoices), products, companies, and contacts
- Searches across all text columns of all joined tables using `LIKE` with the keyword
- Supports configurable result count (`INFRASSEARCH_NB_ROWS`), sorting (`INFRASSEARCH_SORT`/`INFRASSEARCH_ORDER`), and entity filtering (`INFRASSEARCH_ONLY_IN_ENTITY`)
- Optionally shows the matched field content with highlighting (`INFRASSEARCH_SHOW_FIND_FIELD`)

#### Phone Number Search

Phone search is normalized to support diverse input formats:
- **Digit extraction**: Characters `+`, `-`, `.`, `(`, `)`, `/`, ` ` are stripped from both the keyword and DB values via nested `REPLACE()` SQL
- **Phone detection**: A keyword is treated as phone-like when it contains ≥5 digits and only phone characters (`+`, digits, spaces, `-`, `.`, `(`, `)`, `/`)
- **Phone fields**: Columns matching `/phone|fax|mobile|tel/i` get additional normalized LIKE comparisons
- **Madagascar prefix conversion**: Automatic cross-search between local (`034...`) and international (`+261 34...`, `00261 34...`) formats
- **INT safety**: When a keyword looks like a phone number, INT column comparisons are skipped entirely to avoid query pollution (hundreds of useless `OR field = N` on every `rowid`, `entity`, `fk_*` column)
- **Overflow protection**: Numeric keywords exceeding `INT MAX` (2147483647) are excluded from INT comparisons

#### Performance Optimizations

- **Static DESCRIBE cache**: `DESCRIBE` results are cached in a `static` variable across `_search()` calls. Shared tables (`societe`, `socpeople`, `product`) appear in ~9 modules each — the cache reduces DESCRIBE queries by ~38% on a typical autocomplete request
- **Column filtering**: Technical columns are excluded from WHERE clauses: `rowid`, `entity`, `import_key`, `model_pdf`, `last_main_doc`, `extraparams`, `fk_object`, `tms`, `multicurrency_*`, `rang`, `special_code`, `fk_unit`, `fk_parent_line`, `fk_user_*`, plus all `fk_*` prefixed columns. This reduces WHERE conditions by ~34%
- **Pre-computed type checks**: `_isDate()`, `is_numeric()`, and escape operations are computed once before the column loop instead of per-column
- **SQL error logging**: Failed queries are logged via `dol_syslog()` instead of silently returning 0 results (since `$db->num_rows(false)` returns 0 without error)

#### Security

- `escapeforlike()` + `escape()` on all LIKE patterns (protects against `%`, `_`, `\` injection)
- `preg_quote()` on keywords before use in regex patterns (prevents regex compilation errors with `+`, `.`, etc.)
- Integer cast `(int)` for all numeric WHERE conditions
- `$db->escape()` for all string comparisons

Supported object types (configurable per-module): `agenda`, `categorie`, `commande`, `commandefournisseur`, `contact`, `contacttracking`, `contrat`, `domain`, `equipement`, `expedition`, `expensereport`, `factory`, `facture`, `facturefournisseur`, `ficheinter`, `hosting`, `knowledgemanagement`, `ndfp`, `product`, `projet`, `propal`, `propalehistory`, `rmindr`, `societe`, `supplier_proposal`, `task`, `ticketsup`

### Breadcrumb Feature

When `INFRASSEARCH_BREADCRUMB` is enabled, a clock icon dropdown in the top menu shows recently visited objects (via the `printCommonFooter` hook). The number of items is configurable (`INFRASSEARCH_NB_BREADCRUMB`). Uses a keyboard shortcut (Ctrl+Shift+K) for quick access.

### Admin Pages

| File | Tab | Purpose |
|------|-----|---------|
| `admin/infrassearchsetup.php` | Settings | Search behavior, module selection/ordering, breadcrumb config, backup/restore |
| `admin/about.php` | About | README.md rendered as HTML |
| `admin/changelog.php` | Changelog | Version history from XML, support info, update check |

### Key Configuration Constants

| Constant | Purpose | Default |
|----------|---------|---------|
| `INFRASSEARCH_ON_TOP_MENU` | Show search input in top menu bar | 1 |
| `INFRASSEARCH_REPLACE_STD` | Replace standard left menu search | 0 |
| `INFRASSEARCH_BREADCRUMB` | Enable breadcrumb history dropdown | 1 |
| `INFRASSEARCH_NB_BREADCRUMB` | Number of breadcrumb items | 10 |
| `INFRASSEARCH_NB_CAR` | Min characters before autocomplete triggers | 3 |
| `INFRASSEARCH_NB_SEC` | Autocomplete delay in milliseconds | 500 |
| `INFRASSEARCH_NB_ROWS` | Max results per object type | 5 |
| `INFRASSEARCH_ONLY_IN_ENTITY` | Restrict search to current entity | 1 |
| `INFRASSEARCH_SORT` | Enable date-based sorting for documents | 0 |
| `INFRASSEARCH_ORDER` | Sort order (ASC/DESC) | DESC |
| `INFRASSEARCH_SHOW_FIND_FIELD` | Show matched field detail with highlighting | 0 |
| `INFRASSEARCH_MOD_<TYPE>` | Enable search for specific object type (1/0) | per module |
| `INFRASSEARCH_POS_<TYPE>` | Display order for object type in results | auto |
| `INFRASSEARCH_LISTTOBJECTTYPE` | Comma-separated list of all searchable types | set on init |

### Permissions

| Permission Key | Description |
|---------------|-------------|
| `paramMenu` | View the settings menu (default: enabled) |
| `paramInfraSSearch` | Modify search settings |
| `paramBkpRest` | Backup / Restore module parameters |

### Bundled JavaScript

- **jquery.tile.min.js** (`js/`) - jQuery plugin for equal-height tile layout on search results page

### CSS

- `css/infrassearch.css.php` - Dynamic CSS (loaded via module_parts) for search results layout, breadcrumb dropdown, loading states

### Translations

Three language directories: `en_US`, `es_ES`, `fr_FR`. Single file `infrassearch.lang` per locale. Loaded via:
```php
$langs->load('infrassearch@infrassearch');
```

### Version Management

Version is read from `docs/changelog.xml` (not a VERSION file). The XML contains:
- Version entries with number and date
- Dolibarr min/max compatibility
- PHP min/max compatibility
- Per-version changelog entries

The module auto-disables if Dolibarr version is below the minimum required. A warning is shown on login if Dolibarr exceeds the maximum supported version.