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

## Skills

## Skill Assignment

Skills can be assigned to:
- Users (employee skills)
- Jobs (required skills)

## Permissions

- `$user->hasRight('hrm', 'read')` - View HRM data
- `$user->hasRight('hrm', 'write')` - Create/edit HRM data
- `$user->hasRight('hrm', 'delete')` - Delete HRM data
