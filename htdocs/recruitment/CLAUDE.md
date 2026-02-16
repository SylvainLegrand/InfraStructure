# Recruitment Module

Job position and candidate management.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | RecruitmentJobPosition, RecruitmentCandidature |
| Tables | llx_recruitment_recruitmentjobposition, llx_recruitment_recruitmentcandidature |
| Permission Key | recruitment |

## Directory Structure

```
recruitment/
├── recruitmentjobposition_card.php   # Job position detail
├── recruitmentjobposition_list.php   # Job positions list
├── recruitmentcandidature_card.php   # Candidate detail
├── recruitmentcandidature_list.php   # Candidates list
├── class/
│   ├── recruitmentjobposition.class.php
│   └── recruitmentcandidature.class.php
└── admin/                # Module settings
```

## Job Position (RecruitmentJobPosition)

```php
class RecruitmentJobPosition extends CommonObject
{
    public $table_element = 'recruitment_recruitmentjobposition';
    public $element = 'recruitmentjobposition';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_RECRUITED = 2;
    const STATUS_CLOSED = 9;

    public $label;             // Position title
    public $description;       // Job description
    public $qty;               // Positions to fill
    public $date_planned;      // Start date
    public $fk_establishment;  // Location
    public $remuneration_suggested;
}
```

## Candidature (RecruitmentCandidature)

```php
class RecruitmentCandidature extends CommonObject
{
    public $table_element = 'recruitment_recruitmentcandidature';
    public $element = 'recruitmentcandidature';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CONTRACT_PROPOSED = 3;
    const STATUS_CONTRACT_REFUSED = 4;
    const STATUS_CONTRACT_SIGNED = 5;
    const STATUS_REFUSED = 8;
    const STATUS_CANCELED = 9;

    public $fk_recruitmentjobposition;  // Job position
    public $firstname;         // Candidate first name
    public $lastname;          // Candidate last name
    public $email;             // Email
    public $phone;             // Phone
    public $date_reception;    // Application date
    public $remuneration_requested;
}
```

## Recruitment Workflow

```
Job Position DRAFT → VALIDATED (open)
    ↓
Candidatures: DRAFT → VALIDATED → CONTRACT_PROPOSED → CONTRACT_SIGNED
                                ↘ REFUSED
    ↓
Job Position → RECRUITED/CLOSED
```

## Permissions

- `$user->hasRight('recruitment', 'recruitmentjobposition', 'read')` - View positions
- `$user->hasRight('recruitment', 'recruitmentjobposition', 'write')` - Manage positions
- `$user->hasRight('recruitment', 'recruitmentcandidature', 'read')` - View candidates
- `$user->hasRight('recruitment', 'recruitmentcandidature', 'write')` - Manage candidates
