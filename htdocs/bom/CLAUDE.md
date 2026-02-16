# Bill of Materials Module

Product composition and manufacturing recipes.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | BOM |
| Line Class | BOMLine |
| Table | llx_bom_bom |
| Element Type | bom |
| Permission Key | bom |

## Directory Structure

```
bom/
├── bom_card.php          # BOM detail
├── bom_list.php          # BOM list
├── class/
│   ├── bom.class.php
│   └── bomline.class.php
├── bom_net_needs.php     # Net requirements
├── bom_document.php      # Documents
└── admin/                # Module settings
```

## Main Class (BOM)

```php
class BOM extends CommonObject
{
    public $table_element = 'bom_bom';
    public $element = 'bom';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELED = 9;

    public $fk_product;        // Finished product
    public $qty;               // Production quantity
    public $duration;          // Production duration
    public $fk_warehouse;      // Default warehouse
}
```

## BOM Line (BOMLine)

```php
class BOMLine extends CommonObjectLine
{
    public $fk_bom;            // Parent BOM
    public $fk_product;        // Component product
    public $qty;               // Quantity needed
    public $qty_frozen;        // Fixed quantity (not scaled)
    public $disable_stock_change;
    public $efficiency;        // Loss percentage
    public $position;          // Line order
}
```

## Usage

1. Create BOM for finished product
2. Add component lines
3. Validate BOM
4. Use in Manufacturing Orders (MRP)

## Net Requirements

`bom_net_needs.php` calculates total component needs for a production quantity.

## Permissions

- `$user->hasRight('bom', 'read')` - View BOMs
- `$user->hasRight('bom', 'write')` - Create/edit BOMs
- `$user->hasRight('bom', 'delete')` - Delete BOMs
