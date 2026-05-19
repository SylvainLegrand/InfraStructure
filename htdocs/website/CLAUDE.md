# htdocs/website - Website Module

Website builder and CMS.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | Website, WebsitePage |
| Tables | llx_website, llx_website_page |
| Element Types | website, website_page |
| Permission Key | website |

## Directory Structure

```
website/
├── index.php             # Website editor
├── card.php              # Website settings
├── class/
│   ├── website.class.php
│   └── websitepage.class.php
├── samples/              # Template samples
├── websiteaccount.php    # User accounts
└── admin/                # Module settings
```
## WebsitePage Class

## File Storage

Website files stored in:
- `documents/website/{ref}/` - Website root
- `documents/medias/` - Shared media

## Template System

Pages can include:
- PHP snippets (admin only)
- Dolibarr variables
- Shared templates

## Permissions

- `$user->hasRight('website', 'read')` - View websites
- `$user->hasRight('website', 'write')` - Create/edit pages
- `$user->hasRight('website', 'delete')` - Delete pages
- `$user->hasRight('website', 'writephp')` - Edit PHP content
