# Categories Module

Hierarchical categorization for all object types.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Categorie |
| Table | llx_categorie |
| Element Type | category |
| Permission Key | categorie |

## Directory Structure

```
categories/
├── card.php              # Category detail
├── index.php             # Category browser
├── class/
│   └── categorie.class.php
├── viewcat.php           # Category view
└── admin/                # Module settings
```

## Main Class (Categorie)

```php
class Categorie extends CommonObject
{
    public $table_element = 'categorie';
    public $element = 'category';

    // Category types
    const TYPE_PRODUCT = 0;
    const TYPE_SUPPLIER = 1;
    const TYPE_CUSTOMER = 2;
    const TYPE_MEMBER = 3;
    const TYPE_CONTACT = 4;
    const TYPE_USER = 5;
    const TYPE_PROJECT = 6;
    const TYPE_ACCOUNT = 7;
    const TYPE_BANK_LINE = 8;
    const TYPE_WAREHOUSE = 9;
    const TYPE_ACTIONCOMM = 10;
    const TYPE_WEBSITE_PAGE = 11;
    const TYPE_TICKET = 12;
    const TYPE_KNOWLEDGEMANAGEMENT = 13;

    public $fk_parent;         // Parent category (for hierarchy)
    public $label;             // Category name
    public $type;              // Category type (see constants)
    public $color;             // Display color
}
```

## Usage

```php
// Get categories for an object
$categories = $categorie->containing($object->id, $type);

// Add object to category
$categorie->add_type($object, $type);

// Remove object from category
$categorie->del_type($object, $type);
```

## Category Colors

Categories can have colors for visual identification in lists.

## Permissions

- `$user->hasRight('categorie', 'lire')` - View categories
- `$user->hasRight('categorie', 'creer')` - Create/edit categories
- `$user->hasRight('categorie', 'supprimer')` - Delete categories
