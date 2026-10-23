<?php
session_start();
require_once '../config/db.php';

// Siguraduhing naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $bookId = intval($_POST['book_id']);
    $userId =$_SESSION['user_id']; 
    
    // Kunin ang piniling araw mula sa modal dropdown (default sa 7 days kung wala)
    $borrowDays = intval($_POST['borrow_days'] ?? 7);
    
    // Kwentahin ang due date batay sa pinili ni user
    $dueDate = date('Y-m-d', strtotime("+{$borrowDays} days"));

    try {
        // 1. I-insert ang record sa `transactions` table kasama ang tamang due_date at NULL return_date
        $sqlTrans = "INSERT INTO transactions (book_id, member_id, borrow_date, due_date, return_date, status) 
                     VALUES (:book_id, :member_id, NOW(), :due_date, NULL, 'Borrowed')";
        $stmtTrans =$conn->prepare($sqlTrans);$stmtTrans->execute([
            ':book_id' => $bookId,
            ':member_id' => $userId,
            ':due_date' => $dueDate
        ]);

        // 2. I-update ang status ng libro sa `books` table para maging 'Borrowed'
        $sqlBook = "UPDATE books SET status = 'Borrowed' WHERE id = :id";
        $stmtBook = $conn->prepare($sqlBook);
        $stmtBook->execute([':id' =>$bookId]);

    } catch (PDOException $e) {
        // I-handle ang error kung kinakailangan
    }
}

// Pumunta sa User Dashboard matapos mag-borrow
header("Location: ../user_dashboard.php");
exit();
?>