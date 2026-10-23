<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['isAdminLoggedIn']) || $_SESSION['isAdminLoggedIn'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $txn_id = (int)$_GET['id'];

    // Kunin ang kasalukuyang status
    $stmtCheck = $conn->prepare("SELECT status FROM transactions WHERE id = :id");
    $stmtCheck->execute([':id' => $txn_id]);
    $currentTxn = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($currentTxn) {
        if (strtolower($currentTxn['status']) === 'returned') {
            // Kung Returned na, ibalik sa Borrowed
            $stmt = $conn->prepare("UPDATE transactions SET status = 'Borrowed', return_date = NULL WHERE id = :id");
        } else {
            // Kung Borrowed, gawing Returned
            $stmt = $conn->prepare("UPDATE transactions SET status = 'Returned', return_date = NOW() WHERE id = :id");
        }
        $stmt->execute([':id' => $txn_id]);
    }
}

header("Location: ../admin.php?view=transactions");
exit();
?>