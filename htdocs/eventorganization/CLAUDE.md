# htdocs/eventorganization - Event Organization Module

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
## ConferenceOrBoothAttendee Class

## Project Integration

Events are typically linked to projects for comprehensive management.

## Public Registration

`public/` directory provides online event registration forms.

## Permissions

- `$user->hasRight('eventorganization', 'read')` - View events
- `$user->hasRight('eventorganization', 'write')` - Create/edit events
- `$user->hasRight('eventorganization', 'delete')` - Delete events
