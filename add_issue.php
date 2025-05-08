<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facility_name = trim($_POST['facility_name'] ?? '');
    $codes = $_POST['codes'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $requester = $_POST['requester'] ?? '';
    $requester_email = $_POST['requester_email'] ?? '';

    // Check if a facility name is provided
    if (!empty($facility_name)) {
        // Insert new facility into the facilities table
        $stmt = $pdo->prepare("INSERT INTO facilities (name) VALUES (?)");
        $stmt->execute([$facility_name]);
        $facility_id = $pdo->lastInsertId(); // Get the new facility ID
    } else {
        echo "<div style='color: red;'>Please enter a facility name.</div>";
        exit;
    }

    // Insert issue with the facility name
    $stmt = $pdo->prepare("INSERT INTO issues (
        facility_id, facility, codes, issue_type, description, requester, requester_email
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $facility_id,
        $facility_name,
        $codes,
        $issue_type,
        $description,
        $requester,
        $requester_email
    ]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Issue</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/issues.jpeg') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        .btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        h1 {
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Issue</h1>
        
        <form method="POST">
            <div class="form-group">
                <label for="facility_name">Facility Name:</label>
                <input type="text" id="facility_name" name="facility_name" placeholder="Enter facility name" required>
            </div>

            <div class="form-group">
                <label for="codes">Codes:</label>
                <input type="text" id="codes" name="codes" required>
            </div>

            <div class="form-group">
                <label for="issue_type">Issue Type:</label>
                <input type="text" id="issue_type" name="issue_type" required>
            </div>  
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="requester">Requester Name:</label>
                <input type="text" id="requester" name="requester" required>
            </div>
            
            <div class="form-group">
                <label for="requester_email">Requester Email:</label>
                <input type="email" id="requester_email" name="requester_email" required>
            </div>
            
            <button type="submit" class="btn">Submit Issue</button>
            <a href="index.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>
