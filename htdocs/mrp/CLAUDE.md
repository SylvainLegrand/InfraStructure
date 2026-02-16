# htdocs/mrp - Manufacturing Orders Module

Manufacturing/production order management (MRP).

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Mo |
| Line Class | MoLine |
| Table | llx_mrp_mo |
| Element Type | mo |
| Permission Key | mrp |

## Directory Structure

```
mrp/
├── mo_card.php           # MO detail
├── mo_list.php           # MO list
├── class/
│   ├── mo.class.php
│   └── moline.class.php
├── mo_production.php     # Production tracking
├── mo_movements.php      # Stock movements
├── mo_document.php       # Documents
└── admin/                # Module settings
```

## Main Class (Mo)

```php
class Mo extends CommonObject
{
    public $table_element = 'mrp_mo';
    public $element = 'mo';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_INPROGRESS = 2;
    const STATUS_PRODUCED = 3;
    const STATUS_CANCELED = 9;

    public $fk_bom;            // Source BOM
    public $fk_product;        // Finished product
    public $qty;               // Quantity to produce
    public $date_start_planned;
    public $date_end_planned;
    public $fk_warehouse;      // Target warehouse
}
```

## Status Workflow

```
DRAFT → VALIDATED → INPROGRESS → PRODUCED
                ↘      ↓
                  CANCELED
```

## Production Tracking

Track component consumption and finished product output:

```php
// Consume components
$mo->consumeLine($user, $lineid, $qty, $warehouseid);

// Produce finished product
$mo->produceLine($user, $qty, $warehouseid);
```

## Integration

- Uses BOMs for component lists
- Links to Workstations for routing
- Triggers stock movements

## Permissions

- `$user->hasRight('mrp', 'read')` - View MOs
- `$user->hasRight('mrp', 'write')` - Create/edit MOs
- `$user->hasRight('mrp', 'delete')` - Delete MOs
