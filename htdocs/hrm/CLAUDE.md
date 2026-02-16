# htdocs/hrm - HRM Module

Human Resource Management (establishments, jobs, skills).

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | Establishment, Job, Skill, SkillRank |
| Tables | llx_establishment, llx_hrm_job, llx_hrm_skill |
| Permission Key | hrm |

## Directory Structure

```
hrm/
├── establishment/        # Work locations
│   ├── card.php
│   └── list.php
├── job/                  # Job positions
│   ├── card.php
│   └── list.php
├── skill/                # Skills catalog
│   ├── card.php
│   └── list.php
├── class/
│   ├── establishment.class.php
│   ├── job.class.php
│   └── skill.class.php
├── evaluation/           # Performance evaluations
└── admin/                # Module settings
```

## Establishment

Work locations/offices:

```php
class Establishment extends CommonObject
{
    public $table_element = 'establishment';
    public $element = 'establishment';

    public $label;             // Name
    public $address;           // Address
    public $zip;               // Postal code
    public $town;              // City
    public $country_id;        // Country
}
```

## Job Positions

```php
class Job extends CommonObject
{
    public $table_element = 'hrm_job';
    public $element = 'job';

    public $label;             // Job title
    public $description;       // Job description
}
```

## Skills

```php
class Skill extends CommonObject
{
    public $table_element = 'hrm_skill';
    public $element = 'skill';

    public $label;             // Skill name
    public $description;       // Skill description
    public $skill_type;        // Type (technical, soft, etc.)
}
```

## Skill Assignment

Skills can be assigned to:
- Users (employee skills)
- Jobs (required skills)

## Permissions

- `$user->hasRight('hrm', 'read')` - View HRM data
- `$user->hasRight('hrm', 'write')` - Create/edit HRM data
- `$user->hasRight('hrm', 'delete')` - Delete HRM data
