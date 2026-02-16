# htdocs/resource - Resources Module

Resource (equipment, rooms, vehicles) management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Dolresource |
| Table | llx_resource |
| Element Type | resource |
| Permission Key | resource |

## Directory Structure

```
resource/
├── card.php              # Resource detail
├── list.php              # Resource list
├── class/
│   └── dolresource.class.php
├── agenda.php            # Resource schedule
├── contact.php           # Contacts
├── document.php          # Documents
└── admin/                # Module settings
```

## Main Class (Dolresource)

```php
class Dolresource extends CommonObject
{
    public $table_element = 'resource';
    public $element = 'dolresource';

    public $ref;               // Reference
    public $description;       // Description
    public $fk_code_type_resource;  // Resource type
    public $country_id;        // Country
    public $phone;             // Phone
    public $max_users;         // Max concurrent users
    public $tms;               // Last modification
}
```

## Resource Types

Resource types defined in dictionary `llx_c_type_resource`:

- Rooms
- Vehicles
- Equipment
- Custom types

## Resource Linking

Resources can be linked to:

- Events/actions
- Projects
- Interventions
- Other objects

```php
// Link resource to object
$resource->add_element_resource($element_id, $element_type);
```

## Permissions

- `$user->hasRight('resource', 'read')` - View resources
- `$user->hasRight('resource', 'write')` - Create/edit resources
- `$user->hasRight('resource', 'delete')` - Delete resources
