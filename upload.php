<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['csv', 'xlsx', 'xls', 'json'];
        $max_file_size = 10 * 1024 * 1024;

        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            if ($file['size'] <= $max_file_size) {
                $file_name = preg_replace("/[^a-zA-Z0-9.]/", "_", basename($file['name']));
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    if ($file_extension === 'csv') {
                        processCsvFile($target_path);
                    } elseif (in_array($file_extension, ['xlsx', 'xls'])) {
                        processExcelFile($target_path, $file_extension);
                    } elseif ($file_extension === 'json') {
                        processJsonFile($target_path);
                    }
                } else {
                    echo "Error uploading the file. Please try again!";
                }
            } else {
                echo "File is too large. Max size is " . ($max_file_size / 1024 / 1024) . " MB.";
            }
        } else {
            echo "Invalid file type! Only CSV, XLSX, XLS, and JSON files are allowed.";
        }
    } else {
        echo "Error uploading file! Error code: " . $file['error'];
    }
} else {
    echo "No file uploaded!";
}

function processCsvFile($file_path) {
    if (($handle = fopen($file_path, 'r')) !== false) {
        fgetcsv($handle); // Skip header

        try {
            $pdo = new PDO("mysql:host=localhost;dbname=facility_tracker;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $stmt = $pdo->prepare("INSERT INTO facilities (name, mfl_code, level) VALUES (?, ?, ?)");

            while (($row = fgetcsv($handle)) !== false) {
                $name = trim($row[0] ?? '');
                $mfl_code = trim($row[1] ?? '');
                $level = trim($row[2] ?? '');

                if ($name !== '') {
                    $stmt->execute([$name, $mfl_code, $level]);
                }
            }

            fclose($handle);
            header("Location: facilities.php?upload=success");
            exit;
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage();
        }
    }
}

function processExcelFile($file_path, $file_extension) {
    require_once 'vendor/autoload.php';

    $reader = ($file_extension === 'xlsx') ? new PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new PhpOffice\PhpSpreadsheet\Reader\Xls();
    $spreadsheet = $reader->load($file_path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=facility_tracker;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare("INSERT INTO facilities (name, mfl_code, level) VALUES (?, ?, ?)");

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip header

            $name = trim($row[0] ?? '');
            $mfl_code = trim($row[1] ?? '');
            $level = trim($row[2] ?? '');

            if ($name !== '') {
                $stmt->execute([$name, $mfl_code, $level]);
            }
        }

        header("Location: facilities.php?upload=success");
        exit;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}

function processJsonFile($file_path) {
    $json_data = file_get_contents($file_path);
    $data = json_decode($json_data, true);

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=facility_tracker;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare("INSERT INTO facilities (name, mfl_code, level) VALUES (?, ?, ?)");

        foreach ($data as $row) {
            $name = trim($row['name'] ?? '');
            $mfl_code = trim($row['mfl_code'] ?? '');
            $level = trim($row['level'] ?? '');

            if ($name !== '') {
                $stmt->execute([$name, $mfl_code, $level]);
            }
        }

        header("Location: facilities.php?upload=success");
        exit;
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
?>
