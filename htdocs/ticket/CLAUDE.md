# htdocs/ticket - Ticket Module

Helpdesk and support ticket management.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Ticket |
| Table | llx_ticket |
| Element Type | ticket |
| Permission Key | ticket |

## Directory Structure

```
ticket/
├── card.php              # Ticket detail
├── list.php              # Ticket list
├── class/
│   └── ticket.class.php
├── agenda.php            # Ticket events
├── contact.php           # Contacts
├── document.php          # Documents
├── messaging.php         # Messages
├── public/               # Public ticket creation
└── admin/                # Module settings
```

## Main Class (Ticket)

```php
class Ticket extends CommonObject
{
    public $table_element = 'ticket';
    public $element = 'ticket';

    // Status constants
    const STATUS_NOT_READ = 0;
    const STATUS_READ = 1;
    const STATUS_ASSIGNED = 2;
    const STATUS_IN_PROGRESS = 3;
    const STATUS_NEED_MORE_INFO = 5;
    const STATUS_WAITING = 7;
    const STATUS_CLOSED = 8;
    const STATUS_CANCELED = 9;

    public $track_id;          // Unique tracking ID
    public $subject;           // Ticket subject
    public $message;           // Initial message
    public $fk_soc;            // Customer ID
    public $fk_user_assign;    // Assigned user
    public $type_code;         // Ticket type
    public $category_code;     // Ticket category
    public $severity_code;     // Priority/severity
}
```

## Public Interface

`public/` directory provides forms for external ticket creation without login.

## Email Integration

Tickets integrate with email collector module for automatic ticket creation from emails.

## Permissions

- `$user->hasRight('ticket', 'read')` - View tickets
- `$user->hasRight('ticket', 'write')` - Create/edit tickets
- `$user->hasRight('ticket', 'delete')` - Delete tickets
- `$user->hasRight('ticket', 'manage')` - Full management
