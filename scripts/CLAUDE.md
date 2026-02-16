# CLI Scripts

Command-line scripts for automation, cron jobs, and system tasks.

## Usage

Scripts should be run from command line:

```bash
php scripts/scriptname.php [options]
```

## Common Scripts

| Script | Purpose |
|--------|---------|
| cron/cron_run_jobs.php | Execute scheduled jobs |
| emailings/mailing-send.php | Send mass mailings |
| invoices/email_unpaid_invoices.php | Send payment reminders |

## Script Initialization

Use `master.inc.php` for lightweight initialization:

```php
<?php
// CLI check
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

$res = @include dirname(__FILE__).'/../htdocs/master.inc.php';
if (!$res) {
    die("Include of master.inc.php failed");
}

// Script code here
```

## Cron Integration

Scripts designed for cron should:
- Accept `--quiet` flag for silent operation
- Return exit code 0 on success, non-zero on error
- Log to appropriate log files

## Security

- Never expose scripts via web
- Validate all input parameters
- Use `restrictedArea()` if needed for permission checks
