# Contacts Module

Contact persons management for third parties.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Contact |
| Table | llx_socpeople |
| Element Type | contact |
| Permission Key | contact |

## Directory Structure

```
contact/
├── card.php              # Contact detail
├── list.php              # Contact list
├── class/
│   └── contact.class.php
├── perso.php             # Personal information
├── document.php          # Attached documents
└── admin/                # Module settings
```

## Main Class (Contact)

```php
class Contact extends CommonObject
{
    public $table_element = 'socpeople';
    public $element = 'contact';

    public $fk_soc;        // Linked third party
    public $civility_code; // Mr, Mrs, etc.
    public $firstname;
    public $lastname;
    public $email;
    public $phone_pro;
    public $phone_mobile;
}
```

## Contact Roles

Contacts can be assigned roles on documents:

```php
// Link contact to object with role
$object->add_contact($contactid, $type_contact, $source);

// Contact type codes
// BILLING - Billing contact
// SHIPPING - Shipping contact
// CUSTOMER - Customer contact
```

## Permissions

- `$user->hasRight('contact', 'lire')` - View contacts
- `$user->hasRight('contact', 'creer')` - Create/edit contacts
- `$user->hasRight('contact', 'supprimer')` - Delete contacts
- `$user->hasRight('contact', 'export')` - Export contacts
