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
## Public Interface

`public/` directory provides forms for external ticket creation without login.

## Email Integration

Tickets integrate with email collector module for automatic ticket creation from emails.

## Permissions

- `$user->hasRight('ticket', 'read')` - View tickets
- `$user->hasRight('ticket', 'write')` - Create/edit tickets
- `$user->hasRight('ticket', 'delete')` - Delete tickets
- `$user->hasRight('ticket', 'manage')` - Full management
