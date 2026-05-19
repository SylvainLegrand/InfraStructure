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
## Stock Management (Entrepot)

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
