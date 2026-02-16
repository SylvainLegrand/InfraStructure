---
path: htdocs/**/*.php
---

# Global Variables

These variables are available throughout Dolibarr after including `main.inc.php`.

## $conf - Configuration

System configuration and module settings (Conf class).

```php
// Check if module enabled
if (isModEnabled('mymodule')) { }

// Get constants (preferred methods)
$value = getDolGlobalString('CONSTANT_NAME', 'default');
$value = getDolGlobalInt('CONSTANT_NAME', 0);

// Common properties
$conf->entity;              // Multi-company entity ID
$conf->currency;            // Currency code (EUR, USD)
$conf->theme;               // UI theme name
$conf->liste_limit;         // Items per list page
$conf->use_javascript_ajax; // AJAX enabled
```

### Reading Constants

Constants are stored in `llx_const` table. Always use accessor functions:

```php
getDolGlobalString('CONSTANT_NAME', 'default');  // String
getDolGlobalInt('CONSTANT_NAME', 0);             // Integer
getDolGlobalBool('CONSTANT_NAME');               // Boolean (true if > 0)

// Check if set
if (getDolGlobalString('CONSTANT_NAME')) { }
```

### User-Specific Constants

```php
// Get user-specific setting (falls back to global)
getDolUserString($user, 'USER_CONSTANT', 'default');
getDolUserInt($user, 'USER_CONSTANT', 0);
```

**Forbidden:** `$conf->global->CONSTANT_NAME` - Use `getDolGlobalString()` or `getDolGlobalInt()` instead.

### Setting Constants

```php
dolibarr_set_const($db, 'CONSTANT_NAME', $value, 'chaine', 0, '', $conf->entity);
dolibarr_del_const($db, 'CONSTANT_NAME', $conf->entity);
```

### Key Development Constants

- `MAIN_FEATURES_LEVEL` - Feature visibility (0=stable, 1=beta, 2=all/dev)
- `MAIN_SECURITY_CSRF_WITH_TOKEN` - CSRF protection level (0-3)

## $user - Current User

Logged-in user data and permissions (User class).

```php
// Permission checks
if ($user->hasRight('mymodule', 'read')) { }
if ($user->hasRight('mymodule', 'write')) { }
if ($user->admin) { }  // Is admin

// User info
$user->id;                          // User ID
$user->login;                       // Username
$user->getFullName($langs);         // Full name
$user->email;                       // Email
$user->entity;                      // Entity ID
```

## $langs - Translation

Multi-language support (Translate class).

```php
// Load language file
$langs->load('mymodule@mymodule');
$langs->loadLangs(array('main', 'bills'));

// Get translations
$langs->trans('Key');               // HTML-encoded
$langs->transnoentities('Key');     // Not encoded
$langs->trans('Hello %s', $name);   // With parameters

// Language info
$langs->defaultlang;                // 'en_US', 'fr_FR'
$langs->getDefaultLang(1);          // Short: 'en', 'fr'
$langs->getCurrencySymbol('EUR');   // '€'
```

## $db - Database Handler

Database abstraction layer (DoliDB class).

```php
// Query execution
$sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."mytable";
$sql .= " WHERE entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        // process $obj->rowid, $obj->ref
    }
    $db->free($resql);
}

// Escaping (SQL injection prevention)
$db->escape($string);               // Escape string
$db->escapeforlike($string);        // Escape for LIKE

// Date conversion
$db->idate($timestamp);             // Timestamp → SQL string
$db->jdate($sqldate);               // SQL string → Timestamp

// Transactions
$db->begin();
$db->commit();  // or $db->rollback();

// Utilities
$db->prefix();                      // Table prefix
$db->num_rows($resql);              // Row count
$db->lasterror();                   // Last error message
```

### Multi-Entity Queries

```php
// Get entity filter for SQL (handles sharing between entities)
$sql .= " WHERE entity IN (".getEntity('tablename').")";

// Common usage
$sql .= " WHERE t.entity IN (".getEntity('product').")";        // Products
$sql .= " WHERE t.entity IN (".getEntity('societe').")";        // Third parties
$sql .= " WHERE t.entity IN (".getEntity('facture').")";        // Invoices
```

### Error Handling Pattern

```php
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        // process row
    }
    $db->free($resql);
} else {
    $error++;
    setEventMessages($db->lasterror(), null, 'errors');
}
```

## $mysoc - Current Company

Main company information (Societe class).

```php
$mysoc->name;           // Company name
$mysoc->address;        // Address
$mysoc->zip;            // Postal code
$mysoc->town;           // City
$mysoc->country_code;   // Country code
$mysoc->phone;          // Phone
$mysoc->email;          // Email
$mysoc->logo;           // Logo filename
$mysoc->tva_intra;      // VAT number
$mysoc->capital;        // Capital
```

## $hookmanager - Hook System

Module hook execution (HookManager class).

```php
// Initialize hooks for context
$hookmanager->initHooks(array('invoicecard'));

// Execute hook
$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('hookName', $parameters, $object, $action);

// Check result
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Get hook output
echo $hookmanager->resPrint;
```

Hook return: 0 = continue, 1 = replace standard code, <0 = error

## $extrafields - Custom Fields

Dynamic extra fields manager (ExtraFields class).

```php
// Load extra fields for object type
$extrafields->fetch_name_optionals_label('invoice');

// Access field definitions
$fields = $extrafields->attributes['invoice'];

// Display in forms
$extrafields->showInputField($key, $value, '', 'invoice_', 'invoice');

// Display in views
$extrafields->showOutputField($key, $object->array_options['options_'.$key], $object, 'invoice');
```

## Server Variables

Access server superglobals safely:

### $_SERVER

```php
// Safe access patterns
$_SERVER['PHP_SELF'];              // Current script path
$_SERVER['REQUEST_METHOD'];        // GET, POST, etc.
$_SERVER['REQUEST_URI'];           // Full request URI
$_SERVER['HTTP_HOST'];             // Host header
$_SERVER['HTTPS'];                 // HTTPS enabled
$_SERVER['REMOTE_ADDR'];           // Client IP

// For redirect URLs, sanitize to prevent open redirect
$url = dol_sanitizeUrl($_SERVER['HTTP_REFERER']);
```

### $_COOKIE

```php
// Read cookies (avoid direct access)
$value = $_COOKIE['cookie_name'] ?? '';

// Set cookies using Dolibarr helper
setcookie('name', $value, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

### $_ENV

```php
// Environment variables (rarely used in Dolibarr)
$value = getenv('VARIABLE_NAME');
```

**Note:** For request parameters (`$_GET`, `$_POST`), always use `GETPOST()` functions. See `security.md` for details.

## Usage in Classes vs Pages

```php
// In page code - use global
global $db, $conf, $user, $langs;

// In class constructor - pass $db
public function __construct(DoliDB $db)
{
    $this->db = $db;
}

// In class methods - use $this->db
$resql = $this->db->query($sql);
```
