<?php

namespace AllegedWizard\PhpSpreadsheets\Tests;

use AllegedWizard\PhpSpreadsheets\Spreadsheets;
use PHPUnit\Framework\TestCase;

class ArrayToCsvTest extends TestCase
{
    public function testBasicArrayToCsv(): void
    {
        $records = [
            ['Name' => 'Alice', 'Email' => 'alice@example.com'],
            ['Name' => 'Bob', 'Email' => 'bob@example.com'],
        ];

        $csv = Spreadsheets::arrayToCsv($records);
        $lines = explode("\n", $csv);

        $this->assertSame('Name,Email', $lines[0]);
        $this->assertSame('Alice,alice@example.com', $lines[1]);
        $this->assertSame('Bob,bob@example.com', $lines[2]);
    }

    public function testArrayToCsvWithArrayValues(): void
    {
        $records = [
            ['Name' => 'Alice', 'Tags' => ['admin', 'user']],
        ];

        $csv = Spreadsheets::arrayToCsv($records);
        $lines = explode("\n", $csv);

        $this->assertSame('Name,Tags', $lines[0]);
        $this->assertStringContainsString('admin;user', $lines[1]);
    }

    public function testArrayToCsvWithEmptyArrayValue(): void
    {
        $records = [
            ['Name' => 'Alice', 'Tags' => [';']],
        ];

        $csv = Spreadsheets::arrayToCsv($records);
        $lines = explode("\n", $csv);

        // A single semicolon array should collapse to empty string
        $this->assertSame('Name,Tags', $lines[0]);
    }
}
