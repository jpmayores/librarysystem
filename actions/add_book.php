<?php
session_start();
require_once '../config/db.php';

$supabaseUrl = getenv("https://pkqtfclzadmejdfzyxxu.supabase.co"); // Palitan ng iyong Supabase URL
$supabaseKey = getenv("eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBrcXRmY2x6YWRtZWpkZnp5eHh1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzYwNTUxNTEsImV4cCI6MjA5MTYzMTE1MX0.HJjhoiv_24jQPrkEA8hkOr5VZHypjJFxADB8LHk-3Wc");   // Palitan ng iyong Supabase Key
$bucketName = "book-covers";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');

    $imageUrl = '';

 // Suriin kung may in-upload na file
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['cover_image']['tmp_name'];
    $fileName = $_FILES['cover_image']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (in_array($fileExtension, $allowedExtensions)) {
        // Gumawa ng unique filename
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Supabase Storage Upload Endpoint
        $url = "$supabaseUrl/storage/v1/object/$bucketName/$newFileName";

        $fileData = file_get_contents($fileTmpPath);

        $headers = [
            "Authorization: Bearer $supabaseKey",
            "Content-Type: " . mime_content_type($fileTmpPath),
            "x-upsert: true"
        ];

        // Gamitin ang cURL para i-upload sa Supabase Bucket
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

     $response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 || $httpCode === 201) {
            // Kunin ang Public URL para i-save sa database
            $imageUrl = "$supabaseUrl/storage/v1/object/public/$bucketName/$newFileName";
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