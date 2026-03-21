<?php

namespace AllegedWizard\PhpSpreadsheets\Tests;

use AllegedWizard\PhpSpreadsheets\Spreadsheets;
use PHPUnit\Framework\TestCase;

class ReadTest extends TestCase
{
    private function fixturePath(string $filename): string
    {
        return __DIR__ . '/fixtures/' . $filename;
    }

    // ---- XLSX single sheet ----

    public function testReadXlsxColumns(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $columns = $spreadsheet->getColumns();

        $this->assertSame(['Name', 'Email', 'Age'], $columns);
    }

    public function testReadXlsxToArray(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('bob@example.com', $rows[1]['Email']);
    }

    public function testReadXlsxSingleSheetNotMultiple(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $this->assertFalse($spreadsheet->hasMultipleSheets());
    }

    // ---- XLSX multi sheet ----

    public function testReadXlsxMultipleSheets(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('multi-sheet.xlsx'));
        $this->assertTrue($spreadsheet->hasMultipleSheets());
    }

    public function testReadXlsxMultiSheetColumns(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('multi-sheet.xlsx'));
        $columns = $spreadsheet->getColumns();

        $this->assertCount(2, $columns);

        $sheetKeys = array_keys($columns);
        $this->assertStringContainsString('Users', $sheetKeys[0]);
        $this->assertStringContainsString('Orders', $sheetKeys[1]);
    }

    public function testReadXlsxMultiSheetToArray(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('multi-sheet.xlsx'));
        $data = $spreadsheet->toArray();

        $sheetKeys = array_keys($data);
        $this->assertCount(2, $sheetKeys);

        $usersKey = $sheetKeys[0];
        $ordersKey = $sheetKeys[1];

        $this->assertSame('Alice', $data[$usersKey][0]['Name']);
        $this->assertSame('alice@example.com', $data[$usersKey][0]['Email']);
        $this->assertCount(1, $data[$ordersKey]);
    }

    public function testGetSheetName(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('multi-sheet.xlsx'));
        $columns = $spreadsheet->getColumns();
        $sheetKeys = array_keys($columns);

        $this->assertSame('Users', $spreadsheet->getSheetName($sheetKeys[0]));
        $this->assertSame('Orders', $spreadsheet->getSheetName($sheetKeys[1]));
    }

    // ---- CSV ----

    public function testReadCsvToArray(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('simple.csv'));
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('25', $rows[1]['Age']);
    }

    public function testReadCsvColumns(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('simple.csv'));
        $columns = $spreadsheet->getColumns();

        $this->assertSame(['Name', 'Email', 'Age'], $columns);
    }

    // ---- Tab-delimited TXT ----

    public function testReadTabDelimitedTxt(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('tabs.txt'));
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('bob@example.com', $rows[1]['Email']);
    }

    // ---- ODS ----

    public function testReadOdsToArray(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('simple.ods'));
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    // ---- Extension override ----

    public function testExtensionOverride(): void
    {
        // Read a CSV file but pass extension explicitly
        $spreadsheet = new Spreadsheets($this->fixturePath('simple.csv'), 'read', 'csv');
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
    }

    // ---- Row count ----

    public function testGetRowCount(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $counts = $spreadsheet->getRowCount();

        // Row count includes header row, so 3 total
        $sheetKey = array_key_first($counts);
        $this->assertSame(3, $counts[$sheetKey]);
    }

    // ---- Invalid path ----

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(\Exception::class);
        new Spreadsheets('/nonexistent/file.xlsx');
    }
}
