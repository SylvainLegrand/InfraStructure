# htdocs/takepos - TakePOS Module

Point of Sale system.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | takepos |

## Directory Structure

```
takepos/
├── index.php             # POS main interface
├── invoice.php           # Invoice handling
├── pay.php               # Payment processing
├── receipt.php           # Receipt printing
├── floors.php            # Restaurant floor plan
├── class/
│   └── takepos.class.php
├── css/                  # POS-specific styles
├── js/                   # POS JavaScript
├── terminals/            # Terminal management
└── admin/                # Module settings
```

## Features

### Sales Interface

- Touch-friendly product selection
- Barcode scanning
- Quantity adjustment
- Discounts
- Multiple payment methods

### Restaurant Mode

- Table management
- Floor plans
- Split bills
- Order transfer

### Terminals

Multiple POS terminals can be configured:

```php
// Terminal configuration
// TAKEPOS_TERMINAL_NAME_{n}
// TAKEPOS_PRINTER_{n}
// TAKEPOS_WAREHOUSE_{n}
```

## Payment Methods

- Cash
- Bank card
- Check
- Credit note
- Customer account

## Integration

TakePOS creates standard Dolibarr invoices:

```php
// Invoice created with type
Facture::TYPE_STANDARD
// And marked with special ref pattern
```

## Hardware Support

- Receipt printers (ESC/POS)
- Barcode scanners
- Cash drawers
- Customer displays

## Permissions

- `$user->hasRight('takepos', 'read')` - Access POS
- `$user->hasRight('takepos', 'run')` - Make sales
