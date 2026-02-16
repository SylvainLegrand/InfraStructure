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

## Main Class (Fichinter)

```php
class Fichinter extends CommonObject
{
    public $table_element = 'fichinter';
    public $element = 'fichinter';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_BILLED = 2;
    const STATUS_CLOSED = 3;

    public $socid;             // Customer ID
    public $fk_contrat;        // Linked contract
    public $fk_projet;         // Linked project
    public $datec;             // Creation date
    public $datev;             // Validation date
    public $duration;          // Total duration
}
```

## Intervention Lines

```php
class FichinterLigne extends CommonObjectLine
{
    public $fk_fichinter;      // Parent intervention
    public $desc;              // Description
    public $date;              // Date
    public $duree;             // Duration (seconds)
}
```

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
