<?php

namespace AllegedWizard\PhpSpreadsheets\Writers;

use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
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
                $writer = new \OpenSpout\Writer\XLSX\Writer;
                break;
            case 'csv':
                $writer = new \OpenSpout\Writer\CSV\Writer;
                break;
            case 'ods':
                $writer = new \OpenSpout\Writer\ODS\Writer;
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
            $writer->addRow(Row::fromValues(array_values($row)));
        }
    }

    protected function _writeNormalXlsx(AbstractWriter $writer, array $records, string $sheetName, int $sheetIndex): void
    {
        if (empty($sheetIndex)) {
            $sheet = $writer->getCurrentSheet();
        } else {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
        }

        $headerColumns = array_keys($records[0]);

        $sheet->setName($sheetName);
        $this->setColumnWidths($sheet, $headerColumns);

        if ($this->freezeHeader) {
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

    protected function _writeOptimizedXlsx(AbstractWriter $writer, array $records, string $sheetName, int $sheetIndex): void
    {
        if (empty($sheetIndex)) {
            $sheet = $writer->getCurrentSheet();
        } else {
            $sheet = $writer->addNewSheetAndMakeItCurrent();
        }

        $headerColumns = $records['columns'];

        $sheet->setName($sheetName);
        $this->setColumnWidths($sheet, $headerColumns);

        if ($this->freezeHeader) {
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

            if (in_array($this->extension, ['xls', 'xlsx'])) {
                $this->_writeNormalXlsx($writer, $records, $sheetName, $sheetIndex);
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

            if (in_array($this->extension, ['xls', 'xlsx'])) {
                $this->_writeOptimizedXlsx($writer, $records, $sheetName, $sheetIndex);
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

        $output = [];

        foreach ($row as $column => $value) {
            $value = trim($value);
            $modelColumn = strtolower(trim($column));
            $dataType = $this->model[$modelColumn]['type'] ?? 'string';

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
}
