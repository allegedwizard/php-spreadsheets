<?php

namespace AllegedWizard\PhpSpreadsheets\Readers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PhpSpreadsheetsSpreadsheetReader extends AbstractSpreadsheetReader
{
    public function getReader(): mixed
    {
        if (!empty($this->reader)) {
            return $this->reader;
        }

        $reader = IOFactory::createReader(ucfirst($this->extension));
        $worksheets = $reader->load($this->path);
        $this->reader = $worksheets;
        return $worksheets;
    }

    public function getColumns(): array
    {
        if (!empty($this->columns)) {
            return $this->columns;
        }

        $columnsBySheet = [];

        foreach ($this->getReader()->getWorksheetIterator() as $sheetIndex => $sheet) {
            $sheetKey = $this->getSheetKey($sheet->getTitle(), $sheetIndex);
            $sheetColumns = [];
            $rowIterator = $sheet->getRowIterator();

            foreach ($rowIterator as $i => $row) {
                if (1 === $i) {
                    $columnIterator = $row->getCellIterator();
                    foreach ($columnIterator as $cell) {
                        $sheetColumns[] = $cell->getFormattedValue();
                    }
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

        foreach ($this->getReader()->getWorksheetIterator() as $sheetIndex => $sheet) {
            $sheetKey = $this->getSheetKey($sheet->getTitle(), $sheetIndex);
            $sheetColumns = $columns[$sheetKey];
            $sheetOutput = [];
            $rowIterator = $sheet->getRowIterator();

            foreach ($rowIterator as $i => $row) {
                if (1 === $i) {
                    continue;
                }

                $rowOutput = [];
                $columnIterator = $row->getCellIterator();
                foreach ($columnIterator as $cell) {
                    $rowOutput[] = $cell->getFormattedValue();
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
}
