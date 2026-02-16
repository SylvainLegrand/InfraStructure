# htdocs/comm - Commercial Module

Commercial actions and proposals (quotes).

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Propal |
| Table | llx_propal |
| Element Type | propal |
| Permission Key | propal |

## Directory Structure

```
comm/
├── propal/
│   ├── card.php              # Proposal detail
│   ├── list.php              # Proposal list
│   └── class/propal.class.php
├── action/                   # Commercial actions
├── mailing/                  # Mass mailing
└── admin/                    # Module settings
```

## Main Class (Propal)

```php
class Propal extends CommonObject
{
    public $table_element = 'propal';
    public $element = 'propal';
    public $fk_element = 'fk_propal';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_SIGNED = 2;
    const STATUS_NOTSIGNED = 3;
    const STATUS_BILLED = 4;
}
```

## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated (sent) |
| STATUS_SIGNED | 2 | Signed |
| STATUS_NOTSIGNED | 3 | Not signed (refused) |
| STATUS_BILLED | 4 | Billed |

## Permissions

- `$user->hasRight('propal', 'lire')` - View proposals
- `$user->hasRight('propal', 'creer')` - Create/edit proposals
- `$user->hasRight('propal', 'supprimer')` - Delete proposals
- `$user->hasRight('propal', 'cloturer')` - Close proposals
