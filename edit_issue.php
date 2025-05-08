<?php
// Include the Composer autoloader
require 'vendor/autoload.php';

// Database connection setup
$host = 'localhost';
$db   = 'facility_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Fetch the issue by ID
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Invalid request: ID not provided.";
    exit;
}

$query = "SELECT i.*, f.name AS facility_name FROM issues i
          LEFT JOIN facilities f ON i.facility_id = f.id
          WHERE i.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$issue = $stmt->fetch();
if (!$issue) {
    echo "Issue not found!";
    exit;
}

// Fetch all facilities for dropdown
$facilityStmt = $pdo->query("SELECT id, name FROM facilities ORDER BY name ASC");
$facilities = $facilityStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_id = $_POST['facility_id'];
    $issue_type = $_POST['issue_type'];
    $codes = $_POST['codes'];
    $requester_email = $_POST['requester_email'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $update_query = "UPDATE issues 
        SET facility_id = ?, codes = ?, issue_type = ?, requester_email = ?, description = ?, status = ? 
        WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([
        $facility_id,
        $codes,
        $issue_type,
        $requester_email,
        $description,
        $status,
        $id
    ]);

    header("Location: index.php");
    exit;
}
?>

<!-- HTML Form to Edit Issue -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Issue</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 30px auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }

        h2 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<h2>Edit Facility Issue</h2>

<form method="post" action="">
    <label for="facility_id">Facility Name:</label>
    <select name="facility_id" required>
        <option value="">-- Select Facility --</option>
        <?php foreach ($facilities as $facility): ?>
            <option value="<?= $facility['id'] ?>" <?= $facility['id'] == $issue['facility_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($facility['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="issue_type">Issue Type:</label>
    <input type="text" name="issue_type" value="<?= htmlspecialchars($issue['issue_type']) ?>" required>

    <label for="codes">Codes:</label>
    <input type="text" name="codes" value="<?= htmlspecialchars($issue['codes'] ?? '') ?>" required>

    <label for="requester_email">Requester Email:</label>
    <input type="email" name="requester_email" value="<?= htmlspecialchars($issue['requester_email']) ?>" required>

    <label for="description">Description:</label>
    <textarea name="description" rows="4" required><?= htmlspecialchars($issue['description'] ?? '') ?></textarea>

    <label for="status">Status:</label>
    <select name="status" required>
        <option value="Pending" <?= $issue['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Resolved" <?= $issue['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
    </select>

    <button type="submit">Update Facility</button>
</form>

</body>
</html>
