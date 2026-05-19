# htdocs/ecm - ECM Module

Electronic Content Management (document management).

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | EcmDirectory, EcmFiles |
| Tables | llx_ecm_directories, llx_ecm_files |
| Element Types | ecm_directories, ecm_files |
| Permission Key | ecm |

## Directory Structure

```
ecm/
├── index.php             # File browser
├── dir_card.php          # Directory detail
├── dir_add_card.php      # Create directory
├── class/
│   ├── ecmdirectory.class.php
│   └── ecmfiles.class.php
├── search.php            # File search
├── file_card.php         # File detail
└── admin/                # Module settings
```
## EcmFiles Class

## File Storage

Documents stored in `documents/ecm/` directory.

## Shared Links

ECM files can be shared via public links:

```php
$ecmfile->share = md5(uniqid('', true));
$ecmfile->update($user);
// Public URL: {baseurl}/document.php?hashp={share}
```

## Integration

ECM tracks files attached to all Dolibarr objects (invoices, orders, etc.).

## Permissions

- `$user->hasRight('ecm', 'read')` - View documents
- `$user->hasRight('ecm', 'upload')` - Upload documents
- `$user->hasRight('ecm', 'setup')` - Manage directories
