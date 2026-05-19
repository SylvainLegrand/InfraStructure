# htdocs/recruitment - Recruitment Module

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
## Candidature (RecruitmentCandidature)

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
