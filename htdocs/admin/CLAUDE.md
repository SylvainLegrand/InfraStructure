# htdocs/admin - System Administration

Administration pages for system configuration, modules, and settings.

## Key Files

| File | Purpose |
|------|---------|
| modules.php | Module activation/deactivation |
| const.php | System constants editor |
| company.php | Main company setup |
| dict.php | Dictionary tables management |
| security.php | Security settings |
| mails.php | Email configuration |

## Admin Page Development

See `/dolibarr-page-patterns` skill for admin page templates and patterns.

Module admin pages are typically at `htdocs/[module]/admin/setup.php`.

## Setting Constants

```php
// Set constant
dolibarr_set_const($db, 'CONSTANT_NAME', $value, 'chaine', 0, '', $conf->entity);

// Delete constant
dolibarr_del_const($db, 'CONSTANT_NAME', $conf->entity);
```

## Dictionary Management

Dictionaries (lookup tables) are managed via `admin/dict.php` with entries in `llx_c_*` tables.
