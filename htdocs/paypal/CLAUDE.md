# htdocs/paypal - PayPal Module

PayPal payment gateway integration.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | paypal |

## Directory Structure

```
paypal/
├── class/
│   └── paypal.class.php
├── payment.php           # Process payment
├── ipn.php               # IPN handler
└── admin/                # Module settings
```

## Configuration

Set in module admin:

- `PAYPAL_API_USER` - API username
- `PAYPAL_API_PASSWORD` - API password
- `PAYPAL_API_SIGNATURE` - API signature
- `PAYPAL_API_SANDBOX` - Sandbox mode (0/1)

## Payment Flow

1. Customer selects PayPal payment
2. Redirect to PayPal
3. Customer authorizes payment
4. Return to Dolibarr
5. IPN confirms payment
6. Invoice marked as paid

## IPN (Instant Payment Notification)

`ipn.php` receives PayPal notifications:

```php
// IPN events handled:
// - Completed
// - Pending
// - Failed
// - Refunded
```

## Public Payment Links

Generate payment links for invoices:

```php
$url = dol_buildpath('/public/payment/newpayment.php', 3);
$url .= '?source=invoice&ref='.urlencode($invoice->ref);
```

## Permissions

Configuration requires admin access. No specific module permissions.
