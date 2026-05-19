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
## Supplier Invoice (FactureFournisseur)

## Permissions

- `$user->hasRight('fournisseur', 'commande', 'lire')` - View orders
- `$user->hasRight('fournisseur', 'facture', 'lire')` - View invoices
- `$user->hasRight('fournisseur', 'commande', 'creer')` - Create orders
- `$user->hasRight('fournisseur', 'facture', 'creer')` - Create invoices
