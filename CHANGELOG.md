# Changelog

## v1.0.1

- Expose temp folder override via `Spreadsheets` constructor (4th arg) and `setTempFolder()`
- Wired through to OpenSpout XLSX reader, OpenSpout XLSX/ODS writer, and PhpSpreadsheet reader (via `TMPDIR`/`TMP`/`TEMP` env vars, since PhpSpreadsheet has no public API)
- Auto-creates the temp folder if it doesn't exist

## v1.0.0

- Initial stable release
- Read XLSX, XLS, CSV, TXT, and ODS files via OpenSpout with PhpSpreadsheet fallback
- Write XLSX, CSV, and ODS files via OpenSpout
- Column limiting with wildcard (`prefix**`) support
- File extension override for mismatched file types
- Auto-detect tab vs comma delimiter for CSV/TXT files
- Multi-sheet read/write support
- Column model for styled XLSX output (width, type: string/number/currency/date/boolean)
- Configurable header formatting: `setFreezeHeader()` and `setBoldHeader()`
- Optimized write mode for pre-structured column/row data
- Static `arrayToCsv()` utility via League\Csv
- PHPUnit test suite
