<?php

namespace AllegedWizard\PhpSpreadsheets\Writers;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriter;
use OpenSpout\Writer\Common\AbstractOptions;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;

class OpenSpoutSpreadsheetWriter extends AbstractSpreadsheetWriter
{
    protected array $model;
    protected bool $freezeHeader = true;
    protected bool $boldHeader = true;

    public function setFreezeHeader(bool $freeze): self
    {
        $this->freezeHeader = $freeze;
        return $this;
    }

    public function setBoldHeader(bool $bold): self
    {
        $this->boldHeader = $bold;
        return $this;
    }

    protected function getWriter(): AbstractWriter
    {
        switch ($this->extension) {
            case 'xls':
            case 'xlsx':
                $options = new \OpenSpout\Writer\XLSX\Options();
                if ($this->tempFolder !== null) {
                    $options->setTempFolder($this->tempFolder);
                }
                $writer = new \OpenSpout\Writer\XLSX\Writer($options);
                break;
            case 'csv':
                $writer = new \OpenSpout\Writer\CSV\Writer;
                break;
            case 'ods':
                $options = new \OpenSpout\Writer\ODS\Options();
                if ($this->tempFolder !== null) {
                    $options->setTempFolder($this->tempFolder);
                }
                $writer = new \OpenSpout\Writer\ODS\Writer($options);
                break;
        }

        if (!isset($writer)) {
            throw new \Exception('Unable to initialize spreadsheet writer!');
        }

        return $writer;
    }

    /**
     * @param  array  $records  An array of key => value rows to write
     * @param  array  $model  An optional model definition [column_name => [width => ..., type => ...]]
     * @param  bool|string  $multipleSheets  Default false. String overrides 'Sheet1'. True treats $records as array of sheets.
     * @param  bool  $optimizedColumns  If true, $records must contain 'columns' and 'rows' arrays.
     */
    public function write(
        array $records = [],
        array $model = [],
        $multipleSheets = false,
        bool $optimizedColumns = false
    ): void {
        $this->model = $this->cleanModel($model);

        $this->validateContext($multipleSheets, count($records));

        $writer = $this->getWriter();
        $writer->openToFile($this->path);

        if (false === $multipleSheets || is_string($multipleSheets)) {
            $sheetName = is_string($multipleSheets) ? $multipleSheets : 'Sheet1';
            $sheetsRecords = [$sheetName => $records];
        } else {
            $sheetsRecords = $records;
        }

        if (!$optimizedColumns) {
            $this->writeNormal($writer, $sheetsRecords);
        } else {
            $this->writeOptimized($writer, $sheetsRecords);
        }

        $writer->close();
    }

    protected function validateContext($multipleSheets, int $recordCount): void
    {
        if ('csv' === $this->extension && $multipleSheets) {
            throw new \Exception('Multiple sheets not allowed for CSV.');
        }

        $excelRowLimit = 1048576;
        if (
            in_array($this->extension, ['xls', 'xlsx'], true) &&
            $recordCount >= $excelRowLimit
        ) {
            throw new \Exception(
                sprintf(
                    'Excel rows have a limit of %s; %s records passed.',
                    $excelRowLimit,
                    $recordCount
                )
            );
        }
    }

    protected function _writeNormalCsv(AbstractWriter $writer, array $records): void
    {
        $headerColumns = array_keys($records[0]);
        $writer->addRow(Row::fromValues($headerColumns));

        foreach ($records as $row) {
            // Normal CSV mode bypasses the model, but link values may still
            // arrive as arrays -- flatten those to a string before writing.
            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    $row[$key] = $this->flattenLinkForCsv($value);
                }
            }
            $writer->addRow(Row::fromValues(array_values($row)));
        }
    }

    protected function _writeNormalSheet(AbstractWriter $writer, array $records, string $sheetName, int $sheetIndex): void
    {
        if (empty($sheetIndex)) {
            $sheet = $writer->getCurrentSheet();
        } else {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
        }

        $headerColumns = array_keys($records[0]);

        $sheet->setName($sheetName);
        $this->setColumnWidths($sheet, $headerColumns);

        // SheetView (freeze row) is XLSX-only; ODS ignores it.
        if ($this->freezeHeader && in_array($this->extension, ['xls', 'xlsx'], true)) {
            $sheetView = new SheetView();
            $sheetView->setFreezeRow(2);
            $sheet->setSheetView($sheetView);
        }

        $writer->addRow($this->getFormattedHeader($headerColumns));

        foreach ($records as $row) {
            $writer->addRow($this->getRow($row));
        }
    }

    protected function _writeOptimizedCsv(AbstractWriter $writer, array $records): void
    {
        $headerColumns = $records['columns'];
        $writer->addRow(Row::fromValues($headerColumns));

        foreach ($records['rows'] as $row) {
            $row = $this->rebuildOptimizedRow($row, $headerColumns);
            $writer->addRow($this->getRow($row));
        }
    }

    protected function _writeOptimizedSheet(AbstractWriter $writer, array $records, string $sheetName, int $sheetIndex): void
    {
        if (empty($sheetIndex)) {
            $sheet = $writer->getCurrentSheet();
        } else {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
        }

        $headerColumns = $records['columns'];

        $sheet->setName($sheetName);
        $this->setColumnWidths($sheet, $headerColumns);

        // SheetView (freeze row) is XLSX-only; ODS ignores it.
        if ($this->freezeHeader && in_array($this->extension, ['xls', 'xlsx'], true)) {
            $sheetView = new SheetView();
            $sheetView->setFreezeRow(2);
            $sheet->setSheetView($sheetView);
        }

        $writer->addRow($this->getFormattedHeader($headerColumns));

        foreach ($records['rows'] as $row) {
            $row = $this->rebuildOptimizedRow($row, $headerColumns);
            $writer->addRow($this->getRow($row));
        }
    }

    protected function rebuildOptimizedRow(array $row, array $headerColumns): array
    {
        foreach ($headerColumns as $i => $headerColumn) {
            if (!isset($row[$i])) {
                $row[$i] = '';
            }
        }

        ksort($row);

        if (count($headerColumns) !== count($row)) {
            throw new \Exception('Column/row count mismatch in optimized write.');
        }

        return array_combine($headerColumns, $row);
    }

    protected function writeNormal(AbstractWriter $writer, array $sheetsRecords): void
    {
        $sheetIndex = 0;

        foreach ($sheetsRecords as $sheetName => $records) {
            if ('csv' === $this->extension) {
                $this->_writeNormalCsv($writer, $records);
            }

            if (in_array($this->extension, ['xls', 'xlsx', 'ods'], true)) {
                $this->_writeNormalSheet($writer, $records, $sheetName, $sheetIndex);
            }

            $sheetIndex++;
        }
    }

    protected function writeOptimized(AbstractWriter $writer, array $sheetsRecords): void
    {
        $sheetIndex = 0;

        foreach ($sheetsRecords as $sheetName => $records) {
            if ('csv' === $this->extension) {
                $this->_writeOptimizedCsv($writer, $records);
            }

            if (in_array($this->extension, ['xls', 'xlsx', 'ods'], true)) {
                $this->_writeOptimizedSheet($writer, $records, $sheetName, $sheetIndex);
            }

            $sheetIndex++;
        }
    }

    protected function cleanModel(array $model): array
    {
        $cleanModel = [];
        foreach ($model as $key => $value) {
            $cleanModel[trim(strtolower($key))] = $value;
        }
        return $cleanModel;
    }

    protected function setColumnWidths(AbstractOptions|Sheet $optionsOrSheet, array $columns): void
    {
        foreach ($columns as $i => $column) {
            $modelColumn = strtolower(trim($column));
            $width = $this->model[$modelColumn]['width'] ?? 0;

            if (empty($width)) {
                $width = max(10, min(50, strlen($column) * 1.4));
            }

            $optionsOrSheet->setColumnWidth($width, $i + 1);
        }
    }

    protected function getFormattedHeader(array $columns): Row
    {
        if ($this->boldHeader) {
            $headerStyle = new Style();
            $headerStyle->setFontBold();
            return Row::fromValues($columns, $headerStyle);
        }

        return Row::fromValues($columns);
    }

    protected function getRow(array $row): Row
    {
        if (empty($this->model)) {
            return Row::fromValues(array_values($row));
        }

        $stringStyle = new Style;

        $stringRight = new Style;
        $stringRight->setCellAlignment('right');

        $dateStyle = new Style;
        $dateStyle->setCellAlignment('right');
        $dateStyle->setFormat('yyyy-mm-dd');

        $numericStyle = new Style;
        $numericStyle->setCellAlignment('right');

        $currencyStyle = new Style;
        $currencyStyle->setFormat('$#,##0.00');

        $linkStyle = new Style;
        $linkStyle->setFontColor('0563C1');
        $linkStyle->setFontUnderline();

        $output = [];

        foreach ($row as $column => $value) {
            $modelColumn = strtolower(trim($column));
            $dataType = $this->model[$modelColumn]['type'] ?? 'string';

            // Handle link before the trim, because link values may be arrays.
            if ('link' === $dataType) {
                $output[] = $this->getLinkCell($value, $linkStyle, $stringStyle);
                continue;
            }

            $value = trim($value);

            if (empty($value) && '0' !== $value) {
                $output[] = new EmptyCell($value, $stringStyle);
                continue;
            }

            switch ($dataType) {
                case 'date':
                    try {
                        $dateTime = new \DateTime($value);
                        $output[] = new DateTimeCell($dateTime, $dateStyle);
                    } catch (\Exception $e) {
                        $output[] = new StringCell($value, $stringRight);
                    }
                    break;

                case 'number':
                    if (empty($value)) {
                        $output[] = new EmptyCell($value, $stringStyle);
                    } else {
                        $output[] = new NumericCell($value, $numericStyle);
                    }
                    break;

                case 'currency':
                    $output[] = new NumericCell((float) $value, $currencyStyle);
                    break;

                case 'boolean':
                    $value = $value ? 'TRUE' : '';
                    $output[] = new StringCell($value, $stringStyle);
                    break;

                case 'string':
                default:
                    $output[] = new StringCell($value, $stringStyle);
                    break;
            }
        }

        return new Row($output);
    }

    /**
     * Build a cell for a `link` typed value. XLSX/XLS get a clickable
     * `=HYPERLINK(...)` formula; CSV/ODS get a plain "label (url)" string.
     *
     * Accepts either a string URL or `['url' => ..., 'label' => ...]`.
     */
    protected function getLinkCell($value, Style $linkStyle, Style $emptyStyle): Cell
    {
        [$url, $label] = $this->extractLink($value);

        if ('' === $url && '' === $label) {
            return new EmptyCell('', $emptyStyle);
        }

        if ('' !== $url && in_array($this->extension, ['xls', 'xlsx'], true)) {
            $formula = sprintf(
                '=HYPERLINK(%s,%s)',
                $this->escapeFormulaArg($url),
                $this->escapeFormulaArg($label)
            );
            return new FormulaCell($formula, $linkStyle, $label);
        }

        return new StringCell($this->flattenLinkForCsv($value), $emptyStyle);
    }

    /**
     * Pull (url, label) out of a link value, accepting either a string URL or
     * an associative array. Label defaults to the URL when missing.
     *
     * @return array{0: string, 1: string} Tuple of [url, label].
     */
    protected function extractLink($value): array
    {
        if (is_array($value)) {
            $url = trim((string) ($value['url'] ?? ''));
            $label = trim((string) ($value['label'] ?? ''));
        } else {
            $url = trim((string) $value);
            $label = '';
        }

        if ('' === $label) {
            $label = $url;
        }

        return [$url, $label];
    }

    /**
     * Flatten a link value to a plain string for non-formula targets (CSV/ODS).
     */
    protected function flattenLinkForCsv($value): string
    {
        [$url, $label] = $this->extractLink($value);

        if ('' === $url && '' === $label) {
            return '';
        }
        if ('' === $url || $url === $label) {
            return $label;
        }
        return sprintf('%s (%s)', $label, $url);
    }

    /**
     * Wrap a value in double quotes and double-up any embedded quotes so it's
     * a valid Excel formula string argument.
     */
    protected function escapeFormulaArg(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }
}
