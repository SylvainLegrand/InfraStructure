# Salaries Module

Employee salary payments management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Salary |
| Table | llx_salary |
| Element Type | salary |
| Permission Key | salaries |

## Directory Structure

```
salaries/
├── card.php              # Salary payment detail
├── list.php              # Salary list
├── class/
│   └── salary.class.php
├── document.php          # Attached documents
├── info.php              # Payment info
└── admin/                # Module settings
```

## Main Class (Salary)

```php
class Salary extends CommonObject
{
    public $table_element = 'salary';
    public $element = 'salary';

    public $fk_user;           // Employee user ID
    public $amount;            // Salary amount
    public $datesp;            // Period start date
    public $dateep;            // Period end date
    public $datep;             // Payment date
    public $fk_bank;           // Bank account
    public $label;             // Description
}
```

## Features

- Salary payment tracking
- Bank account linkage
- Period-based management
- Integration with accounting

## Bank Payment

When linked to a bank account, salary payments create bank transactions.

```php
$salary->create($user);
$salary->addPaymentToBank($user, 'payment_salary', $label, $bankaccountid);
```

## Permissions

- `$user->hasRight('salaries', 'read')` - View salaries
- `$user->hasRight('salaries', 'write')` - Create/edit salaries
- `$user->hasRight('salaries', 'delete')` - Delete salaries
