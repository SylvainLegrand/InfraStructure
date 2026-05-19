# htdocs/expedition - Shipments Module

Customer shipment management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Expedition |
| Table | llx_expedition |
| Element Type | shipping |
| Permission Key | expedition |

## Directory Structure

```
expedition/
├── card.php              # Shipment detail
├── list.php              # Shipment list
├── class/
│   └── expedition.class.php
├── dispatch.php          # Line dispatch
├── contact.php           # Contacts
├── document.php          # Documents
├── note.php              # Notes
└── admin/                # Module settings
```
## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated (shipped) |
| STATUS_CLOSED | 2 | Closed (delivered) |

## Stock Integration

Shipment validation decreases stock:

```php
// When shipment is validated
$expedition->valid($user);
// Stock is automatically decreased
```

## Create from Order

```php
$expedition = new Expedition($db);
$expedition->origin = 'commande';
$expedition->origin_id = $order->id;
$expedition->socid = $order->socid;
$expedition->create($user);
```

## Permissions

- `$user->hasRight('expedition', 'lire')` - View shipments
- `$user->hasRight('expedition', 'creer')` - Create/edit shipments
- `$user->hasRight('expedition', 'supprimer')` - Delete shipments
- `$user->hasRight('expedition', 'delivery')` - Record delivery
