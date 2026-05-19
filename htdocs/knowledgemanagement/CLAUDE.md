# htdocs/knowledgemanagement - Knowledge Management Module

Knowledge base and documentation articles.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | KnowledgeRecord |
| Table | llx_knowledgemanagement_knowledgerecord |
| Element Type | knowledgerecord |
| Permission Key | knowledgemanagement |

## Directory Structure

```
knowledgemanagement/
├── knowledgerecord_card.php  # Article detail
├── knowledgerecord_list.php  # Article list
├── class/
│   └── knowledgerecord.class.php
├── knowledgerecord_document.php
└── admin/                # Module settings
```
## Features

- Rich text content (HTML editor)
- Multi-language support
- Category organization
- Ticket integration

## Ticket Integration

Knowledge articles can be linked to ticket categories for quick reference during support.

```php
// Link article to ticket category
$knowledgerecord->fk_c_ticket_category = $categoryid;
```

## Search

Full-text search across question and answer fields.

## Permissions

- `$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')` - View articles
- `$user->hasRight('knowledgemanagement', 'knowledgerecord', 'write')` - Create/edit articles
- `$user->hasRight('knowledgemanagement', 'knowledgerecord', 'delete')` - Delete articles
