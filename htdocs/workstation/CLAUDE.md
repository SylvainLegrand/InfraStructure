# htdocs/workstation - Workstations Module

Manufacturing workstation/work center management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Workstation |
| Table | llx_workstation_workstation |
| Element Type | workstation |
| Permission Key | workstation |

## Directory Structure

```
workstation/
├── workstation_card.php  # Workstation detail
├── workstation_list.php  # Workstation list
├── class/
│   └── workstation.class.php
├── workstation_document.php
└── admin/                # Module settings
```
## Workstation Types

| Type | Description |
|------|-------------|
| human | Manual work |
| machine | Automated machine |
| virtual | Planning only |

## Integration with MRP

Workstations are used in BOM routing to:

- Define production steps
- Calculate labor costs
- Plan capacity

## Permissions

- `$user->hasRight('workstation', 'read')` - View workstations
- `$user->hasRight('workstation', 'write')` - Create/edit workstations
- `$user->hasRight('workstation', 'delete')` - Delete workstations
