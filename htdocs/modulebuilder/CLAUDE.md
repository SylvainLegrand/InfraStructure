# htdocs/modulebuilder - Module Builder

Development tool for creating Dolibarr modules.

## Module Info

| Property | Value |
|----------|-------|
| Location | Home > Developer Tools > Module Builder |
| Permission Key | modulebuilder |

## Directory Structure

```
modulebuilder/
├── index.php             # Module Builder UI
├── template/             # Module templates
│   ├── core/
│   │   └── modules/
│   ├── class/
│   │   └── myobject.class.php
│   ├── sql/
│   └── ...
└── admin/                # Module settings
```

## Features

### Create Module

1. Enter module name and description
2. Generate module descriptor (`modMyModule.class.php`)
3. Module created in `htdocs/custom/mymodule/`

### Add Objects

For each object:
1. Define fields (name, type, size, required)
2. Generate:
   - Class file (`class/myobject.class.php`)
   - SQL files (`sql/llx_*.sql`)
   - Card page (`myobject_card.php`)
   - List page (`myobject_list.php`)

### Configure Module

- Permissions
- Menus (`/dolibarr-menus` skill)
- Tabs (`/dolibarr-tabs` skill)
- Hooks (`/dolibarr-hooks` skill)
- Triggers (`/dolibarr-triggers` skill)

## Generated Structure

```
custom/mymodule/
├── core/modules/
│   └── modMyModule.class.php
├── class/
│   └── myobject.class.php
├── sql/
│   ├── llx_mymodule_myobject.sql
│   └── llx_mymodule_myobject.key.sql
├── myobject_card.php
├── myobject_list.php
└── admin/setup.php
```

## Regeneration

Module Builder can regenerate files after field changes. Backup custom code first.

## Export

Export module as ZIP for distribution.

## Permissions

- `$user->hasRight('modulebuilder', 'read')` - Access Module Builder
- `$user->hasRight('modulebuilder', 'run')` - Generate code
