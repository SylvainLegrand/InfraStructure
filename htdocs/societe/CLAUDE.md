# htdocs/societe - Third Parties Module

Customers, suppliers, and prospects management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Societe |
| Table | llx_societe |
| Element Type | societe |
| Permission Key | societe |

## Directory Structure

```
societe/
├── card.php              # Third party detail
├── list.php              # Third party list
├── class/
│   └── societe.class.php
├── contact.php           # Linked contacts
├── document.php          # Attached documents
├── price.php             # Special prices
├── consumption.php       # Consumption stats
└── admin/                # Module settings
```
## Third Party Types

| Property | Value | Description |
|----------|-------|-------------|
| client | 0 | Neither |
| client | 1 | Customer |
| client | 2 | Prospect |
| client | 3 | Customer and Prospect |
| fournisseur | 1 | Supplier |

## Permissions

- `$user->hasRight('societe', 'lire')` - View third parties
- `$user->hasRight('societe', 'creer')` - Create/edit third parties
- `$user->hasRight('societe', 'supprimer')` - Delete third parties
- `$user->hasRight('societe', 'export')` - Export third parties
