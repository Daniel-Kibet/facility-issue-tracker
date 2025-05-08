<?php
require 'vendor/autoload.php';
require 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$sheet->fromArray(['ID', 'Facility Name', 'MFL Code', 'Issue Type', 'Description', 'Email', 'Date Reported', 'Status'], NULL, 'A1');

// Fetch data
$stmt = $pdo->query("SELECT id, facility, mfl_code, issue_type, description, email, request_date, status FROM issues");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Insert data starting from row 2
$rowNum = 2;
foreach ($data as $row) {
    $sheet->fromArray(array_values($row), NULL, 'A' . $rowNum++);
}

// Output the Excel file
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="facility_issues.xlsx"');
$writer->save('php://output');
exit;
?>
