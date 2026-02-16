# Blocked Log Module

Unalterable audit logs (French NF525 compliance).

## Module Info

| Property | Value |
|----------|-------|
| Main Class | BlockedLog |
| Table | llx_blockedlog |
| Element Type | blockedlog |
| Permission Key | blockedlog |

## Directory Structure

```
blockedlog/
├── list.php              # Log viewer
├── class/
│   └── blockedlog.class.php
├── admin/                # Module settings
└── authority/            # Certification export
```

## BlockedLog Class

```php
class BlockedLog extends CommonObject
{
    public $table_element = 'blockedlog';
    public $element = 'blockedlog';

    public $entity;            // Entity ID
    public $date_creation;     // Log timestamp
    public $tms;               // Technical timestamp
    public $action;            // Action code
    public $amounts;           // Amount (signed)
    public $element;           // Object type
    public $fk_object;         // Object ID
    public $ref_object;        // Object reference
    public $fk_user;           // User who made the change
    public $user_fullname;     // User full name (snapshot)
    public $object_data;       // Object data (JSON)
    public $signature;         // Chain signature
}
```

## Logged Actions

| Action | Description |
|--------|-------------|
| BILL_VALIDATE | Invoice validated |
| BILL_DELETE | Invoice deleted |
| BILL_PAYED | Invoice paid |
| BILL_SENTBYMAIL | Invoice sent |
| PAYMENT_* | Payment actions |
| DON_* | Donation actions |

## Chain Integrity

Each log entry contains a signature chaining to the previous entry:

```php
$signature = hash('sha256', $previoussignature . $object_data);
```

## Certification Export

Export logs for tax authority verification:

```php
// Export to XML/PDF for audit
$blockedlog->getExportAuthority($datestart, $dateend);
```

## Legal Context

Required for French businesses (NF525/NF203 certification).

## Permissions

- `$user->hasRight('blockedlog', 'read')` - View logs
