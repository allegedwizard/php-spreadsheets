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
                $reader = new \OpenSpout\Reader\XLSX\Reader;
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
     * Detect the field delimiter by inspecting the first line of the file.
     */
    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine && substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            return "\t";
        }

        return ',';
    }
}
