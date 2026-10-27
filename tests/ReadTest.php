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

    // ---- Semicolon-delimited CSV (Excel in many locales) ----

    public function testReadSemicolonCsvDetectsDelimiter(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('semicolons.csv'));

        $this->assertSame(['Name', 'Email', 'Age'], $spreadsheet->getColumns());

        $rows = $spreadsheet->toArray();
        $this->assertCount(2, $rows);
        // A quoted comma inside a field must not sway the detection.
        $this->assertSame('Smith, Alice', $rows[0]['Name']);
        $this->assertSame('bob@example.com', $rows[1]['Email']);
    }

    // ---- Rows with fewer / more cells than the header ----

    public function testReadCsvPadsShortRows(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('short-rows.csv'));
        $rows = $spreadsheet->toArray();

        // Alice, Bob, Carol, Dave; Eve has data beyond the header.
        $this->assertCount(4, $rows);
        $this->assertSame(['Name' => 'Bob', 'Email' => 'bob@example.com', 'Age' => ''], $rows[1]);
        $this->assertSame(['Name' => 'Carol', 'Email' => '', 'Age' => ''], $rows[2]);
        // Empty trailing cells beyond the header are dropped.
        $this->assertSame(['Name' => 'Dave', 'Email' => 'dave@example.com', 'Age' => '40'], $rows[3]);
    }

    public function testRowsWithDataBeyondHeaderAreUnparsable(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('short-rows.csv'));
        $spreadsheet->toArray();

        $this->assertSame([['Eve', 'eve@example.com', '22', 'extra']], $spreadsheet->getUnparsable());
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

    // ---- Link fixtures ----

    public function testWithLinksXlsxFixtureContainsHyperlinkFormula(): void
    {
        $path = $this->fixturePath('with-links.xlsx');
        $this->assertFileExists($path);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertNotFalse($sheetXml);
        $this->assertStringContainsString(
            'HYPERLINK(&quot;https://example.com&quot;,&quot;Example&quot;)',
            $sheetXml
        );
        $this->assertStringContainsString(
            'HYPERLINK(&quot;https://openspout.org&quot;,&quot;https://openspout.org&quot;)',
            $sheetXml
        );
        $this->assertStringContainsString(
            'HYPERLINK(&quot;https://github.com/openspout/openspout&quot;,&quot;OpenSpout on GitHub&quot;)',
            $sheetXml
        );
    }

    public function testReadXlsxHyperlinkCellReturnsRawFormula(): void
    {
        // Pin current behavior: our reader returns the raw formula string
        // for HYPERLINK cells. If we ever add link-aware read parsing this
        // assertion will need to update.
        $rows = (new Spreadsheets($this->fixturePath('with-links.xlsx')))->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('=HYPERLINK("https://example.com","Example")', $rows[0]['Site']);
        $this->assertSame('=HYPERLINK("https://openspout.org","https://openspout.org")', $rows[1]['Site']);
        $this->assertSame('=HYPERLINK("https://github.com/openspout/openspout","OpenSpout on GitHub")', $rows[2]['Site']);
    }

    public function testReadCsvLinkFallbackReturnsFlattenedString(): void
    {
        $rows = (new Spreadsheets($this->fixturePath('with-links.csv')))->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Example (https://example.com)', $rows[0]['Site']);
        $this->assertSame('https://openspout.org', $rows[1]['Site']);
        $this->assertSame('OpenSpout on GitHub (https://github.com/openspout/openspout)', $rows[2]['Site']);
    }

    public function testReadOdsLinkFallbackReturnsFlattenedString(): void
    {
        $rows = (new Spreadsheets($this->fixturePath('with-links.ods')))->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Example (https://example.com)', $rows[0]['Site']);
        $this->assertSame('https://openspout.org', $rows[1]['Site']);
        $this->assertSame('OpenSpout on GitHub (https://github.com/openspout/openspout)', $rows[2]['Site']);
    }

    // ---- Invalid path ----

    public function testInvalidPathThrowsException(): void
    {
        $this->expectException(\Exception::class);
        new Spreadsheets('/nonexistent/file.xlsx');
    }
}
