# htdocs/emailcollector - Email Collector Module

Automatic email collection and processing.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | EmailCollector |
| Table | llx_emailcollector_emailcollector |
| Element Type | emailcollector |
| Permission Key | emailcollector |

## Directory Structure

```
emailcollector/
├── emailcollector_card.php   # Collector detail
├── emailcollector_list.php   # Collectors list
├── class/
│   ├── emailcollector.class.php
│   └── emailcollectorfilter.class.php
│   └── emailcollectoraction.class.php
└── admin/                # Module settings
```
## Filters

Define which emails to process:

## Actions

Define what to do with matching emails:

## Action Types

| Type | Description |
|------|-------------|
| project | Create/link project |
| ticket | Create ticket |
| thirdparty | Create/link third party |
| move | Move email |
| delete | Delete email |

## Cron Integration

Collectors run via cron job:

```bash
php scripts/emailcollector/collect.php
```

## Permissions

- `$user->hasRight('emailcollector', 'read')` - View collectors
- `$user->hasRight('emailcollector', 'write')` - Create/edit collectors
- `$user->hasRight('emailcollector', 'delete')` - Delete collectors
