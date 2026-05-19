# htdocs/webhook - Webhook Module

Outgoing webhook notifications.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Target |
| Table | llx_webhook_target |
| Element Type | webhook |
| Permission Key | webhook |

## Directory Structure

```
webhook/
├── target_card.php       # Webhook target detail
├── target_list.php       # Webhook targets list
├── class/
│   └── target.class.php
├── history.php           # Delivery history
└── admin/                # Module settings
```
## Trigger Events

Configure which triggers fire webhooks:

```php
// Example trigger codes:
// COMPANY_CREATE, COMPANY_MODIFY, COMPANY_DELETE
// BILL_CREATE, BILL_VALIDATE, BILL_PAYED
// ORDER_CREATE, ORDER_VALIDATE
```

## Webhook Payload

```json
{
  "event": "TRIGGER_CODE",
  "timestamp": 1234567890,
  "object": { ... },
  "signature": "hmac_sha256_signature"
}
```

## Signature Verification

Receivers verify authenticity:

```php
$computed = hash_hmac('sha256', $payload, $secret);
if ($computed === $signature) { /* valid */ }
```

## Permissions

- `$user->hasRight('webhook', 'target', 'read')` - View targets
- `$user->hasRight('webhook', 'target', 'write')` - Create/edit targets
- `$user->hasRight('webhook', 'target', 'delete')` - Delete targets
