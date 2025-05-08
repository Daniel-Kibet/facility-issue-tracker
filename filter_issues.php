<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "facility_tracker");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Sanitize input
$status = isset($input['status']) ? $conn->real_escape_string($input['status']) : '';
$assignedUser = isset($input['assignedUser']) ? $conn->real_escape_string($input['assignedUser']) : '';

// Build query
$sql = "SELECT * FROM issues WHERE 1";

if (!empty($status)) {
    $sql .= " AND status = '$status'";
}

if (!empty($assignedUser)) {
    $sql .= " AND assigned_to = '$assignedUser'";
}

// Execute query
$result = $conn->query($sql);

$issues = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
}

// Output JSON
header('Content-Type: application/json');
echo json_encode($issues);

$conn->close();
?>
