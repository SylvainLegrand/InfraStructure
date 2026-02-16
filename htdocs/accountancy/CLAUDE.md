# htdocs/accountancy - Accountancy Module

Double-entry accounting system.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | AccountingAccount, BookKeeping, AccountancyCategory |
| Tables | llx_accounting_account, llx_accounting_bookkeeping, llx_c_accounting_category |
| Permission Key | accounting |

## Directory Structure

```
accountancy/
├── bookkeeping/
│   ├── list.php          # Journal entries list
│   ├── card.php          # Entry detail
│   └── balance.php       # Account balances
├── journal/              # Journal management
├── customer/             # Customer binding
├── supplier/             # Supplier binding
├── expensereport/        # Expense binding
├── class/
│   ├── accountingaccount.class.php
│   └── bookkeeping.class.php
├── admin/                # Chart of accounts setup
└── closure/              # Fiscal year closure
```

## Key Classes

### AccountingAccount

Chart of accounts entries.

```php
class AccountingAccount extends CommonObject
{
    public $account_number;     // Account code
    public $label;              // Account name
    public $account_category;   // Asset, Liability, etc.
    public $fk_accounting_category;
}
```

### BookKeeping

Journal entries (écritures).

```php
class BookKeeping extends CommonObject
{
    public $doc_date;           // Document date
    public $doc_type;           // Document type
    public $doc_ref;            // Reference
    public $numero_compte;      // Account number
    public $debit;              // Debit amount
    public $credit;             // Credit amount
}
```

### AccountancyCategory

Custom groupings of accounting accounts for reporting.

```php
class AccountancyCategory
{
    public $id;                 // Primary key
    public $code;               // Category code (e.g., 'INCOMES')
    public $label;              // Category label
    public $sens;               // Direction: 0=credit-debit, 1=debit-credit
    public $category_type;      // 0=normal, 1=computed (formula-based)
    public $formula;            // Formula referencing other category rowids
    public $position;           // Display sort order
    public $fk_country;         // Country restriction (NULL=all)
}
```

**Category types:** 0=Normal (direct account grouping), 1=Computed (formula like "1 + 2" referencing rowids)

**Default categories:** `INCOMES`, `EXPENSES`, `PROFIT` (computed)

```php
$category = new AccountancyCategory($db);
$category->fetch($id, $code, $label);

// Account management
$category->display($id);                          // Get accounts in category
$category->updateAccAcc($cat_id, $account_ids);   // Add accounts to category

// Financial calculations
$category->getSumDebitCredit($accounts, $date_start, $date_end, $sens);
// Results: $category->sdc, $category->sdcpermonth, $category->sdcperaccount
```

## Accounting Workflow

1. Create documents (invoices, payments)
2. Bind documents to accounting accounts
3. Transfer to bookkeeping ledger
4. Validate entries
5. Close fiscal year

## Permissions

- `$user->hasRight('accounting', 'read')` - View accounting
- `$user->hasRight('accounting', 'write')` - Create entries
- `$user->hasRight('accounting', 'settup')` - Configure accounting
