# Projects Module

Project and task management.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | Project, Task |
| Tables | llx_projet, llx_projet_task |
| Element Types | project, project_task |
| Permission Key | projet |

## Directory Structure

```
projet/
├── card.php              # Project detail
├── list.php              # Project list
├── class/
│   ├── project.class.php
│   └── task.class.php
├── tasks/                # Task management
│   ├── task.php
│   ├── list.php
│   └── time.php          # Time tracking
├── ganttview.php         # Gantt chart
├── contact.php           # Project contacts
└── admin/                # Module settings
```

## Project Class

```php
class Project extends CommonObject
{
    public $table_element = 'projet';
    public $element = 'project';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;

    public $socid;             // Customer ID
    public $date_start;        // Start date
    public $date_end;          // End date
    public $opp_status;        // Opportunity status
    public $opp_amount;        // Opportunity amount
    public $opp_percent;       // Win probability
}
```

## Task Class

```php
class Task extends CommonObject
{
    public $table_element = 'projet_task';
    public $element = 'project_task';

    public $fk_projet;         // Parent project
    public $fk_task_parent;    // Parent task (hierarchy)
    public $label;             // Task name
    public $date_start;
    public $date_end;
    public $planned_workload;  // Planned hours
    public $progress;          // Completion percentage
}
```

## Time Tracking

```php
// Log time on task
$task->addTimeSpent($user, $date, $duration, $note);
```

## Permissions

- `$user->hasRight('projet', 'lire')` - View projects
- `$user->hasRight('projet', 'creer')` - Create/edit projects
- `$user->hasRight('projet', 'all', 'lire')` - View all projects
- `$user->hasRight('projet', 'time')` - Log time
