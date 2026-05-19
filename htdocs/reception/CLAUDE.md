# htdocs/reception - Reception Module

Goods reception from suppliers.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Reception |
| Table | llx_reception |
| Element Type | reception |
| Permission Key | reception |

## Directory Structure

```
reception/
├── card.php              # Reception detail
├── list.php              # Reception list
├── class/
│   └── reception.class.php
├── dispatch.php          # Line dispatch
├── document.php          # Documents
└── admin/                # Module settings
```
## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated |
| STATUS_CLOSED | 2 | Closed |

## Stock Integration

Reception validation triggers stock movements:

```php
// When reception is validated
$reception->valid($user);
// Stock is automatically added to warehouse
```

## Link to Supplier Orders

Receptions are typically created from supplier orders and track partial deliveries.

## Permissions

- `$user->hasRight('reception', 'lire')` - View receptions
- `$user->hasRight('reception', 'creer')` - Create/edit receptions
- `$user->hasRight('reception', 'supprimer')` - Delete receptions
- `$user->hasRight('reception', 'valider')` - Validate receptions
