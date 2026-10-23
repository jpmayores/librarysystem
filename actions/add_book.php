<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');

    $imageUrl = '';

    // 1. Suriin kung may in-upload na file
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cover_image']['tmp_name'];
        $fileName = $_FILES['cover_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            // Gamitin ang DOCUMENT_ROOT para sa tamang absolute path sa Docker/Apache container
            $uploadFileDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0775, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // I-save ang relative path sa database para madaling ma-display sa web pages
                $imageUrl = 'uploads/' . $newFileName;

            }
        }
    }

    // 2. Kung walang file, kunin mula sa URL input
    if (empty($imageUrl) && !empty($_POST['cover_url'])) {
        $imageUrl = trim($_POST['cover_url']);
    }

    try {
        // Ginamit na natin ang 'image_url' para tumugma sa iyong database table
        $sql = "INSERT INTO books (title, author, category, status, image_url) VALUES (:title, :author, :category, :status, :imageUrl)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':category' => $category,
            ':status' => $status,
            ':imageUrl' => $imageUrl
        ]);

        header("Location: ../admin.php");
        exit();

    } catch (PDOException $e) {
        echo "<h3 style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
        exit();
    }
}
$action = "Add Book";
$details = "Nagdagdag ng bagong libro: " . $bookTitle; // Palitan batay sa variable ng pangalan ng libro mo
$stmtLog = $conn->prepare("INSERT INTO audit_logs (action, details, created_at) VALUES (?, ?, NOW())");
$stmtLog->execute([$action, $details]);