# DAV Module

CalDAV and CardDAV server integration.

## Module Info

| Property | Value |
|----------|-------|
| Permission Key | dav |

## Directory Structure

```
dav/
├── dav.php               # DAV server entry point
├── class/
│   └── dav.class.php
└── admin/                # Module settings
```

## CalDAV

Calendar synchronization with external clients (Thunderbird, mobile devices, etc.).

### Endpoints

```
{baseurl}/dav.php/calendars/{username}/default/
```

### Features

- Sync Dolibarr actions/events
- Two-way synchronization
- ICS format support

## CardDAV

Contact synchronization with external clients.

### Endpoints

```
{baseurl}/dav.php/addressbooks/{username}/default/
```

### Features

- Sync Dolibarr contacts
- VCard format support
- Read-only mode available

## Authentication

Uses Dolibarr user credentials with HTTP Basic Authentication.

## Client Configuration

### Thunderbird

1. Add CalDAV calendar
2. URL: `{baseurl}/dav.php/calendars/{login}/default/`
3. Enter Dolibarr credentials

### Mobile (iOS/Android)

1. Add CalDAV/CardDAV account
2. Server: `{baseurl}/dav.php`
3. Username/password: Dolibarr credentials

## Permissions

DAV access requires:
- User login credentials
- Appropriate module permissions (agenda, contacts)
