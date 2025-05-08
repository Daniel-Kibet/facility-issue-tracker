<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM issues WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?deleted=1");
        exit;
    } catch (Exception $e) {
        echo "Error deleting issue: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
    exit;
}
