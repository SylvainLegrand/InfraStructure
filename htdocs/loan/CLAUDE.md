# Loans Module

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

## Main Class (Loan)

```php
class Loan extends CommonObject
{
    public $table_element = 'loan';
    public $element = 'loan';

    public $capital;           // Principal amount
    public $datestart;         // Start date
    public $dateend;           // End date
    public $nbterm;            // Number of terms
    public $rate;              // Interest rate
    public $fk_bank;           // Linked bank account
}
```

## Loan Schedule (LoanSchedule)

Repayment schedule entries:

```php
class LoanSchedule extends CommonObject
{
    public $fk_loan;           // Parent loan
    public $datep;             // Payment date
    public $amount_capital;    // Principal portion
    public $amount_insurance;  // Insurance portion
    public $amount_interest;   // Interest portion
}
```

## Permissions

- `$user->hasRight('loan', 'read')` - View loans
- `$user->hasRight('loan', 'write')` - Create/edit loans
- `$user->hasRight('loan', 'delete')` - Delete loans
