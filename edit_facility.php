<?php
// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=facility_tracker;charset=utf8mb4", "root", "");

// Get facility ID from URL
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid ID");
}

// Fetch facility details
$stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = ?");
$stmt->execute([$id]);
$facility = $stmt->fetch();

if (!$facility) {
    die("Facility not found");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $mfl_code = $_POST['mfl_code'];

    $update = $pdo->prepare("UPDATE facilities SET name = ?, mfl_code = ? WHERE id = ?");
    $update->execute([$name, $mfl_code, $id]);

    echo "<script>alert('Facility updated successfully'); window.location.href='facilities.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Facility</title>
    <style>
        body {
            font-family: Arial;
            background: #f7f9fc;
            padding: 30px;
        }
        .form-container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        label, input {
            display: block;
            width: 100%;
            margin-bottom: 15px;
        }
        input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            background: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Facility</h2>
    <form method="POST">
        <label>Facility Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($facility['name']) ?>" required>

        <label>MFL Code:</label>
        <input type="text" name="mfl_code" value="<?= htmlspecialchars($facility['mfl_code']) ?>" required>

        <button type="submit">Update Facility</button>
    </form>
</div>

</body>
</html>
