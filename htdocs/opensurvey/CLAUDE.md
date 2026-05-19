# htdocs/opensurvey - OpenSurvey Module

Poll and survey management (meeting scheduling).

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Opensurveysondage |
| Tables | llx_opensurvey_sondage, llx_opensurvey_user_studs |
| Element Type | opensurvey |
| Permission Key | opensurvey |

## Directory Structure

```
opensurvey/
├── card.php              # Survey detail
├── list.php              # Survey list
├── class/
│   └── opensurveysondage.class.php
├── public/               # Public voting interface
│   └── studs.php         # Vote submission
├── results.php           # Survey results
├── wizard/               # Survey creation wizard
│   ├── create_date.php
│   └── create_classic.php
└── admin/                # Module settings
```
## Survey Types

| Type | Use Case |
|------|----------|
| Date poll | Find best meeting time |
| Classic | Multiple choice questions |

## Public Voting

`public/studs.php` allows external participants to vote without login.

## Vote Storage

Votes stored in `llx_opensurvey_user_studs`:

```php
// Vote values
// 0 = Not available
// 1 = Available
// 2 = If needed
```

## Permissions

- `$user->hasRight('opensurvey', 'read')` - View surveys
- `$user->hasRight('opensurvey', 'write')` - Create/edit surveys
- `$user->hasRight('opensurvey', 'delete')` - Delete surveys
