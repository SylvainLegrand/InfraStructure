# REST API

RESTful API using Restler framework. Authentication via DOLAPIKEY header.

## Directory Structure

| File | Purpose |
|------|---------|
| index.php | API entry point (Restler bootstrap) |
| class/api.class.php | Base class (DolibarrApi) |
| class/api_access.class.php | Auth handler (DolibarrApiAccess) |

## Creating API Endpoints

Use `/dolibarr-api-development` skill for complete patterns and examples.

API classes are auto-discovered from `{module}/class/api_{module}.class.php`.

## Common Endpoints

- `/invoices`, `/orders`, `/thirdparties`, `/products`, `/contacts`, `/users`

## Documentation

API explorer: `{dolibarr_url}/api/index.php/explorer`
