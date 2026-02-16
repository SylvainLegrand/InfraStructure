# htdocs/delivery - Delivery Notes Module

Delivery receipts for shipped goods.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Delivery |
| Table | llx_delivery |
| Element Type | delivery |
| Permission Key | expedition |

## Directory Structure

```
delivery/
├── card.php              # Delivery note detail
├── list.php              # Delivery note list
├── class/
│   └── delivery.class.php
├── contact.php           # Contacts
├── document.php          # Documents
└── note.php              # Notes
```

## Main Class (Delivery)

```php
class Delivery extends CommonObject
{
    public $table_element = 'delivery';
    public $element = 'delivery';

    // Status constants
    const STATUS_DRAFT = -1;
    const STATUS_VALIDATED = 0;

    public $socid;             // Customer ID
    public $fk_expedition;     // Source shipment
    public $date_delivery;     // Delivery date
}
```

## Relationship to Shipments

Delivery notes confirm receipt of shipments:

```
Order → Shipment → Delivery Note
```

- Shipment: Items sent
- Delivery: Items received/confirmed

## Usage

```php
// Create delivery from shipment
$delivery = new Delivery($db);
$delivery->origin = 'expedition';
$delivery->origin_id = $expedition->id;
$delivery->socid = $expedition->socid;
$delivery->create($user);
```

## Enabling Delivery Notes

Set constant `MAIN_SUBMODULE_DELIVERY` to enable this submodule.

## Permissions

Uses shipment permissions (`$user->hasRight('expedition', ...)`).
