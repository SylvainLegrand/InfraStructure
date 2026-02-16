# Donations Module

Donation tracking and receipts.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Don |
| Table | llx_don |
| Element Type | don |
| Permission Key | don |

## Directory Structure

```
don/
├── card.php              # Donation detail
├── list.php              # Donation list
├── class/
│   └── don.class.php
├── payment/              # Donation payments
├── document.php          # Attached documents
└── admin/                # Module settings
```

## Main Class (Don)

```php
class Don extends CommonObject
{
    public $table_element = 'don';
    public $element = 'don';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_PAID = 2;
    const STATUS_CANCELED = -1;

    public $amount;            // Donation amount
    public $societe;           // Donor company name
    public $firstname;         // Donor first name
    public $lastname;          // Donor last name
    public $datedon;           // Donation date
}
```

## Status Values

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_DRAFT | 0 | Draft |
| STATUS_VALIDATED | 1 | Validated |
| STATUS_PAID | 2 | Paid |
| STATUS_CANCELED | -1 | Canceled |

## Features

- Donation receipt generation (PDF)
- Tax receipt for donors
- Campaign tracking
- Statistics and reports

## Permissions

- `$user->hasRight('don', 'lire')` - View donations
- `$user->hasRight('don', 'creer')` - Create/edit donations
- `$user->hasRight('don', 'supprimer')` - Delete donations
