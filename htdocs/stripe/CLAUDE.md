# Stripe Module

Stripe payment gateway integration.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | stripe |

## Directory Structure

```
stripe/
├── class/
│   ├── stripe.class.php
│   └── actions_stripe.class.php
├── payment.php           # Process payment
├── ipn.php               # Webhook handler
├── confirm.php           # Payment confirmation
├── connect.php           # Stripe Connect
└── admin/                # Module settings
```

## Configuration

Set in module admin:

- `STRIPE_TEST_PUBLISHABLE_KEY` - Test publishable key
- `STRIPE_TEST_SECRET_KEY` - Test secret key
- `STRIPE_LIVE_PUBLISHABLE_KEY` - Live publishable key
- `STRIPE_LIVE_SECRET_KEY` - Live secret key
- `STRIPE_LIVE` - Enable live mode (0/1)

## Payment Flow

1. Customer selects Stripe payment
2. Payment intent created via API
3. Customer enters card details
4. Stripe processes payment
5. Webhook confirms payment
6. Invoice marked as paid

## Webhook Handling

`ipn.php` receives Stripe webhooks:

```php
// Webhook events handled:
// - payment_intent.succeeded
// - payment_intent.payment_failed
// - charge.refunded
```

## Stripe API

```php
$stripe = new Stripe($db);
$stripe->getStripeAccount($mode);

// Create payment intent
$paymentintent = $stripe->getPaymentIntent($amount, $currency, $customer);
```

## Permissions

- `$user->hasRight('stripe', 'read')` - View Stripe data
- `$user->hasRight('stripe', 'write')` - Configure Stripe
