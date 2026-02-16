# htdocs/barcode - Barcode Module

Barcode generation and management for products and third parties.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | barcode |

## Directory Structure

```
barcode/
├── printsheet.php        # Print barcode sheets
├── codeinit.php          # Initialize barcodes
└── admin/                # Module settings
```

## Barcode Types

Supported barcode formats:

| Type | Description |
|------|-------------|
| EAN13 | European Article Number (13 digits) |
| EAN8 | Short EAN (8 digits) |
| UPC | Universal Product Code |
| ISBN | Book numbers |
| C39 | Code 39 |
| C128 | Code 128 |

## Product Barcodes

Products store barcode in `barcode` field:

```php
$product->barcode;              // Barcode value
$product->barcode_type;         // Barcode type ID
$product->barcode_type_code;    // Type code (EAN13, etc.)
```

## Barcode Generation

```php
// Generate barcode image
$module = new modBarcode();
$module->buildBarCode($product->barcode, $encoding);
```

## Printing

The `printsheet.php` page generates printable barcode sheets for:

- Selected products
- Product ranges
- Custom labels

## Third Party Barcodes

Third parties (customers, suppliers) can also have barcodes assigned.

## Permissions

- `$user->hasRight('barcode', 'lire')` - View barcodes
- `$user->hasRight('barcode', 'creer')` - Create/edit barcodes
