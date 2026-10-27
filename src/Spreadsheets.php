<?php

namespace AllegedWizard\PhpSpreadsheets;

use AllegedWizard\PhpSpreadsheets\Readers\AbstractSpreadsheetReader;
use AllegedWizard\PhpSpreadsheets\Readers\OpenSpoutSpreadsheetReader;
use AllegedWizard\PhpSpreadsheets\Readers\PhpSpreadsheetsSpreadsheetReader;
use AllegedWizard\PhpSpreadsheets\Writers\AbstractSpreadsheetWriter;
use AllegedWizard\PhpSpreadsheets\Writers\OpenSpoutSpreadsheetWriter;
use League\Csv\Writer;

/**
 * Main class for interfacing/reading/writing spreadsheet data.
 * For reading: prefers OpenSpout for low memory footprint, with fallback to PHPSpreadsheet.
 * For writing, currently only OpenSpout is supported.
 */
class Spreadsheets
{
    protected string $path;
    protected string $extension;
    protected ?string $tempFolder = null;
    protected AbstractSpreadsheetReader $reader;
    protected AbstractSpreadsheetWriter $writer;

    /**
     * @param  string  $path  Absolute path to file to read or write.
     * @param  string  $context  Accepts 'read' or 'write'.
     * @param  string  $extension  Override file extension.
     * @param  string|null  $tempFolder  Override the temp folder used by the
     *         underlying OpenSpout/PhpSpreadsheet engine. Created if missing.
     */
    public function __construct(string $path, string $context = 'read', string $extension = '', ?string $tempFolder = null)
    {
        $this->path = $path;
        $this->extension = $extension;
        $this->tempFolder = $tempFolder;

        if ('read' === $context) {
            $this->getReader();
        }

        if ('write' === $context) {
            $this->getWriter();
        }
    }

    /**
     * Override the temp folder used by the underlying engine. For the read
     * context this must be set via the constructor since the reader is opened
     * eagerly; calling this method after construction has no effect on an
     * already-opened reader.
     */
    public function setTempFolder(?string $tempFolder): self
    {
        $this->tempFolder = $tempFolder;
        if (isset($this->writer)) {
            $this->writer->setTempFolder($tempFolder);
        }
        if (isset($this->reader)) {
            $this->reader->setTempFolder($tempFolder);
        }
        return $this;
    }

    public function getTempFolder(): ?string
    {
        return $this->tempFolder;
    }

    public function setFreezeHeader(bool $freeze): self
    {
        $writer = $this->getWriter();
        if ($writer instanceof OpenSpoutSpreadsheetWriter) {
            $writer->setFreezeHeader($freeze);
        }
        return $this;
    }

    public function setBoldHeader(bool $bold): self
    {
        $writer = $this->getWriter();
        if ($writer instanceof OpenSpoutSpreadsheetWriter) {
            $writer->setBoldHeader($bold);
        }
        return $this;
    }

    public function write(array $records = [], array $model = [], $multipleSheets = false, bool $optimizedColumns = false): void
    {
        $this->getWriter()->write($records, $model, $multipleSheets, $optimizedColumns);
    }

    /**
     * Limit the columns returned by the reader.
     *
     * @param  array  $columns  Column keys to include. Wildcard supported with {prefix}** syntax.
     */
    public function setLimitColumns(array $columns): self
    {
        $this->getReader()->setLimitColumns($columns);
        return $this;
    }

    protected function getReader(): AbstractSpreadsheetReader
    {
        if (!empty($this->reader)) {
            return $this->reader;
        }

        // Attempt to parse via OpenSpout, fall back to PhpSpreadsheet.
        try {
            $reader = new OpenSpoutSpreadsheetReader($this->path);
            if ($this->extension) {
                $reader->setExtension($this->extension);
            }
            $reader->setTempFolder($this->tempFolder);
            $reader->getReader();
        } catch (\Exception $e) {
            $reader = new PhpSpreadsheetsSpreadsheetReader($this->path);
            if ($this->extension) {
                $reader->setExtension($this->extension);
            }
            $reader->setTempFolder($this->tempFolder);
            $reader->getReader();
        }

        $this->reader = $reader;

        return $reader;
    }

    protected function getWriter(): AbstractSpreadsheetWriter
    {
        if (!empty($this->writer)) {
            return $this->writer;
        }

        $writer = new OpenSpoutSpreadsheetWriter($this->path);
        if ($this->extension) {
            $writer->setExtension($this->extension);
        }
        $writer->setTempFolder($this->tempFolder);

        $this->writer = $writer;

        return $writer;
    }

    public function getColumns(): array
    {
        $columns = $this->getReader()->getColumns();

        if ($this->hasMultipleSheets()) {
            return $columns;
        }

        return array_pop($columns);
    }

    public function toArray(): array
    {
        $values = $this->getReader()->toArray();

        if ($this->hasMultipleSheets()) {
            return $values;
        }

        return array_pop($values);
    }

    public function hasMultipleSheets(): bool
    {
        return $this->getReader()->hasMultipleSheets();
    }

    public function getSheetName(string $sheetKey): string
    {
        return $this->getReader()->getSheetName($sheetKey);
    }

    public function getRowCount(): array
    {
        return $this->getReader()->getRowCount();
    }

    /**
     * Rows toArray() left out because they hold data beyond the header's
     * columns, as lists of cell values. Filled by toArray().
     */
    public function getUnparsable(): array
    {
        return $this->getReader()->getUnparsable();
    }

    /**
     * Convert an associative array of records to a CSV string.
     *
     * @param  array  $records
     * @return string
     */
    public static function arrayToCsv(array $records): string
    {
        $arrayDelimiter = ';';
        foreach ($records as $x => $row) {
            foreach ($row as $y => $cell) {
                if (is_array($cell)) {
                    $records[$x][$y] = implode($arrayDelimiter, $cell);
                    if ($arrayDelimiter === $records[$x][$y]) {
                        $records[$x][$y] = '';
                    }
                }
            }
        }

        $csv = Writer::fromString();
        if (empty($records[0])) {
            $records = array_values($records);
        }
        $headers = array_keys($records[0]);
        $csv->insertOne($headers);
        $csv->insertAll($records);
        return trim($csv->toString());
    }
}
