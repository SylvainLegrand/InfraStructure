# Email Collector Module

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

## EmailCollector Class

```php
class EmailCollector extends CommonObject
{
    public $table_element = 'emailcollector_emailcollector';
    public $element = 'emailcollector';

    // Status constants
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public $label;             // Collector name
    public $host;              // IMAP server
    public $login;             // IMAP login
    public $password;          // IMAP password
    public $source_directory;  // IMAP folder
    public $datelastresult;    // Last run date
}
```

## Filters

Define which emails to process:

```php
class EmailCollectorFilter extends CommonObject
{
    public $fk_emailcollector;
    public $type;              // from, to, subject, etc.
    public $rulevalue;         // Filter value
}
```

## Actions

Define what to do with matching emails:

```php
class EmailCollectorAction extends CommonObject
{
    public $fk_emailcollector;
    public $type;              // Action type
    public $actionparam;       // Action parameters
}
```

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
