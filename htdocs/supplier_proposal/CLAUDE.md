# Supplier Proposals Module

Supplier RFQ (Request for Quotation) management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | SupplierProposal |
| Table | llx_supplier_proposal |
| Element Type | supplier_proposal |
| Permission Key | supplier_proposal |

## Directory Structure

```
supplier_proposal/
├── card.php              # Proposal detail
├── list.php              # Proposal list
├── class/
│   └── supplier_proposal.class.php
├── contact.php           # Contacts
├── document.php          # Documents
└── admin/                # Module settings
```

## Main Class (SupplierProposal)

```php
class SupplierProposal extends CommonObject
{
    public $table_element = 'supplier_proposal';
    public $element = 'supplier_proposal';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_SIGNED = 2;
    const STATUS_NOTSIGNED = 3;
    const STATUS_CLOSE = 4;
}
```

## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated (sent to supplier) |
| STATUS_SIGNED | 2 | Accepted |
| STATUS_NOTSIGNED | 3 | Refused |
| STATUS_CLOSE | 4 | Closed |

## Workflow

1. Create price request to supplier
2. Send request
3. Receive and record supplier response
4. Accept/refuse proposal
5. Convert to supplier order if accepted

## Permissions

- `$user->hasRight('supplier_proposal', 'lire')` - View proposals
- `$user->hasRight('supplier_proposal', 'creer')` - Create/edit proposals
- `$user->hasRight('supplier_proposal', 'supprimer')` - Delete proposals
