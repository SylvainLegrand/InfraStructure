# Core Framework

Framework classes, libraries, modules, database abstraction, triggers, and hooks.

## Directory Structure

| Subdirectory | Purpose |
|--------------|---------|
| class/ | Core framework classes (CommonObject, Form, etc.) |
| lib/ | Helper function libraries |
| modules/ | Module descriptors and numbering models |
| triggers/ | Event trigger system |
| boxes/ | Dashboard widget base classes |
| tpl/ | Template fragments |
| db/ | Database driver classes |
| login/ | Authentication handlers |
| menus/ | Menu system classes |
| substitutions/ | Variable substitution system |

## Key Classes

### CommonObject (class/commonobject.class.php)

Base class for all business objects. See `/dolibarr-class-conventions` skill for class structure, $fields array, CRUD patterns, and status workflows.

### Form (class/html.form.class.php)

Form helper for generating HTML inputs (selectors, date pickers, etc.). See `/dolibarr-page-patterns` skill for usage examples.

## Libraries (lib/)

| File | Purpose |
|------|---------|
| functions.lib.php | Core helper functions |
| date.lib.php | Date manipulation |
| files.lib.php | File operations |
| security.lib.php | Security functions |
| pdf.lib.php | PDF generation helpers |

## Triggers

Event system for cross-module notifications. See `/dolibarr-triggers` skill.

## Hooks

Extension points in core pages. See `/dolibarr-hooks` skill.
