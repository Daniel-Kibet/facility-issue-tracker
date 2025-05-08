<?php
// Include DB connection
require 'db.php';

// Set headers for UTF-8 with BOM (to display special characters correctly in Excel)
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=facility_issues_export.csv');

// Output BOM for Excel UTF-8 compatibility
echo "\xEF\xBB\xBF";

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ['ID', 'Facility Name', 'MFL Code', 'Issue Type', 'Description', 'Email', 'Date Reported', 'Status']);

// Fetch issues with all details
try {
    $stmt = $pdo->query("SELECT id, facility,codes, issue_type, description, requester_email, request_date, status FROM issues");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['facility'],
            $row['codes'],
            $row['issue_type'],
            $row['description'],
            $row['requester_email'],
            $row['request_date'],
            $row['status']
        ]);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

fclose($output);
exit;
?>
