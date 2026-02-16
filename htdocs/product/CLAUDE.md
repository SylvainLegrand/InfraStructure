# htdocs/product - Products Module

Products, services, and stock management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Product |
| Stock Classes | Entrepot, MouvementStock |
| Table | llx_product |
| Element Type | product |
| Permission Key | produit |

## Directory Structure

```
product/
├── card.php              # Product detail
├── list.php              # Product list
├── class/
│   └── product.class.php
├── stock/                # Stock management
│   ├── card.php          # Warehouse card
│   ├── mouvement.php     # Stock movements
│   ├── class/
│   │   ├── entrepot.class.php
│   │   └── mouvementstock.class.php
│   └── replenish.php     # Replenishment
├── price.php             # Pricing
├── photos.php            # Product images
├── fournisseurs.php      # Supplier prices
├── stats/                # Statistics
├── composition/          # Kits/bundles
└── admin/                # Module settings
```

## Main Class (Product)

```php
class Product extends CommonObject
{
    public $table_element = 'product';
    public $element = 'product';

    // Product types
    const TYPE_PRODUCT = 0;
    const TYPE_SERVICE = 1;

    // Status
    const STATUS_CLOSED = 0;     // Not for sale
    const STATUS_OPEN = 1;       // For sale

    public $type;                // 0=product, 1=service
    public $price;               // Selling price
    public $price_ttc;           // Price with tax
    public $cost_price;          // Cost price
    public $pmp;                 // Weighted average price
}
```

## Stock Management (Entrepot)

```php
class Entrepot extends CommonObject
{
    public $table_element = 'entrepot';
    public $element = 'stock';

    const STATUS_CLOSED = 0;
    const STATUS_OPEN = 1;
}
```

## Stock Movements

```php
// Add stock
$product->correct_stock($user, $warehouseid, $qty, 0, $label);

// Remove stock
$product->correct_stock($user, $warehouseid, $qty, 1, $label);
```

## Permissions

- `$user->hasRight('produit', 'lire')` - View products
- `$user->hasRight('produit', 'creer')` - Create/edit products
- `$user->hasRight('stock', 'mouvement', 'creer')` - Stock movements
