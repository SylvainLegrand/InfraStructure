# Expense Reports Module

Employee expense report management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | ExpenseReport |
| Line Class | ExpenseReportLine |
| Table | llx_expensereport |
| Element Type | expensereport |
| Permission Key | expensereport |

## Directory Structure

```
expensereport/
├── card.php              # Expense report detail
├── list.php              # Expense report list
├── class/
│   └── expensereport.class.php
├── document.php          # Receipts/documents
├── payment/              # Payments
│   ├── card.php
│   └── list.php
└── admin/                # Module settings
```

## Main Class (ExpenseReport)

```php
class ExpenseReport extends CommonObject
{
    public $table_element = 'expensereport';
    public $element = 'expensereport';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 2;
    const STATUS_APPROVED = 5;
    const STATUS_REFUSED = 4;
    const STATUS_CANCELED = 99;
    const STATUS_CLOSED = 6;

    public $fk_user_author;    // Employee user ID
    public $fk_user_approve;   // Approver user ID
    public $date_debut;        // Period start
    public $date_fin;          // Period end
    public $total_ht;          // Total excl. tax
    public $total_tva;         // Total VAT
    public $total_ttc;         // Total incl. tax
}
```

## Expense Lines

```php
class ExpenseReportLine extends CommonObjectLine
{
    public $fk_expensereport;  // Parent report
    public $fk_c_type_fees;    // Expense type
    public $date;              // Expense date
    public $comments;          // Description
    public $qty;               // Quantity
    public $value_unit;        // Unit price
    public $total_ht;          // Line total
}
```

## Expense Types

Defined in dictionary `llx_c_type_fees`:

- Meals
- Transportation
- Accommodation
- Custom types

## Approval Workflow

```
DRAFT → VALIDATED (submitted) → APPROVED → CLOSED (paid)
                              ↘ REFUSED
```

## Permissions

- `$user->hasRight('expensereport', 'lire')` - View reports
- `$user->hasRight('expensereport', 'creer')` - Create reports
- `$user->hasRight('expensereport', 'approve')` - Approve reports
- `$user->hasRight('expensereport', 'to_paid')` - Process payments
