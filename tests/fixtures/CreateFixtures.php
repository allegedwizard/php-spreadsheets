<?php

/**
 * Run this script once to generate test fixture files.
 * Usage: php tests/fixtures/CreateFixtures.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use AllegedWizard\PhpSpreadsheets\Spreadsheets;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\ODS\Writer as OdsWriter;

$fixturesDir = __DIR__;

// --- single-sheet.xlsx ---
$writer = new XlsxWriter();
$writer->openToFile($fixturesDir . '/single-sheet.xlsx');
$writer->addRow(Row::fromValues(['Name', 'Email', 'Age']));
$writer->addRow(Row::fromValues(['Alice', 'alice@example.com', 30]));
$writer->addRow(Row::fromValues(['Bob', 'bob@example.com', 25]));
$writer->close();

// --- multi-sheet.xlsx ---
$writer = new XlsxWriter();
$writer->openToFile($fixturesDir . '/multi-sheet.xlsx');
$sheet1 = $writer->getCurrentSheet();
$sheet1->setName('Users');
$writer->addRow(Row::fromValues(['Name', 'Email']));
$writer->addRow(Row::fromValues(['Alice', 'alice@example.com']));

$sheet2 = $writer->addNewSheetAndMakeItCurrent();
$sheet2->setName('Orders');
$writer->addRow(Row::fromValues(['OrderId', 'Total']));
$writer->addRow(Row::fromValues([1, 99.99]));
$writer->close();

// --- simple.csv ---
file_put_contents($fixturesDir . '/simple.csv', "Name,Email,Age\nAlice,alice@example.com,30\nBob,bob@example.com,25\n");

// --- tabs.txt ---
file_put_contents($fixturesDir . '/tabs.txt', "Name\tEmail\tAge\nAlice\talice@example.com\t30\nBob\tbob@example.com\t25\n");

// --- simple.ods ---
$writer = new OdsWriter();
$writer->openToFile($fixturesDir . '/simple.ods');
$writer->addRow(Row::fromValues(['Name', 'Email', 'Age']));
$writer->addRow(Row::fromValues(['Alice', 'alice@example.com', 30]));
$writer->addRow(Row::fromValues(['Bob', 'bob@example.com', 25]));
$writer->close();

// --- with-links.xlsx / .ods / .csv ---
// Generated via the Spreadsheets writer using the `link` model type so you
// can open these by hand and verify the hyperlinks work in Excel/LibreOffice.
$linkRecords = [
    ['Name' => 'Alice', 'Site' => ['url' => 'https://example.com', 'label' => 'Example']],
    ['Name' => 'Bob', 'Site' => 'https://openspout.org'],
    ['Name' => 'Carol', 'Site' => ['url' => 'https://github.com/openspout/openspout', 'label' => 'OpenSpout on GitHub']],
];
$linkModel = [
    'Name' => ['width' => 20, 'type' => 'string'],
    'Site' => ['width' => 40, 'type' => 'link'],
];

foreach (['xlsx', 'ods', 'csv'] as $ext) {
    $path = $fixturesDir . '/with-links.' . $ext;
    (new Spreadsheets($path, 'write'))->write($linkRecords, $linkModel);
}

echo "Fixtures created successfully.\n";
