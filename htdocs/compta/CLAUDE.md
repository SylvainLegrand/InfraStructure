# Finance Module

Bank accounts, payments, VAT, and customer invoices.

## Module Info

| Property | Value |
|----------|-------|
| Submodules | facture, bank, paiement, tva |
| Permission Key | banque, facture |

## Directory Structure

```
compta/
├── facture/              # Customer invoices (see below)
├── paiement/             # Customer payments
├── bank/                 # Bank accounts & transactions
├── tva/                  # VAT management
├── prelevement/          # Direct debit
├── resultat/             # Results/reports
├── sociales/             # Social charges
└── cashcontrol/          # Cash register control
```

## Customer Invoices (facture/)

| Property | Value |
|----------|-------|
| Main Class | Facture |
| Line Class | FactureLigne |
| Table | llx_facture |
| Element Type | facture |

### Invoice Status

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated (unpaid) |
| STATUS_CLOSED | 2 | Paid |
| STATUS_ABANDONED | 3 | Abandoned |

### Invoice Types

| Type | Value | Description |
|------|-------|-------------|
| TYPE_STANDARD | 0 | Standard invoice |
| TYPE_REPLACEMENT | 1 | Replacement invoice |
| TYPE_CREDIT_NOTE | 2 | Credit note |
| TYPE_DEPOSIT | 3 | Deposit invoice |
| TYPE_SITUATION | 5 | Situation invoice |

## Bank Accounts

Main class: `Account` (htdocs/compta/bank/class/account.class.php)

## Permissions

- `$user->hasRight('facture', 'lire')` - View invoices
- `$user->hasRight('facture', 'creer')` - Create/edit invoices
- `$user->hasRight('banque', 'lire')` - View bank accounts
- `$user->hasRight('banque', 'modifier')` - Edit bank transactions
