<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? 'Available';

    if (!empty($bookId)) {
        try {
            $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, category = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $author, $category, $status, $bookId]);
        } catch (Exception $e) {
            // Handle error kung kinakailangan
        }
    }
}

header("Location: ../admin.php?view=inventory");
exit();
?>