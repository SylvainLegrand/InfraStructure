# htdocs/imports - Imports Module

Data import functionality.

## Module Info

| Property | Value |
|----------|-------|
| Main Class | Import |
| Permission Key | import |

## Directory Structure

```
imports/
├── index.php             # Import wizard
├── import.php            # Process import
├── class/
│   └── import.class.php
└── admin/                # Module settings
```

## Import Process

1. Select data type (products, invoices, etc.)
2. Upload file (CSV, Excel)
3. Map columns to fields
4. Preview data
5. Execute import

## Import Formats

| Format | Description |
|--------|-------------|
| csv | Comma-separated values |
| excel | Excel workbook |
| excel2007 | Excel 2007+ (xlsx) |

## Field Mapping

Map source file columns to target fields:

```php
$import = new Import($db);
$import->import_file($datatoimport, $filename, $fieldmapping);
```

## Module Integration

Modules define importable datasets in `modMyModule.class.php`:

```php
$this->import_code[$r] = 'mymodule_myobject';
$this->import_label[$r] = 'MyObjects';
$this->import_entities_array[$r] = array();
$this->import_tables_array[$r] = array('m' => MAIN_DB_PREFIX.'mymodule_myobject');
$this->import_fields_array[$r] = array(
    'm.ref' => 'Ref*',
    'm.label' => 'Label'
);
```

## Validation

Imports validate:
- Required fields (marked with *)
- Data types
- Foreign key references
- Unique constraints

## Permissions

- `$user->hasRight('import', 'run')` - Execute imports
