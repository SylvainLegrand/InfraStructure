# Website Module

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

## Website Class

```php
class Website extends CommonObject
{
    public $table_element = 'website';
    public $element = 'website';

    public $ref;               // Website reference
    public $description;       // Description
    public $maincolor;         // Primary color
    public $maincolorbis;      // Secondary color
    public $lang;              // Default language
    public $fk_default_home;   // Home page ID
    public $virtualhost;       // Virtual host URL
}
```

## WebsitePage Class

```php
class WebsitePage extends CommonObject
{
    public $table_element = 'website_page';
    public $element = 'website_page';

    public $fk_website;        // Parent website
    public $pageurl;           // Page slug
    public $aliasalt;          // Alternate URLs
    public $title;             // Page title
    public $description;       // Meta description
    public $keywords;          // Meta keywords
    public $content;           // Page HTML content
    public $lang;              // Page language
    public $type_container;    // page, blogpost, etc.
}
```

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
