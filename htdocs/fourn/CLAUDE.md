# htdocs/fourn - Suppliers Module

Supplier management: orders and invoices.

## Module Info

| Property | Value |
|----------|-------|
| Order Class | CommandeFournisseur |
| Invoice Class | FactureFournisseur |
| Tables | llx_commande_fournisseur, llx_facture_fourn |
| Permission Key | fournisseur |

## Directory Structure

```
fourn/
├── commande/             # Supplier orders
│   ├── card.php
│   ├── list.php
│   └── dispatch.php      # Stock dispatch
├── facture/              # Supplier invoices
│   ├── card.php
│   ├── list.php
│   └── paiement.php      # Payments
├── class/
│   ├── fournisseur.commande.class.php
│   └── fournisseur.facture.class.php
├── paiement/             # Supplier payments
└── admin/                # Module settings
```

## Supplier Order (CommandeFournisseur)

```php
class CommandeFournisseur extends CommonOrder
{
    public $table_element = 'commande_fournisseur';
    public $element = 'order_supplier';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_ORDERSENT = 3;
    const STATUS_RECEIVED_PARTIALLY = 4;
    const STATUS_RECEIVED_COMPLETELY = 5;
    const STATUS_CANCELED = 6;
    const STATUS_REFUSED = 7;
}
```

## Supplier Invoice (FactureFournisseur)

```php
class FactureFournisseur extends CommonInvoice
{
    public $table_element = 'facture_fourn';
    public $element = 'invoice_supplier';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;
    const STATUS_ABANDONED = 3;
}
```

## Permissions

- `$user->hasRight('fournisseur', 'commande', 'lire')` - View orders
- `$user->hasRight('fournisseur', 'facture', 'lire')` - View invoices
- `$user->hasRight('fournisseur', 'commande', 'creer')` - Create orders
- `$user->hasRight('fournisseur', 'facture', 'creer')` - Create invoices
