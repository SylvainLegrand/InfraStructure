# htdocs/user - Users Module

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

## User Context

`$user` global variable contains current logged-in user:

```php
$user->id;                     // User ID
$user->login;                  // Login name
$user->getFullName($langs);    // Full name
$user->email;                  // Email address
```
