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
