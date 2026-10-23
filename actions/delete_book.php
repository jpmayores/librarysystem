<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['isAdminLoggedIn']) || $_SESSION['isAdminLoggedIn'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $book_id = (int)$_GET['id'];

    try {
        // Step 1: Burahin muna ang lahat ng transactions na nakaugnay sa librong ito
        $stmtTxn = $conn->prepare("DELETE FROM transactions WHERE book_id = :book_id");
        $stmtTxn->execute([':book_id' => $book_id]);

        // Step 2: Burahin na ang libro mula sa books table
        $stmtBook = $conn->prepare("DELETE FROM books WHERE id = :id");
        $stmtBook->execute([':id' => $book_id]);

        header("Location: ../admin.php?view=inventory&status=deleted");
        exit();

    } catch (PDOException $e) {
        header("Location: ../admin.php?view=inventory&error=cannot_delete");
        exit();
    }
} else {
    header("Location: ../admin.php?view=inventory");
    exit();
}
?>