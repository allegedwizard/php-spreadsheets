<?php

namespace AllegedWizard\PhpSpreadsheets\Tests;

use AllegedWizard\PhpSpreadsheets\Spreadsheets;
use PHPUnit\Framework\TestCase;

class LimitColumnsTest extends TestCase
{
    private function fixturePath(string $filename): string
    {
        return __DIR__ . '/fixtures/' . $filename;
    }

    public function testLimitToSpecificColumns(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $spreadsheet->setLimitColumns(['Name', 'Age']);
        $rows = $spreadsheet->toArray();

        $this->assertCount(2, $rows);
        $this->assertArrayHasKey('Name', $rows[0]);
        $this->assertArrayHasKey('Age', $rows[0]);
        $this->assertArrayNotHasKey('Email', $rows[0]);
    }

    public function testLimitColumnsReturnsEmptyForMissing(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $spreadsheet->setLimitColumns(['NonExistent']);
        $rows = $spreadsheet->toArray();

        foreach ($rows as $row) {
            $this->assertEmpty($row);
        }
    }

    public function testLimitColumnsWildcard(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        // "Na**" should match "Name"
        $spreadsheet->setLimitColumns(['Na**']);
        $rows = $spreadsheet->toArray();

        $this->assertArrayHasKey('Name', $rows[0]);
        $this->assertCount(1, $rows[0]);
    }

    public function testSetLimitColumnsReturnsSelf(): void
    {
        $spreadsheet = new Spreadsheets($this->fixturePath('single-sheet.xlsx'));
        $result = $spreadsheet->setLimitColumns(['Name']);

        $this->assertSame($spreadsheet, $result);
    }
}
