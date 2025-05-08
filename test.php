<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Excel Import Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    echo "POST data received:\n";
    print_r($_POST);
    echo "\n\nFILE data received:\n";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color:green'>File upload successful!</p>";
        echo "<p>File name: " . htmlspecialchars($_FILES['excel_file']['name']) . "</p>";
        echo "<p>File type: " . htmlspecialchars($_FILES['excel_file']['type']) . "</p>";
        echo "<p>File size: " . htmlspecialchars($_FILES['excel_file']['size']) . " bytes</p>";
    } else {
        echo "<p style='color:red'>File upload failed or no file received</p>";
        if (isset($_FILES['excel_file']['error'])) {
            echo "<p>Error code: " . $_FILES['excel_file']['error'] . "</p>";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="excel_file" required>
    <button type="submit">Test Upload</button>
</form>