<?php
$conn = new mysqli("localhost", "root", "", "facility_tracker");

// Total issues this week
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM issues WHERE request_date >= '$startOfWeek'");
$total = $totalQuery->fetch_assoc()['total'];

// Breakdown by issue type (excluding empty and null)
$typeQuery = $conn->query("SELECT issue_type, COUNT(*) AS count FROM issues WHERE request_date >= '$startOfWeek' AND issue_type != '' AND issue_type IS NOT NULL GROUP BY issue_type");
$types = [];
while ($row = $typeQuery->fetch_assoc()) {
    $types[] = $row;
}

// Status distribution (excluding empty and null)
$statusQuery = $conn->query("SELECT IFNULL(status, 'Not Assigned') AS status, COUNT(*) AS count FROM issues WHERE request_date >= '$startOfWeek' AND status != '' AND status IS NOT NULL GROUP BY status");
$statuses = [];
while ($row = $statusQuery->fetch_assoc()) {
    $statuses[] = $row;
}

// Issues by day (for the current week)
$dailyQuery = $conn->query("
    SELECT DATE(request_date) AS date, COUNT(*) AS count
    FROM issues
    WHERE request_date >= '$startOfWeek'
    GROUP BY DATE(request_date)
");
$daily = [];
while ($row = $dailyQuery->fetch_assoc()) {
    $daily[] = $row;
}

echo json_encode([
    'total' => $total,
    'types' => $types,
    'statuses' => $statuses,
    'daily' => $daily
]);
?>
