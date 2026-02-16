# htdocs/cron - Cron Module

Scheduled job management for background tasks.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Cronjob |
| Table | llx_cronjob |
| Element Type | cronjob |
| Permission Key | cron |

## Directory Structure

```
cron/
├── card.php              # Job detail/edit page
├── list.php              # Job list page
├── info.php              # Job info page
├── class/
│   └── cronjob.class.php # Cronjob business object
└── admin/                # Module settings
```

## Creating Scheduled Jobs

Use the `/dolibarr-cron` skill for creating scheduled jobs in modules.

The skill covers:
- Job registration in module descriptors
- Method job implementation patterns
- Command job configuration
- Batch processing and error handling
- System cron setup

## Permissions

- `$user->hasRight('cron', 'read')` - View jobs
- `$user->hasRight('cron', 'create')` - Create/edit jobs
- `$user->hasRight('cron', 'delete')` - Delete jobs
- `$user->hasRight('cron', 'execute')` - Execute jobs manually

## Wiki Documentation

https://wiki.dolibarr.org/index.php/Module_Scheduled_jobs
