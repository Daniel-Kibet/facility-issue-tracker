<?php
// Get the JSON data sent from the frontend
$filters = json_decode(file_get_contents('php://input'), true);

// Construct the SQL query based on the filters
$query = "SELECT * FROM issues WHERE 1"; // '1' means always true, so filters will be added

// Search filter
if (!empty($filters['search'])) {
    $search = $filters['search'];
    $query .= " AND (issue_type LIKE '%$search%' OR description LIKE '%$search%' OR requester LIKE '%$search%')";
}

// Date range filter
if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
    $startDate = $filters['startDate'];
    $endDate = $filters['endDate'];
    $query .= " AND request_date BETWEEN '$startDate' AND '$endDate'";
}

// Facility filter
if (!empty($filters['facility'])) {
    $facility = $filters['facility'];
    $query .= " AND facility_name = '$facility'";
}

// Status filter
if (!empty($filters['status'])) {
    $status = $filters['status'];
    $query .= " AND status = '$status'";
}

// Assigned User filter
if (!empty($filters['assignedUser'])) {
    $assignedUser = $filters['assignedUser'];
    $query .= " AND assigned_user = '$assignedUser'"; // Make sure this column exists in your database
}

// Execute the query
$conn = new mysqli("localhost", "root", "", "facility_tracker");
$result = $conn->query($query);

// Fetch and return the filtered results
$issues = [];
while ($row = $result->fetch_assoc()) {
    $issues[] = $row;
}

// Return filtered issues as JSON
echo json_encode($issues);
?>
