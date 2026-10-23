<?php
require_once '../config/db.php';

try {
    // Kunin ang mga active transactions
    $sql = "SELECT t.*, u.username, b.title as book_title 
            FROM transactions t 
            JOIN users u ON t.member_id = u.id 
            JOIN books b ON t.book_id = b.id 
            WHERE t.status = 'Borrowed'";
    
    $stmt = $conn->query($sql);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime();
    $updateStmt = $conn->prepare("UPDATE transactions SET fine = :fine WHERE id = :id");

    foreach ($transactions as $txn) {
        $dueDate = new DateTime($txn['due_date']);
        $interval = $today->diff($dueDate);
        $daysRemaining = (int)$interval->format('%r%d'); // Negative kung overdue na

        if ($daysRemaining < 0) {
            $daysOverdue = abs($daysRemaining);
            $calculatedFine = $daysOverdue * 10; // Halimbawa: ₱10 bawat araw na lumagpas

            // I-update ang fine sa database para sa transaction na ito
            $updateStmt->execute([
                ':fine' => $calculatedFine,
                ':id' => $txn['id'] // Siguraduhing tumutugma ito sa primary key ng transactions table mo
            ]);
        }
    }

    // Redirect pabalik sa admin transactions view na may success message
    header("Location: ../admin.php?view=transactions&updated=1");
    exit();

} catch (Exception $e) {
    echo "May naganap na error: " . $e->getMessage();
}
?>