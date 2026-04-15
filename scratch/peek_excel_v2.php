<?php
@ini_set('memory_limit', '1024M');
require_once __DIR__ . '/../vendor/autoload.php';
$filePath = __DIR__ . '/../uploads/20260410_125029_35c2de71.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($filePath);
$sheet = null;
foreach ($spreadsheet->getWorksheetIterator() as $s) {
    if (strtoupper($s->getTitle()) === 'BASE') { $sheet = $s; break; }
}
if (!$sheet) $sheet = $spreadsheet->getActiveSheet();

echo "Sheet: " . $sheet->getTitle() . "\n";
$headerRow = $sheet->rangeToArray("A1:Z1", null, true, false)[0] ?? [];
echo "Header: " . implode(" | ", $headerRow) . "\n";
$firstDataRow = $sheet->rangeToArray("A2:Z2", null, true, false)[0] ?? [];
echo "First Row: " . implode(" | ", $firstDataRow) . "\n";
