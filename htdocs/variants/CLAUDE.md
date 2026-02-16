# htdocs/variants - Product Variants Module

Product variations (size, color, etc.).

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | ProductAttribute, ProductAttributeValue, ProductCombination |
| Tables | llx_product_attribute, llx_product_attribute_combination |
| Permission Key | produit |

## Directory Structure

```
variants/
├── card.php              # Variant attribute card
├── list.php              # Attributes list
├── class/
│   ├── ProductAttribute.class.php
│   ├── ProductAttributeValue.class.php
│   └── ProductCombination.class.php
└── admin/                # Module settings
```

## Key Classes

### ProductAttribute

Defines variant types (e.g., "Size", "Color"):

```php
class ProductAttribute extends CommonObject
{
    public $label;             // "Size", "Color"
    public $ref;               // Reference code
    public $rang;              // Display order
}
```

### ProductAttributeValue

Values for an attribute (e.g., "Small", "Medium", "Large"):

```php
class ProductAttributeValue extends CommonObject
{
    public $fk_product_attribute;  // Parent attribute
    public $ref;                   // Value reference
    public $value;                 // Display value
}
```

### ProductCombination

Links parent product to variant products:

```php
class ProductCombination extends CommonObject
{
    public $fk_product_parent;     // Parent product ID
    public $fk_product_child;      // Variant product ID
    public $variation_price;       // Price adjustment
    public $variation_price_percentage;
    public $variation_weight;      // Weight adjustment
}
```

## Usage

1. Create attributes (Size, Color)
2. Add values to attributes (S, M, L / Red, Blue)
3. Create combinations on parent product
4. System generates child products for each combination

## Permissions

Uses product permissions (`$user->hasRight('produit', ...)`).
