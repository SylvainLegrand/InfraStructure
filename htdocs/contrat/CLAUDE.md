# htdocs/contrat - Contracts Module

Service contracts and subscriptions management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Contrat |
| Line Class | ContratLigne |
| Table | llx_contrat |
| Element Type | contrat |
| Permission Key | contrat |

## Directory Structure

```
contrat/
├── card.php              # Contract detail
├── list.php              # Contract list
├── class/
│   └── contrat.class.php
├── services_list.php     # All services list
├── document.php          # Attached documents
└── admin/                # Module settings
```
## Service Line Status (ContratLigne)

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_INITIAL | 0 | Not started |
| STATUS_OPEN | 4 | Running |
| STATUS_CLOSED | 5 | Closed |

## Key Features

- Multi-line contracts with individual service status
- Service date tracking (start, end)
- Automatic renewal options
- Link to interventions

## Permissions

- `$user->hasRight('contrat', 'lire')` - View contracts
- `$user->hasRight('contrat', 'creer')` - Create/edit contracts
- `$user->hasRight('contrat', 'supprimer')` - Delete contracts
- `$user->hasRight('contrat', 'activer')` - Activate services
