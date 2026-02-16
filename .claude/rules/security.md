---
path: htdocs/**/*.php
---

# Security Patterns

## Input Validation (GETPOST)

Always validate user input with appropriate type checks:

```php
$id = GETPOSTINT('id');                    // Integer
$ref = GETPOST('ref', 'alpha');            // String, no HTML
$action = GETPOST('action', 'aZ09');       // Alphanumeric (actions, keys)
$email = GETPOST('email', 'email');        // Email format
$search = GETPOST('search', 'alphanohtml'); // Default, safe for most text
```

### GETPOST Type Parameters

| Type | Use Case | Allowed Characters |
|------|----------|-------------------|
| `int` | IDs, counts | Numeric only |
| `aZ09` | Actions, keys, identifiers | a-z, 0-9, underscore, dash, dot |
| `aZ09arobase` | Element types | aZ09 + @ (e.g., 'myobject@mymodule') |
| `alpha` / `alphanohtml` | Text input (default) | No HTML tags, no ../, no quotes |
| `alphawithlgt` | Email content | Preserves balanced < > tags |
| `email` | Email addresses | FILTER_SANITIZE_EMAIL |
| `url` | URLs | Sanitized, no external patterns |
| `nohtml` | Plain text | Strips all HTML |
| `restricthtml` | Rich text | Limited safe HTML tags |
| `array` | Array parameters | Array handling |
| `none` | **Deprecated** | No sanitization (avoid) |

### Related Functions

```php
GETPOSTINT('param');              // Returns integer
GETPOSTFLOAT('param');            // Returns float
GETPOSTISSET('param');            // Check if exists
GETPOSTISARRAY('param');          // Check if array
```

## CSRF Protection

### Form Token

Include token in all forms:

```php
print '<input type="hidden" name="token" value="'.newToken().'">';
```

### Token Validation

Dolibarr validates tokens automatically based on `MAIN_SECURITY_CSRF_WITH_TOKEN`:
- **0** - Disabled
- **1-2** - Validates POST + certain GET actions
- **3** - Validates POST + all sensitive actions

Sensitive actions requiring token: `confirm_*`, `add`, `del`, `update`, `save`, `enable`, `disable`, `remove`, `close`, `reopen`

### Disable for AJAX

```php
define('NOTOKENRENEWAL', 1);  // Before main.inc.php for AJAX endpoints
```

## SQL Injection Prevention

### Escaping Values

```php
// Integer fields - cast to int
$sql .= " WHERE rowid = ".((int) $id);

// String fields - use escape()
$sql .= " WHERE ref = '".$db->escape($ref)."'";

// LIKE queries - use escapeforlike()
$sql .= " WHERE label LIKE '%".$db->escapeforlike($search)."%'";
```

### Rules

- **Never use `SELECT *`** - list fields explicitly
- **Always quote strings**, never quote integers
- **No SQL date functions** (`NOW()`, `SYSDATE()` forbidden)
- Use `$db->ifsql()` for conditional SQL

## Access Control

### Permission Check

Call after `main.inc.php`:

```php
restrictedArea($user, 'mymodule');                    // Module access
restrictedArea($user, 'mymodule', $object->id);       // Object access
restrictedArea($user, 'societe|fournisseur');         // OR logic
restrictedArea($user, 'societe&contact');             // AND logic
```

### Check User Rights

```php
if ($user->hasRight('mymodule', 'read')) { }
if ($user->hasRight('mymodule', 'write')) { }
if ($user->hasRight('mymodule', 'delete')) { }
```

## File Upload Security

Use `dol_move_uploaded_file()` which provides:
- Antivirus scanning (if configured)
- Executable content detection (.php, .js, .exe → .noexe)
- Path traversal prevention (rejects `..`, dotfiles)
- PDF JavaScript check

```php
$result = dol_move_uploaded_file(
    $_FILES['userfile']['tmp_name'],
    $upload_dir.'/'.$filename,
    $allowoverwrite,
    0,  // disablevirusscan
    $_FILES['userfile']['error']
);
```

## Output Escaping

```php
// HTML output
print dol_escape_htmltag($value);

// JavaScript strings
print dol_escape_js($value);

// URL parameters
print urlencode($value);
```

## Sanitization Functions

Use these functions to sanitize user input for specific contexts:

```php
// File names - removes dangerous characters, path traversal
$filename = dol_sanitizeFileName($str, $newstr = '_', $unaccent = 1, $includequotes = 0);

// Path names - sanitizes directory paths
$path = dol_sanitizePathName($str, $newstr = '_', $unaccent = 1);

// URLs - sanitizes URL strings
$url = dol_sanitizeUrl($stringtoclean, $type = 1);

// Email addresses - sanitizes email input
$email = dol_sanitizeEmail($stringtoclean);

// Key codes - sanitizes identifier/key strings
$key = dol_sanitizeKeyCode($str);
```

| Function | Use Case |
|----------|----------|
| `dol_sanitizeFileName()` | File names before storage |
| `dol_sanitizePathName()` | Directory paths |
| `dol_sanitizeUrl()` | URL parameters and links |
| `dol_sanitizeEmail()` | Email addresses |
| `dol_sanitizeKeyCode()` | Identifiers and key codes |

## Documentation

https://wiki.dolibarr.org/index.php/Language_and_development_rules
