<?php

namespace AllegedWizard\PhpSpreadsheets\Tests;

use AllegedWizard\PhpSpreadsheets\Spreadsheets;
use PHPUnit\Framework\TestCase;

class WriteTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/php-spreadsheets-test-' . uniqid();
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->outputDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->outputDir);
    }

    private function outputPath(string $filename): string
    {
        return $this->outputDir . '/' . $filename;
    }

    // ---- XLSX write + read-back ----

    public function testWriteXlsxAndReadBack(): void
    {
        $path = $this->outputPath('output.xlsx');
        $records = [
            ['Name' => 'Alice', 'Email' => 'alice@example.com'],
            ['Name' => 'Bob', 'Email' => 'bob@example.com'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('bob@example.com', $rows[1]['Email']);
    }

    public function testWriteXlsxWithModel(): void
    {
        $path = $this->outputPath('modeled.xlsx');
        $records = [
            ['Name' => 'Alice', 'Amount' => '100.50'],
        ];
        $model = [
            'Name' => ['width' => 30, 'type' => 'string'],
            'Amount' => ['width' => 15, 'type' => 'currency'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model);

        $this->assertFileExists($path);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    public function testWriteXlsxCustomSheetName(): void
    {
        $path = $this->outputPath('named-sheet.xlsx');
        $records = [
            ['Name' => 'Alice'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, [], 'MySheet');

        $reader = new Spreadsheets($path);
        $columns = $reader->getColumns();
        $this->assertSame(['Name'], $columns);
    }

    public function testWriteXlsxMultipleSheets(): void
    {
        $path = $this->outputPath('multi.xlsx');
        $records = [
            'Sheet A' => [['Col1' => 'A1']],
            'Sheet B' => [['Col2' => 'B1']],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, [], true);

        $reader = new Spreadsheets($path);
        $this->assertTrue($reader->hasMultipleSheets());

        $data = $reader->toArray();
        $this->assertCount(2, $data);
    }

    public function testWriteXlsxOptimized(): void
    {
        $path = $this->outputPath('optimized.xlsx');
        $records = [
            'columns' => ['Name', 'Email'],
            'rows' => [
                ['Alice', 'alice@example.com'],
                ['Bob', 'bob@example.com'],
            ],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, [], false, true);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();
        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    // ---- CSV write + read-back ----

    public function testWriteCsvAndReadBack(): void
    {
        $path = $this->outputPath('output.csv');
        $records = [
            ['Name' => 'Alice', 'Email' => 'alice@example.com'],
            ['Name' => 'Bob', 'Email' => 'bob@example.com'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    public function testWriteCsvMultipleSheetsThrows(): void
    {
        $path = $this->outputPath('multi.csv');
        $records = [
            'Sheet A' => [['Col1' => 'A1']],
            'Sheet B' => [['Col2' => 'B1']],
        ];

        $writer = new Spreadsheets($path, 'write');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Multiple sheets not allowed for CSV');
        $writer->write($records, [], true);
    }

    // ---- Header config ----

    public function testWriteXlsxNoFreezeHeader(): void
    {
        $path = $this->outputPath('no-freeze.xlsx');
        $records = [['Name' => 'Alice']];

        $writer = new Spreadsheets($path, 'write');
        $writer->setFreezeHeader(false);
        $writer->write($records);

        $this->assertFileExists($path);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    public function testWriteXlsxNoBoldHeader(): void
    {
        $path = $this->outputPath('no-bold.xlsx');
        $records = [['Name' => 'Alice']];

        $writer = new Spreadsheets($path, 'write');
        $writer->setBoldHeader(false);
        $writer->write($records);

        $this->assertFileExists($path);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    public function testSetFreezeHeaderReturnsSelf(): void
    {
        $path = $this->outputPath('chain-freeze.xlsx');
        $spreadsheet = new Spreadsheets($path, 'write');
        $this->assertSame($spreadsheet, $spreadsheet->setFreezeHeader(false));
    }

    public function testSetBoldHeaderReturnsSelf(): void
    {
        $path = $this->outputPath('chain-bold.xlsx');
        $spreadsheet = new Spreadsheets($path, 'write');
        $this->assertSame($spreadsheet, $spreadsheet->setBoldHeader(false));
    }

    // ---- ODS write ----

    public function testWriteOdsAndReadBack(): void
    {
        $path = $this->outputPath('output.ods');
        $records = [
            ['Name' => 'Alice', 'Email' => 'alice@example.com'],
            ['Name' => 'Bob', 'Email' => 'bob@example.com'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records);

        $this->assertFileExists($path);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
        $this->assertSame('bob@example.com', $rows[1]['Email']);
    }

    public function testWriteOdsOptimizedAndReadBack(): void
    {
        $path = $this->outputPath('optimized.ods');
        $records = [
            'columns' => ['Name', 'Email'],
            'rows' => [
                ['Alice', 'alice@example.com'],
                ['Bob', 'bob@example.com'],
            ],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, [], false, true);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Alice', $rows[0]['Name']);
    }

    // ---- Link type ----

    public function testWriteXlsxLinkTypeEmbedsHyperlinkFormula(): void
    {
        $path = $this->outputPath('links.xlsx');
        $records = [
            ['Name' => 'Alice', 'Site' => ['url' => 'https://example.com', 'label' => 'Example']],
            ['Name' => 'Bob', 'Site' => 'https://openspout.org'],
            ['Name' => 'Carol', 'Site' => ''],
        ];
        $model = [
            'Name' => ['type' => 'string'],
            'Site' => ['type' => 'link'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model);

        $this->assertFileExists($path);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertNotFalse($sheetXml);
        // OpenSpout escapes quotes in formulas as XML entities.
        $this->assertStringContainsString('HYPERLINK(&quot;https://example.com&quot;,&quot;Example&quot;)', $sheetXml);
        $this->assertStringContainsString('HYPERLINK(&quot;https://openspout.org&quot;,&quot;https://openspout.org&quot;)', $sheetXml);
    }

    public function testWriteCsvOptimizedLinkTypeFallsBackToText(): void
    {
        $path = $this->outputPath('links.csv');
        $records = [
            'columns' => ['Name', 'Site'],
            'rows' => [
                ['Alice', ['url' => 'https://example.com', 'label' => 'Example']],
                ['Bob', 'https://openspout.org'],
                ['Carol', ''],
            ],
        ];
        $model = [
            'Name' => ['type' => 'string'],
            'Site' => ['type' => 'link'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model, false, true);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('Example (https://example.com)', $rows[0]['Site']);
        $this->assertSame('https://openspout.org', $rows[1]['Site']);
        $this->assertSame('', $rows[2]['Site']);
    }

    public function testWriteCsvNormalLinkValueFlattensArray(): void
    {
        $path = $this->outputPath('links-normal.csv');
        $records = [
            ['Name' => 'Alice', 'Site' => ['url' => 'https://example.com', 'label' => 'Example']],
            ['Name' => 'Bob', 'Site' => 'https://openspout.org'],
        ];

        // No model passed; normal CSV mode should still flatten array values.
        $writer = new Spreadsheets($path, 'write');
        $writer->write($records);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('Example (https://example.com)', $rows[0]['Site']);
        $this->assertSame('https://openspout.org', $rows[1]['Site']);
    }

    public function testWriteXlsxLinkTypeEscapesEmbeddedQuotes(): void
    {
        $path = $this->outputPath('links-quotes.xlsx');
        $records = [
            ['Site' => ['url' => 'https://example.com/?q="hi"', 'label' => 'Say "hi"']],
        ];
        $model = ['Site' => ['type' => 'link']];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model);

        $zip = new \ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        // Excel formula escapes embedded quotes by doubling them; the XML
        // layer then encodes each quote as &quot;.
        $this->assertStringContainsString(
            'HYPERLINK(&quot;https://example.com/?q=&quot;&quot;hi&quot;&quot;&quot;,&quot;Say &quot;&quot;hi&quot;&quot;&quot;)',
            $sheetXml
        );
    }

    // ---- Image type ----

    public function testWriteXlsxImageTypeEmbedsImageFormula(): void
    {
        $path = $this->outputPath('images.xlsx');
        $records = [
            ['Name' => 'Cat', 'Pic' => 'https://example.com/cat.jpg'],
            ['Name' => 'Dog', 'Pic' => ['url' => 'https://example.com/dog.png', 'alt' => 'A good dog']],
            ['Name' => 'Empty', 'Pic' => ''],
        ];
        $model = [
            'Name' => ['type' => 'string'],
            'Pic'  => ['type' => 'image'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model);

        $zip = new \ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('IMAGE(&quot;https://example.com/cat.jpg&quot;)', $sheetXml);
        $this->assertStringContainsString('IMAGE(&quot;https://example.com/dog.png&quot;,&quot;A good dog&quot;)', $sheetXml);
    }

    public function testWriteCsvImageTypeFallsBackToUrl(): void
    {
        $path = $this->outputPath('images.csv');
        $records = [
            'columns' => ['Name', 'Pic'],
            'rows' => [
                ['Cat', 'https://example.com/cat.jpg'],
                ['Dog', ['url' => 'https://example.com/dog.png', 'alt' => 'A good dog']],
                ['Empty', ''],
            ],
        ];
        $model = [
            'Name' => ['type' => 'string'],
            'Pic'  => ['type' => 'image'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model, false, true);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(3, $rows);
        $this->assertSame('https://example.com/cat.jpg', $rows[0]['Pic']);
        $this->assertSame('A good dog (https://example.com/dog.png)', $rows[1]['Pic']);
        $this->assertSame('', $rows[2]['Pic']);
    }

    // ---- Formula type ----

    public function testWriteXlsxFormulaTypeEmbedsFormula(): void
    {
        $path = $this->outputPath('formulas.xlsx');
        $records = [
            ['Label' => 'Sum', 'Value' => '=SUM(1,2,3)'],
            // Without leading '=' -- writer should add it.
            ['Label' => 'Product', 'Value' => 'PRODUCT(2,4)'],
            // Array form with computed fallback.
            ['Label' => 'Concat', 'Value' => ['formula' => '=CONCAT("a","b")', 'computed' => 'ab']],
        ];
        $model = [
            'Label' => ['type' => 'string'],
            'Value' => ['type' => 'formula'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model);

        $zip = new \ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('<f>SUM(1,2,3)</f>', $sheetXml);
        $this->assertStringContainsString('<f>PRODUCT(2,4)</f>', $sheetXml);
        $this->assertStringContainsString('CONCAT(&quot;a&quot;,&quot;b&quot;)', $sheetXml);
    }

    public function testWriteCsvFormulaTypeFallsBackToComputedOrText(): void
    {
        $path = $this->outputPath('formulas.csv');
        $records = [
            'columns' => ['Label', 'Value'],
            'rows' => [
                ['Sum', '=SUM(1,2,3)'],
                ['Concat', ['formula' => '=CONCAT("a","b")', 'computed' => 'ab']],
            ],
        ];
        $model = [
            'Label' => ['type' => 'string'],
            'Value' => ['type' => 'formula'],
        ];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records, $model, false, true);

        $reader = new Spreadsheets($path);
        $rows = $reader->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame('=SUM(1,2,3)', $rows[0]['Value']);
        $this->assertSame('ab', $rows[1]['Value']);
    }

    // ---- Creates directories ----

    public function testWriteCreatesDirectoryIfNeeded(): void
    {
        $nestedDir = $this->outputDir . '/nested/deep';
        $path = $nestedDir . '/output.xlsx';
        $records = [['Name' => 'Alice']];

        $writer = new Spreadsheets($path, 'write');
        $writer->write($records);

        $this->assertFileExists($path);

        // Cleanup nested dirs
        unlink($path);
        rmdir($nestedDir);
        rmdir($this->outputDir . '/nested');
    }
}
