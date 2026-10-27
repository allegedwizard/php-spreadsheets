# PHP Spreadsheets

This package is a wrapper utility interface for reading/writing spreadsheets, preferring OpenSpout with fallback to PhpSpreadsheet

Supports reading and writing XLSX, XLS, CSV, TXT, and ODS files.

Built on top of [OpenSpout](https://github.com/openspout/openspout) and [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet).

Created by [Alleged Wizard](https://allegedwizard.com).


## Installation

```bash
composer require allegedwizard/php-spreadsheets:^1.0
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

### Rows That Don't Match the Header

A row with fewer cells than the header (a CSV line that leaves off its trailing empty fields) is padded with empty strings, and empty cells beyond the header are ignored. A row that holds data beyond the header's columns is left out of `toArray()`; read those rows, as plain lists of cell values, after calling it:

```php
$rows = $spreadsheet->toArray();
$skipped = $spreadsheet->getUnparsable();
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

Supported types: `string`, `number`, `currency`, `date`, `boolean`, `link`, `image`, `formula`.

#### Link Type

The `link` type produces a clickable `=HYPERLINK(...)` formula in XLSX/XLS, and falls back to a plain `label (url)` string for CSV/ODS. Values may be either a plain URL string or an array with `url` and `label` keys:

```php
$records = [
    ['Name' => 'Alice', 'Site' => ['url' => 'https://example.com', 'label' => 'Example']],
    ['Name' => 'Bob',   'Site' => 'https://openspout.org'],
];
$model = [
    'Name' => ['type' => 'string'],
    'Site' => ['type' => 'link'],
];

$spreadsheet->write($records, $model);
```

#### Image Type

The `image` type produces an `=IMAGE("url")` formula in XLSX/XLS. CSV/ODS fall back to the URL as plain text. Values may be a plain URL string or an array with `url` and optional `alt`:

```php
$records = [
    ['Name' => 'Cat', 'Pic' => 'https://example.com/cat.jpg'],
    ['Name' => 'Dog', 'Pic' => ['url' => 'https://example.com/dog.png', 'alt' => 'A good dog']],
];
$model = [
    'Name' => ['type' => 'string'],
    'Pic'  => ['type' => 'image'],
];
```

Note: `=IMAGE()` is **Excel 365 (2023+) only**. Older Excel desktop, LibreOffice, and Google Sheets will not render the image.

#### Formula Type

The `formula` type writes an arbitrary formula. The leading `=` is optional; the writer adds it if missing. CSV/ODS write the `computed` value if provided, otherwise the literal formula text:

```php
$records = [
    ['Label' => 'Sum',    'Value' => '=SUM(1,2,3)'],
    ['Label' => 'Concat', 'Value' => ['formula' => '=CONCAT("a","b")', 'computed' => 'ab']],
];
$model = [
    'Label' => ['type' => 'string'],
    'Value' => ['type' => 'formula'],
];
```

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

## Testing

```bash
composer install
composer test
```

## License

MIT
