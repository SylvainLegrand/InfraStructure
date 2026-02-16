# htdocs/exports - Exports Module

Data export functionality.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Export |
| Permission Key | export |

## Directory Structure

```
exports/
├── index.php             # Export wizard
├── export.php            # Process export
├── class/
│   └── export.class.php
└── admin/                # Module settings
```

## Export Process

1. Select data type (products, invoices, etc.)
2. Choose fields to export
3. Apply filters
4. Select format
5. Generate export

## Export Formats

| Format | Description |
|--------|-------------|
| csv | Comma-separated values |
| tsv | Tab-separated values |
| excel | Excel workbook |
| excel2007 | Excel 2007+ (xlsx) |

## Creating Export Profiles

Export profiles define reusable field selections:

```php
$export = new Export($db);
$export->build_sql_from_filters($datatoimport, $sqlquery);
```

## Module Integration

Modules define exportable datasets in `modMyModule.class.php`:

```php
$this->export_code[$r] = 'mymodule_myobject';
$this->export_label[$r] = 'MyObjects';
$this->export_fields_array[$r] = array(
    's.rowid' => 'Id',
    's.ref' => 'Ref',
    's.label' => 'Label'
);
```

## Permissions

- `$user->hasRight('export', 'lire')` - Create exports
