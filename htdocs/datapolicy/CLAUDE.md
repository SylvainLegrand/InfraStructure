# htdocs/datapolicy - Data Policy Module

GDPR compliance and data protection management.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | datapolicy |

## Directory Structure

```
datapolicy/
├── class/
│   └── datapolicy.class.php
├── lib/
│   └── datapolicy.lib.php
└── admin/                # Module settings
```

## Features

### Data Retention

Configure automatic anonymization of old data:

```php
// Constants for retention periods
DATAPOLICY_TIERS_CLIENT        // Customer retention (months)
DATAPOLICY_TIERS_PROSPECT      // Prospect retention (months)
DATAPOLICY_TIERS_NIPROSPECT_NICLIENT  // Other third parties
DATAPOLICY_CONTACT_CLIENT      // Customer contacts
DATAPOLICY_CONTACT_PROSPECT    // Prospect contacts
DATAPOLICY_ADHERENT            // Members
```

### Anonymization

Anonymize personal data after retention period:

```php
// Fields anonymized:
// - Name → "XXXX"
// - Email → "xxx@xxx.com"
// - Phone → "XXXXXXXX"
// - Address → "XXXX"
```

### Data Export

Generate data export for individual requests (GDPR right to portability):

```php
$datapolicy = new DataPolicy($db);
$datapolicy->getAllDataForThirdparty($socid);
```

### Consent Tracking

Track consent for data processing:

- Collection date
- Consent type
- Purpose
- Withdrawal date

## Cron Jobs

Automatic processing via scheduled jobs:

- Check retention periods
- Send anonymization warnings
- Execute anonymization

## Configuration

Set retention periods and enable automatic processing in module admin.

## Permissions

Requires admin access for configuration.
