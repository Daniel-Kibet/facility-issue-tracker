<?php
require 'vendor/autoload.php';
require 'db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    if (!empty($file)) {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach ($rows as $index => $row) {
            if ($index == 0) continue; // Skip header row

            $name = $row[0];
            $mfl_code = $row[1];
            $county = $row[2];
            $level = $row[3];

            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO facilities (name, mfl_code, county, level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $mfl_code, $county, $level]);
        }

        echo "Import successful!";
    } else {
        echo "No file uploaded.";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit" name="import">Import Excel</button>
</form>
