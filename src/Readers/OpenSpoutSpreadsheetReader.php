<?php

namespace AllegedWizard\PhpSpreadsheets\Readers;

class OpenSpoutSpreadsheetReader extends AbstractSpreadsheetReader
{
    /**
     * @return \OpenSpout\Reader\CSV\Reader|\OpenSpout\Reader\ODS\Reader|\OpenSpout\Reader\XLSX\Reader
     * @throws \OpenSpout\Common\Exception\IOException
     */
    public function getReader(): mixed
    {
        if (!empty($this->reader)) {
            return $this->reader;
        }

        $path = strtolower($this->path);
        $reader = null;

        switch ($this->extension) {
            case 'xls':
            case 'xlsx':
                $options = new \OpenSpout\Reader\XLSX\Options();
                if ($this->tempFolder !== null) {
                    $options->setTempFolder($this->tempFolder);
                }
                $reader = new \OpenSpout\Reader\XLSX\Reader($options);
                break;
            case 'csv':
            case 'txt':
                $options = new \OpenSpout\Reader\CSV\Options();
                $options->FIELD_DELIMITER = $this->detectDelimiter($this->path);
                $reader = new \OpenSpout\Reader\CSV\Reader($options);
                break;
            case 'ods':
                $reader = new \OpenSpout\Reader\ODS\Reader;
                break;
        }

        if (empty($reader)) {
            throw new \Exception('Unable to resolve spreadsheet reader, supported types: .xls, .xlsx, .csv, .txt, .ods');
        }

        $reader->open($path);

        $this->reader = $reader;

        return $reader;
    }

    public function getColumns(): array
    {
        if (!empty($this->columns)) {
            return $this->columns;
        }

        $columnsBySheet = [];

        foreach ($this->getReader()->getSheetIterator() as $sheetIndex => $sheet) {
            $sheetKey = $this->getSheetKey($sheet->getName(), $sheetIndex);
            $sheetColumns = [];

            foreach ($sheet->getRowIterator() as $i => $row) {
                if (1 !== $i) {
                    continue;
                }
                foreach ($row->getCells() as $cell) {
                    $sheetColumns[] = $cell->getValue();
                }
            }

            $columnsBySheet[$sheetKey] = $sheetColumns;
        }

        $this->columns = $columnsBySheet;

        return $this->columns;
    }

    public function toArray(): array
    {
        $rowsBySheet = [];
        $columns = $this->getColumns();

        foreach ($this->getReader()->getSheetIterator() as $sheetIndex => $sheet) {
            $sheetOutput = [];
            $sheetKey = $this->getSheetKey($sheet->getName(), $sheetIndex);
            $sheetColumns = $columns[$sheetKey];

            foreach ($sheet->getRowIterator() as $i => $row) {
                if (1 === $i) {
                    continue;
                }

                $rowOutput = [];
                foreach ($row->getCells() as $cell) {
                    $rowOutput[] = $cell->getValue();
                }

                // Column count mismatch — attempt to trim trailing empties.
                if (count($sheetColumns) !== count($rowOutput)) {
                    $columnCount = count($sheetColumns) - 1;
                    foreach ($rowOutput as $index => $value) {
                        if ($index > $columnCount && empty($value)) {
                            unset($rowOutput[$index]);
                        }
                    }
                }

                if (count($sheetColumns) !== count($rowOutput)) {
                    $this->unparsable[] = $rowOutput;
                } else {
                    $sheetOutput[] = $this->maybeLimitColumns(
                        array_combine($sheetColumns, $rowOutput)
                    );
                }
            }

            $rowsBySheet[$sheetKey] = $sheetOutput;
        }

        return $rowsBySheet;
    }

    public function getRowCount(): array
    {
        $rowsBySheet = [];

        foreach ($this->getReader()->getSheetIterator() as $sheetIndex => $sheet) {
            $rowCount = 0;
            $sheetKey = $this->getSheetKey($sheet->getName(), $sheetIndex);

            foreach ($sheet->getRowIterator() as $i => $row) {
                $rowCount = $i;
            }

            $rowsBySheet[$sheetKey] = $rowCount;
        }

        $this->getReader()->close();

        return $rowsBySheet;
    }

    /**
     * Detect the field delimiter by inspecting the first line of the file:
     * whichever of comma, semicolon, tab or pipe occurs most often outside
     * quoted text wins, comma on ties. Excel exports CSV with semicolons in
     * many locales and tab-separated .txt exports are common; a comma-only
     * reader collapses those files into a single column.
     */
    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = $handle ? fgets($handle) : false;
        if ($handle) {
            fclose($handle);
        }

        if (!$firstLine) {
            return ',';
        }

        // Ignore delimiters inside double-quoted fields ("Smith, John").
        $unquoted = preg_replace('/"[^"]*"/', '', $firstLine) ?? $firstLine;

        $best = ',';
        $bestCount = substr_count($unquoted, ',');

        foreach ([';', "\t", '|'] as $candidate) {
            $count = substr_count($unquoted, $candidate);
            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }
}
