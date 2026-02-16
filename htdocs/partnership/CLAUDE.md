# Partnership Module

Partner/affiliate relationship management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Partnership |
| Table | llx_partnership |
| Element Type | partnership |
| Permission Key | partnership |

## Directory Structure

```
partnership/
├── partnership_card.php  # Partnership detail
├── partnership_list.php  # Partnership list
├── class/
│   └── partnership.class.php
├── partnership_document.php
└── admin/                # Module settings
```

## Main Class (Partnership)

```php
class Partnership extends CommonObject
{
    public $table_element = 'partnership';
    public $element = 'partnership';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REFUSED = 3;
    const STATUS_CANCELED = 9;

    public $ref;               // Reference
    public $fk_soc;            // Partner company
    public $fk_member;         // Partner member
    public $date_partnership_start;
    public $date_partnership_end;
    public $url;               // Partner website
    public $note_public;       // Public notes
    public $count_last_url_check_error;
}
```

## Partner Types

Partnerships can be linked to:
- Third parties (`fk_soc`)
- Members (`fk_member`)

## Workflow

```
DRAFT → VALIDATED (submitted) → APPROVED/REFUSED
```

## Features

- Partner commission tracking
- URL monitoring (partner websites)
- Date-based validity

## Permissions

- `$user->hasRight('partnership', 'read')` - View partnerships
- `$user->hasRight('partnership', 'write')` - Create/edit partnerships
- `$user->hasRight('partnership', 'delete')` - Delete partnerships
