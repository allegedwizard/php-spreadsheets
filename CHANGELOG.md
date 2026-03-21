# Changelog

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
