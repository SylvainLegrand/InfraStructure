# htdocs/multicurrency - Multi-Currency Module

Multi-currency support for commercial documents.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | MultiCurrency |
| Table | llx_multicurrency |
| Permission Key | multicurrency |

## Directory Structure

```
multicurrency/
├── class/
│   └── multicurrency.class.php
└── admin/                # Currency settings
```
## Usage in Documents

Commercial documents store:

- `multicurrency_code` - Currency code
- `multicurrency_tx` - Exchange rate at document time
- `multicurrency_total_ht` - Amount in foreign currency
- `multicurrency_total_tva` - VAT in foreign currency
- `multicurrency_total_ttc` - Total in foreign currency

## Exchange Rates

```php
// Get current rate
$rate = MultiCurrency::getIdAndTxFromCode($db, 'USD');

// Update rate
$multicurrency = new MultiCurrency($db);
$multicurrency->fetch(0, 'USD');
$multicurrency->updateRate($newrate);
```

## Automatic Rate Updates

Rates can be synced automatically from online services via cron job.

## Permissions

- `$user->hasRight('multicurrency', 'currency', 'read')` - View currencies
- `$user->hasRight('multicurrency', 'currency', 'write')` - Edit currencies
