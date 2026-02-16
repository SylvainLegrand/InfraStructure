# Event Organization Module

Event and conference management.

## Module Info

| Property | Value |
|----------|-------|
| Main Classes | ConferenceOrBooth, ConferenceOrBoothAttendee |
| Tables | llx_eventorganization_conferenceorbooth |
| Element Type | conferenceorbooth |
| Permission Key | eventorganization |

## Directory Structure

```
eventorganization/
├── conferenceorbooth_card.php        # Event/booth detail
├── conferenceorbooth_list.php        # Events list
├── conferenceorboothattendee_card.php # Attendee detail
├── conferenceorboothattendee_list.php # Attendees list
├── class/
│   ├── conferenceorbooth.class.php
│   └── conferenceorboothattendee.class.php
├── public/               # Public registration
└── admin/                # Module settings
```

## ConferenceOrBooth Class

```php
class ConferenceOrBooth extends CommonObject
{
    public $table_element = 'eventorganization_conferenceorbooth';
    public $element = 'conferenceorbooth';

    // Status constants
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 9;

    public $ref;               // Reference
    public $label;             // Event name
    public $fk_project;        // Linked project
    public $fk_soc;            // Organizer
    public $datep;             // Event date
    public $datef;             // End date
    public $location;          // Venue
    public $note_public;       // Public notes
    public $note_private;      // Private notes
}
```

## ConferenceOrBoothAttendee Class

```php
class ConferenceOrBoothAttendee extends CommonObject
{
    public $fk_actioncomm;     // Linked event
    public $fk_soc;            // Attendee company
    public $email;             // Attendee email
    public $date_subscription; // Registration date
    public $amount;            // Registration fee
    public $status;            // Registration status
}
```

## Project Integration

Events are typically linked to projects for comprehensive management.

## Public Registration

`public/` directory provides online event registration forms.

## Permissions

- `$user->hasRight('eventorganization', 'read')` - View events
- `$user->hasRight('eventorganization', 'write')` - Create/edit events
- `$user->hasRight('eventorganization', 'delete')` - Delete events
