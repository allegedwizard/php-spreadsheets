# Changelog

## v1.0.6

- Fix CSV delimiter detection: the reader only told tabs from commas, so a semicolon-separated CSV (the default Excel export in many locales) or a pipe-separated file collapsed into a single column. The header line is now scanned for comma, semicolon, tab and pipe, ignoring delimiters inside quoted fields, and the most frequent one wins (comma on ties). Adds the `semicolons.csv` fixture and a read test.

## v1.0.5

- Fix `image` type so Excel renders the image instead of showing `@IMAGE(...)`. Post-2007 Excel functions must be stored with the `_xlfn.` prefix in the XLSX XML; without it Excel injects the implicit-intersection `@` operator and the formula doesn't evaluate. Emit `=_xlfn.IMAGE(...)` -- Excel strips the prefix for display.

## v1.0.4

- Add `image` model type. Accepts a URL string or `['url' => ..., 'alt' => ...]`. XLSX/XLS emit `=IMAGE("url"[,"alt"])` via FormulaCell (Excel 365+ only); CSV/ODS fall back to the URL (or `alt (url)` if both differ).
- Add `formula` model type for arbitrary user-supplied formulas. Accepts a formula string (with or without leading `=`) or `['formula' => ..., 'computed' => ...]`. XLSX/XLS emit a FormulaCell with the optional computed value; CSV/ODS write the computed value if given, otherwise the literal formula text.
- Refactor `getRow` to switch on type for all array-shaped values (link/image/formula) before the scalar trim.
- Generalize the normal-CSV array flattener (`flattenArrayForCsv`) to dispatch on shape.

## v1.0.3

- Add `link` model type. Values can be a URL string or `['url' => ..., 'label' => ...]`. XLSX/XLS emit a clickable `=HYPERLINK(...)` formula; CSV/ODS fall back to a plain `label (url)` string. Embedded quotes are escaped per Excel formula rules.
- Fix ODS write: `writeNormal`/`writeOptimized` previously only branched on csv/xls/xlsx, so `.ods` files were generated empty. Both normal and optimized ODS write now emit rows correctly.
- Rename internal `_writeNormalXlsx`/`_writeOptimizedXlsx` to `_writeNormalSheet`/`_writeOptimizedSheet` since they handle any multi-sheet writer; gate the XLSX-only `SheetView` freeze-row block accordingly.
- Add `with-links.xlsx`/`with-links.ods`/`with-links.csv` fixtures for manual inspection.

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
