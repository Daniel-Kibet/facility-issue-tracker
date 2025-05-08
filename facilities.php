<?php
// DB connection
$host = 'localhost';
$db = 'facility_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$search = $_GET['search'] ?? '';
$facility_filter = $_GET['facility_name'] ?? '';
$code_filter = $_GET['facility_code'] ?? '';

$query = "SELECT * FROM facilities WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR county LIKE ? OR level LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($facility_filter)) {
    $query .= " AND name = ?";
    $params[] = $facility_filter;
}
if (!empty($code_filter)) {
    $query .= " AND mfl_code = ?";
    $params[] = $code_filter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$facilities = $stmt->fetchAll();

$all_facilities = $pdo->query("SELECT DISTINCT name FROM facilities")->fetchAll();
$codes = $pdo->query("SELECT DISTINCT mfl_code FROM facilities")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Facility Management</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 40px;
        }
        h2 {
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .filter-form, .import-form {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 25px;
        }
        input[type="text"], select, input[type="file"] {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            flex: 1;
            min-width: 180px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 14px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .view-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .view-btn:hover {
            background-color: #218838;
        }
        .no-results {
            text-align: center;
            color: #dc3545;
            font-weight: bold;
            margin-top: 20px;
        }
        .success-message {
            background-color: #28a745;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .back-btn {
            background-color: #17a2b8;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Back Button -->
    <a href="index.php" class="back-btn">Back</a>

    <h2>Facility Management</h2>

    <!-- Display Success Message if Upload Successful -->
    <?php if (isset($_GET['upload']) && $_GET['upload'] == 'success'): ?>
        <div class="success-message">
            File uploaded and data processed successfully!
        </div>
    <?php endif; ?>

    <!-- Import Form -->
    <form class="import-form" action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="import_file" accept=".csv, .xlsx, .xls" required>
        <button type="submit">Upload</button>
    </form>

    <!-- Filter/Search Form -->
    <form class="filter-form" method="GET">
        <select name="facility_name">
            <option value="">All Facilities</option>
            <?php foreach ($all_facilities as $fac): ?>
                <option value="<?= htmlspecialchars($fac['name']) ?>" <?= $facility_filter == $fac['name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($fac['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="facility_code">
            <option value="">All Codes</option>
            <?php foreach ($codes as $code): ?>
                <option value="<?= htmlspecialchars($code['mfl_code']) ?>" <?= $code_filter == $code['mfl_code'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($code['mfl_code']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="search" placeholder="Search by name, county or level..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Apply Filters</button>
    </form>

    <?php if (count($facilities) === 0): ?>
        <p class="no-results">No facilities found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Facility Name</th>
                    <th>MFL Code</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facilities as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['mfl_code']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td> <!-- Display Status -->
                        <td><a class="view-btn" href="edit_facility.php?id=<?= $row['id'] ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
