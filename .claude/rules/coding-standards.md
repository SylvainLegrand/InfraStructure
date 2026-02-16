---
path: htdocs/**/*.php
---

# PHP Coding Standards

## PHP Version Requirements

- **Minimum:** PHP 7.1
- **Maximum:** PHP 8.4
- Code must be compatible with all versions in this range
- Avoid features only available in newer PHP versions unless behind version checks

## Framework Policy

Dolibarr deliberately avoids heavy frameworks:

- **No template engines:** No Smarty, Twig, Blade - use native PHP for templates
- **No Composer:** Dolibarr does not use Composer for dependency management
  - All dependencies are vendored in `htdocs/includes/`
  - This ensures stability and works in shared hosting environments
  - External modules may use Composer but core does not
- **No heavy MVC frameworks:** No Symfony, Laravel, etc.
- **Minimal JavaScript frameworks:** jQuery only, no React/Vue/Angular in core

## Design Patterns

Dolibarr uses several design patterns:

- **Active Record:** Business objects handle their own CRUD operations
- **MVC (lightweight):** Model=DAO classes, Controller=actions, View=print statements
- **Singleton:** `$conf`, `$user`, `$langs` are global singletons
- **Factory:** Module descriptors create related objects
- **Observer:** Trigger and hook systems

## File Requirements

- **Extension:** `.php` only
- **Line endings:** Unix (LF), not Windows (CR/LF)
- **Encoding:** UTF-8 without BOM
- **PHP tags:** Full `<?php` only, never `<?` or `<?=`
- **No closing `?>` tag** in PHP-only files

## Formatting

- **Indentation:** Tabs (4 spaces width), preserve existing
- **Line limit:** 1000 characters max (hard), 120 characters (soft)
- **Base standard:** PSR-12 (mandatory rules only)
- **Comments:** `//` single line, `/* */` blocks

## Variables & Syntax

- **Boolean/null:** lowercase (`true`, `false`, `null`)
- **Variable declarations:** Individual, not chained (`$a = $b = 0` forbidden)
- **String variables:** Concatenate outside quotes (`$x." text"`, not `"$x text"`)
- **Superglobals:** Use `GETPOST()` for `$_GET`/`$_POST`, dedicated operators for `$_COOKIE`, `$_SERVER`, `$_ENV` (see `security.md` for type parameters)

## Include Rules

- **Classes/functions:** `include_once` for `.class.php`, `.lib.php`
- **Templates:** `include` for `.inc.php`, `.tpl.php`

**Preferred:** Use `dol_include_once()` - searches both core and custom module paths:

```php
dol_include_once('/mymodule/class/myobject.class.php');
dol_include_once('/core/lib/functions.lib.php');
```

Alternatives (when appropriate):
- `include_once DOL_DOCUMENT_ROOT.'/path'` - direct core file include
- `include_once './file.php'` - relative include within same module

## Return Values

- **Success:** Return `>= 0`
- **Error:** Return `< 0`
- **No dead code** in core (acceptable in external modules)

## Global Variables

See `globals.md` for detailed documentation on `$conf`, `$user`, `$langs`, `$db`, `$mysoc`, `$hookmanager`, `$extrafields`.

## Constants

See `globals.md` for complete documentation on reading and setting constants (`getDolGlobalString()`, `getDolGlobalInt()`, etc.).

## Code Quality

```bash
phpcs --standard=dev/setup/codesniffer/ruleset.xml <file>   # Check
phpcbf --standard=dev/setup/codesniffer/ruleset.xml <file>  # Auto-fix
```

## Copyright Header

All files must begin with:

```php
<?php
/* Copyright (C) YYYY Name <email>
 *
 * License information
 */
```

Add your line when editing existing files.

## SQL in PHP

See `security.md` for SQL injection prevention rules (escaping, forbidden patterns).

See `globals.md` for database access patterns with `$db`.

## Date Handling

- **Memory:** Always GMT Timestamp
- **Database:** GMT Timestamp in DB timezone
- `$db->jdate()` - Database datetime → GMT Timestamp
- `$db->idate()` - GMT Timestamp → SQL string
- Use: `dol_now()`, `dol_mktime()`, `dol_stringtotime()`, `dol_getdate()`, `dol_time_plus_duree()`

## Float/Amount Handling

Always clean float results:

```php
$amount = price2num($value, 'MT');  // Amounts
$qty = price2num($value, 'MU');     // Quantities
$other = round($value, $precision); // Non-amounts
```

## Tab/Head Functions

See `/dolibarr-page-patterns` skill for complete tab/head documentation including `dol_get_fiche_head()`, `dol_banner_tab()`, and lib file patterns.
