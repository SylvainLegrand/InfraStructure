# htdocs/adherents - Members Module

Association/club membership management.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | Adherent, Subscription |
| Tables | llx_adherent, llx_subscription |
| Element Types | member, subscription |
| Permission Key | adherent |

## Directory Structure

```
adherents/
├── card.php              # Member detail
├── list.php              # Member list
├── class/
│   ├── adherent.class.php
│   └── subscription.class.php
├── subscription/         # Subscriptions
│   ├── card.php
│   └── list.php
├── type.php              # Member types
├── stats/                # Statistics
├── public/               # Public registration
└── admin/                # Module settings
```

## Adherent Class

```php
class Adherent extends CommonObject
{
    public $table_element = 'adherent';
    public $element = 'member';

    // Status constants
    const STATUS_DRAFT = -1;
    const STATUS_VALIDATED = 1;
    const STATUS_RESILIATED = 0;
    const STATUS_EXCLUDED = -2;

    public $civility_code;     // Mr, Mrs, etc.
    public $firstname;         // First name
    public $lastname;          // Last name
    public $login;             // Member login
    public $email;             // Email
    public $typeid;            // Member type
    public $datefin;           // End of membership
    public $morphy;            // 'mor' or 'phy' (company/individual)
    public $fk_soc;            // Linked third party
}
```

## Subscription Class

```php
class Subscription extends CommonObject
{
    public $table_element = 'subscription';
    public $element = 'subscription';

    public $fk_adherent;       // Member ID
    public $dateh;             // Start date
    public $datef;             // End date
    public $amount;            // Subscription amount
    public $fk_bank;           // Bank account
}
```

## Member Types

Defined in `llx_adherent_type`:

- Annual membership
- Lifetime membership
- Custom types

## Public Registration

`public/` directory allows online member registration and renewal.

## Permissions

- `$user->hasRight('adherent', 'lire')` - View members
- `$user->hasRight('adherent', 'creer')` - Create/edit members
- `$user->hasRight('adherent', 'supprimer')` - Delete members
- `$user->hasRight('adherent', 'cotisation', 'creer')` - Manage subscriptions
