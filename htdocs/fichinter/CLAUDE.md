# htdocs/fichinter - Interventions Module

Field service intervention management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Fichinter |
| Table | llx_fichinter |
| Element Type | fichinter |
| Permission Key | ficheinter |

## Directory Structure

```
fichinter/
├── card.php              # Intervention detail
├── list.php              # Intervention list
├── class/
│   └── fichinter.class.php
├── fiche.php             # Legacy card view
├── contact.php           # Contacts
├── document.php          # Documents
└── admin/                # Module settings
```
## Intervention Lines

## Status Workflow

```
DRAFT → VALIDATED → BILLED → CLOSED
```

## Link to Contracts

Interventions can be linked to service contracts for tracking support activities.

## Permissions

- `$user->hasRight('ficheinter', 'lire')` - View interventions
- `$user->hasRight('ficheinter', 'creer')` - Create/edit interventions
- `$user->hasRight('ficheinter', 'supprimer')` - Delete interventions
