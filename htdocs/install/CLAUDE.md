# htdocs/install - Installation & SQL Schemas

Installation wizard and database schema files.

## Directory Structure

| Subdirectory | Purpose |
|--------------|---------|
| mysql/ | MySQL/MariaDB SQL files |
| pgsql/ | PostgreSQL SQL files (auto-generated) |
| doctemplates/ | Document templates installed by default |

## SQL File Types

| Pattern | Purpose |
|---------|---------|
| `llx_*.sql` | Table creation |
| `llx_*.key.sql` | Indexes and foreign keys |
| `data_*.sql` | Initial/seed data |
| `update_*.sql` | Migration files |

## Schema Conventions

See `/dolibarr-sql-schema` skill for table templates, required fields, indexes, and migration patterns.

## Installation Steps

1. `step1.php` - License agreement
2. `step2.php` - Database configuration
3. `step4.php` - Database creation
4. `step5.php` - Admin user creation
