# Customer Orders Module

Customer order management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Commande |
| Line Class | CommandeLine |
| Table | llx_commande |
| Element Type | commande |
| Permission Key | commande |

## Directory Structure

```
commande/
├── card.php              # Order detail/edit
├── list.php              # Order list
├── class/
│   └── commande.class.php
├── orderstoinvoice.php   # Convert to invoice
└── admin/                # Module settings
```

## Main Class (Commande)

```php
class Commande extends CommonOrder
{
    public $table_element = 'commande';
    public $element = 'commande';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_SHIPMENTONPROCESS = 2;
    const STATUS_CLOSED = 3;
    const STATUS_CANCELED = -1;
}
```

## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated |
| STATUS_SHIPMENTONPROCESS | 2 | Shipment in progress |
| STATUS_CLOSED | 3 | Closed (delivered) |
| STATUS_CANCELED | -1 | Canceled |

## Permissions

- `$user->hasRight('commande', 'lire')` - View orders
- `$user->hasRight('commande', 'creer')` - Create/edit orders
- `$user->hasRight('commande', 'supprimer')` - Delete orders
- `$user->hasRight('commande', 'valider')` - Validate orders
