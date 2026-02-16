---
path: htdocs/**/sql/*.sql
---

# Database Conventions

## File Structure

- `llx_tablename.sql` - Table creation
- `llx_tablename.key.sql` - Indexes and foreign keys
- `data*.sql` - Initial/seed data
- `update*.sql` - Migration files

## Table Naming

- **Prefix:** `llx_` (always required)
- **Core tables:** `llx_tablename`
- **Module tables:** `llx_modulename_objectname`

## Standard Table Template

```sql
CREATE TABLE llx_mymodule_myobject (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,
    label           VARCHAR(255),
    fk_soc          INTEGER,
    status          SMALLINT DEFAULT 0,
    date_creation   DATETIME,
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat   INTEGER,
    entity          INTEGER DEFAULT 1 NOT NULL
) ENGINE=innodb;
```

## Required Fields

- `rowid` - INTEGER AUTO_INCREMENT PRIMARY KEY
- `entity` - INTEGER DEFAULT 1 NOT NULL (multicompany ID)

## Common Fields

- `ref` - Business reference
- `label` - Display name
- `status` - SMALLINT
- `date_creation` - DATETIME
- `tms` - TIMESTAMP (auto-updated)
- `fk_user_creat`, `fk_user_modif`, `fk_user_valid` - INTEGER
- `note_private`, `note_public` - TEXT
- `import_key` - VARCHAR(14)

## Field Types

| Use | Type |
|-----|------|
| Primary/Foreign keys | INTEGER (BIGINT for large tables) |
| Status/boolean | SMALLINT |
| Amounts | DOUBLE(24,8) |
| VAT rates | DOUBLE(6,3) |
| Quantities | REAL |
| Strings | VARCHAR (not CHAR) |
| Auto dates | TIMESTAMP |
| Manual dates | DATETIME |
| Large text | TEXT/MEDIUMTEXT |

**Forbidden:** ENUM (use PHP business rules)

## Key Naming

- **Primary key:** `rowid`
- **Foreign keys:** `fk_tablename_fieldname`
- **Unique keys:** `uk_tablename_description`
- **Indexes:** `idx_tablename_fieldname`

## Indexes (*.key.sql)

```sql
ALTER TABLE llx_mymodule_myobject ADD INDEX idx_myobject_fk_soc (fk_soc);
ALTER TABLE llx_mymodule_myobject ADD CONSTRAINT fk_myobject_fk_soc
    FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
```

## Initial Data (data*.sql)

```sql
INSERT INTO llx_c_mydict (code, label, active, entity) VALUES ('CODE1', 'Label 1', 1, __ENTITY__);
```

Use `__ENTITY__` placeholder for multi-company support.

## Constraints

- **No CASCADE** in core (DELETE CASCADE, UPDATE CASCADE)
- External modules may use CASCADE only between their own tables
- Business rules enforced in PHP, not database
- No triggers or stored procedures

## Engine Requirements

- MySQL/MariaDB: InnoDB format
- Must work with MySQL `strict` mode
- PostgreSQL: Only maintain MySQL files; driver converts

## Migration Rules

- Drop obsolete tables only in version n+2
- Maintain through version n+1 for rollback safety

## Module Migration Files (update*.sql)

For module version upgrades:

```sql
-- Add column
ALTER TABLE llx_mymodule_myobject ADD COLUMN newcol varchar(60) DEFAULT NULL;

-- Modify column
ALTER TABLE llx_mymodule_myobject MODIFY COLUMN label varchar(512);

-- Add index
ALTER TABLE llx_mymodule_myobject ADD INDEX idx_myobject_status (status);
```

For core version migrations with MySQL/PostgreSQL compatibility (VMYSQL/VPGSQL comments), see `/dolibarr-sql-schema` skill.
