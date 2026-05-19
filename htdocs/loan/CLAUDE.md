# htdocs/loan - Loans Module

Loan management and repayment schedules.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Loan |
| Schedule Class | LoanSchedule |
| Table | llx_loan |
| Element Type | loan |
| Permission Key | loan |

## Directory Structure

```
loan/
├── card.php              # Loan detail
├── list.php              # Loan list
├── class/
│   ├── loan.class.php
│   └── loanschedule.class.php
├── payment/              # Loan payments
├── document.php          # Attached documents
└── admin/                # Module settings
```
## Loan Schedule (LoanSchedule)

Repayment schedule entries:

## Permissions

- `$user->hasRight('loan', 'read')` - View loans
- `$user->hasRight('loan', 'write')` - Create/edit loans
- `$user->hasRight('loan', 'delete')` - Delete loans
