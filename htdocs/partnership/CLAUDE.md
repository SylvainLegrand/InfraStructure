# htdocs/partnership - Partnership Module

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
