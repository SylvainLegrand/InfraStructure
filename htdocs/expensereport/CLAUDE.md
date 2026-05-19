# htdocs/expensereport - Expense Reports Module

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
## Expense Lines

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
