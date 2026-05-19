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

### ProductAttributeValue

Values for an attribute (e.g., "Small", "Medium", "Large"):

### ProductCombination

Links parent product to variant products:

## Usage

1. Create attributes (Size, Color)
2. Add values to attributes (S, M, L / Red, Blue)
3. Create combinations on parent product
4. System generates child products for each combination

## Permissions

Uses product permissions (`$user->hasRight('produit', ...)`).
