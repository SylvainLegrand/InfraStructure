# ECM Module

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

## EcmDirectory Class

```php
class EcmDirectory extends CommonObject
{
    public $table_element = 'ecm_directories';
    public $element = 'ecm_directories';

    public $label;             // Directory name
    public $fk_parent;         // Parent directory (hierarchy)
    public $description;       // Description
    public $cachenbofdoc;      // Document count cache
}
```

## EcmFiles Class

```php
class EcmFiles extends CommonObject
{
    public $table_element = 'ecm_files';
    public $element = 'ecm_files';

    public $filename;          // File name
    public $filepath;          // Relative path
    public $fullpath_orig;     // Original full path
    public $description;       // Description
    public $gen_or_uploaded;   // 'generated' or 'uploaded'
    public $share;             // Share hash for public links
}
```

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
