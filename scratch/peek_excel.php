<?php
require_once __DIR__ . '/../vendor/autoload.php';
$filePath = __DIR__ . '/../uploads/20260410_121510_e2f4eb99.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$headerRow = $sheet->rangeToArray("A1:Z1", null, true, false)[0] ?? [];
echo "Header: " . implode(" | ", $headerRow) . "\n";
$firstDataRow = $sheet->rangeToArray("A2:Z2", null, true, false)[0] ?? [];
echo "First Row: " . implode(" | ", $firstDataRow) . "\n";
