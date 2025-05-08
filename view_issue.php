<?php 
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'Pending';
    $action_taken = $_POST['action_taken'] ?? '';

    $stmt = $pdo->prepare("UPDATE issues SET status = ?, action_taken = ? WHERE id = ?");
    $stmt->execute([$status, $action_taken, $id]);

    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT i.*, f.name as facility_name, f.mfl_code FROM issues i LEFT JOIN facilities f ON i.facility_id = f.id WHERE i.id = ?");
$stmt->execute([$id]);
$issue = $stmt->fetch();

if (!$issue) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Issue #<?= htmlspecialchars($issue['id']) ?></title>
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #3f8a44;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background: url('images/view.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        h1, h2 {
            margin-top: 0;
            color: #333;
        }
        .issue-details {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .issue-details p {
            margin: 10px 0;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        select, textarea {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .btn {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: var(--secondary-color);
        }
        .btn-link {
            margin-left: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Issue #<?= htmlspecialchars($issue['id']) ?></h1>

        <div class="issue-details">
            <p><strong>Facility:</strong> <?= htmlspecialchars($issue['facility_name']) ?> (<?= htmlspecialchars($issue['mfl_code']) ?>)</p>
            <p><strong>Codes:</strong> <?= htmlspecialchars($issue['codes']) ?></p>
            <p><strong>Issue Type:</strong> <?= htmlspecialchars($issue['issue_type']) ?></p>
            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($issue['description'])) ?></p>
            <p><strong>Requester:</strong> <?= htmlspecialchars($issue['requester']) ?> (<?= htmlspecialchars($issue['requester_email']) ?>)</p>
            <p><strong>Request Date:</strong> <?= htmlspecialchars($issue['request_date']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($issue['status']) ?></p>
            <a href="edit_issue.php?id=<?= $issue['id'] ?>" class="btn">Edit</a>
        </div>

        <h2>Update Issue</h2>
        <form method="POST">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="Pending" <?= $issue['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $issue['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Resolved" <?= $issue['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>

            <div class="form-group">
                <label for="action_taken">Action Taken</label>
                <textarea id="action_taken" name="action_taken" rows="5"><?= htmlspecialchars($issue['action_taken']) ?></textarea>
            </div>

            <button type="submit" class="btn">Update Issue</button>
            <a href="index.php" class="btn-link">← Back to List</a>
        </form>
    </div>
</body>
</html>
