# htdocs/projet - Projects Module

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
## Task Class

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
