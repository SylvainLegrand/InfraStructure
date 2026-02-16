# Users Module

User and group management.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | User, UserGroup |
| Tables | llx_user, llx_usergroup |
| Element Types | user, usergroup |

## Directory Structure

```
user/
├── card.php              # User detail
├── list.php              # User list
├── class/
│   ├── user.class.php
│   └── usergroup.class.php
├── group/                # Group management
│   ├── card.php
│   └── list.php
├── perms.php             # Permission management
├── param_ihm.php         # UI preferences
└── admin/                # Module settings
```

## User Class

```php
class User extends CommonObject
{
    public $table_element = 'user';
    public $element = 'user';

    public $login;             // Login name
    public $pass;              // Password (hashed)
    public $firstname;         // First name
    public $lastname;          // Last name
    public $email;             // Email
    public $admin;             // Is admin (0/1)
    public $entity;            // Entity ID
    public $statut;            // Status (0=disabled, 1=enabled)
}
```

## Permission Checks

```php
// Check module permission
if ($user->hasRight('module', 'action')) { }

// Common permission patterns
$user->hasRight('societe', 'lire');      // Read third parties
$user->hasRight('societe', 'creer');     // Create/edit
$user->hasRight('societe', 'supprimer'); // Delete

// Admin check
if ($user->admin) { }
```

## User Groups

Groups define sets of permissions:

```php
class UserGroup extends CommonObject
{
    public $name;              // Group name
    public $entity;            // Entity ID
}

// Add user to group
$usergroup->addUser($user->id);
```

## User Context

`$user` global variable contains current logged-in user:

```php
$user->id;                     // User ID
$user->login;                  // Login name
$user->getFullName($langs);    // Full name
$user->email;                  // Email address
```
