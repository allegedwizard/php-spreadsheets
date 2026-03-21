# PHP Spreadsheets

This package is a wrapper utility interface for reading/writing spreadsheets, preferring OpenSpout with fallback to PhpSpreadsheet

Supports reading and writing XLSX, XLS, CSV, TXT, and ODS files.

Built on top of [OpenSpout](https://github.com/openspout/openspout) and [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet).

Created by [Alleged Wizard](https://allegedwizard.com).


## Installation

```bash
composer require allegedwizard/php-spreadsheets
```

## Reading

```php
use AllegedWizard\PhpSpreadsheets\Spreadsheets;

$spreadsheet = new Spreadsheets('/path/to/file.xlsx');

// Get all rows as associative arrays keyed by header columns
$rows = $spreadsheet->toArray();

// Get column headers
$columns = $spreadsheet->getColumns();

// Get row counts per sheet
$counts = $spreadsheet->getRowCount();

// Check for multiple sheets
if ($spreadsheet->hasMultipleSheets()) {
    $sheetName = $spreadsheet->getSheetName($sheetKey);
}
```

### Limiting Columns

```php
$spreadsheet = new Spreadsheets('/path/to/file.xlsx');

// Return only specific columns
$spreadsheet->setLimitColumns(['Name', 'Email']);

// Wildcard support: return all columns starting with "Address"
$spreadsheet->setLimitColumns(['Address**']);

$rows = $spreadsheet->toArray();
```

### Overriding File Extension

By default, the reader and writer infer the file type from the file extension. Use the third parameter to override this when the extension doesn't match the actual format.

```php
// Treat a .txt file as CSV
$spreadsheet = new Spreadsheets('/path/to/file.txt', 'read', 'csv');
```

## Writing

```php
use AllegedWizard\PhpSpreadsheets\Spreadsheets;

$records = [
    ['Name' => 'Alice', 'Email' => 'alice@example.com'],
    ['Name' => 'Bob', 'Email' => 'bob@example.com'],
];

$spreadsheet = new Spreadsheets('/path/to/output.xlsx', 'write');
$spreadsheet->write($records);
```

### XLSX Header Formatting

By default, XLSX output will freeze and bold the first row (header). To disable either behavior:

```php
$spreadsheet = new Spreadsheets('/path/to/output.xlsx', 'write');
$spreadsheet->setFreezeHeader(false);
$spreadsheet->setBoldHeader(false);
$spreadsheet->write($records);
```

### Column Model

Define per-column width and type for styled XLSX output:

```php
$model = [
    'Name'   => ['width' => 30, 'type' => 'string'],
    'Amount' => ['width' => 15, 'type' => 'currency'],
    'Date'   => ['width' => 20, 'type' => 'date'],
];

$spreadsheet->write($records, $model);
```

Supported types: `string`, `number`, `currency`, `date`, `boolean`.

### Multiple Sheets

```php
// Rename the single sheet
$spreadsheet->write($records, [], 'My Sheet');

// Write multiple sheets
$multiSheetRecords = [
    'Users'  => $userRecords,
    'Orders' => $orderRecords,
];

$spreadsheet->write($multiSheetRecords, [], true);
```

### Optimized Write Mode

When records are structured as separate `columns` and `rows` arrays:

```php
$records = [
    'columns' => ['Name', 'Email'],
    'rows' => [
        ['Alice', 'alice@example.com'],
        ['Bob', 'bob@example.com'],
    ],
];

$spreadsheet->write($records, [], false, true);
```

## CSV Utility

Convert an associative array to a CSV string:

```php
$csv = Spreadsheets::arrayToCsv($records);
```

## License

MIT
