# htdocs/holiday - Holiday Module

Leave/absence management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Holiday |
| Table | llx_holiday |
| Element Type | holiday |
| Permission Key | holiday |

## Directory Structure

```
holiday/
├── card.php              # Leave request detail
├── list.php              # Leave request list
├── class/
│   └── holiday.class.php
├── define_holiday.php    # Leave type allocation
├── month_report.php      # Monthly report
├── view_log.php          # Change log
└── admin/                # Module settings
```
## Leave Types

Defined in dictionary `llx_c_holiday_types`:

- Paid leave
- Sick leave
- Unpaid leave
- Custom types

## Leave Allocation

`define_holiday.php` manages leave balance allocation per user and type.

## Approval Workflow

```
DRAFT → VALIDATED (submitted) → APPROVED/REFUSED
```

## Permissions

- `$user->hasRight('holiday', 'read')` - View leave requests
- `$user->hasRight('holiday', 'write')` - Create requests
- `$user->hasRight('holiday', 'approve')` - Approve requests
- `$user->hasRight('holiday', 'read_all')` - View all users' requests
