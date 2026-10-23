<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txnId = $_POST['txn_id'] ?? '';
    $bookId = $_POST['book_id'] ?? '';
    $condition = $_POST['condition_status'] ?? 'Good';
    $damageFee = isset($_POST['damage_fee']) ? floatval($_POST['damage_fee']) : 0.00;

    // Kung sakaling blangko ang book_id mula sa POST, kunin natin diretso sa database gamit ang txn_id
    if (empty($bookId) && !empty($txnId)) {
        $stmtBookCheck = $conn->prepare("SELECT book_id FROM transactions WHERE id = ?");
        $stmtBookCheck->execute([$txnId]);
        $bookData = $stmtBookCheck->fetch(PDO::FETCH_ASSOC);
        $bookId = $bookData['book_id'] ?? '';
    }

    if (!empty($txnId) && !empty($bookId)) {
        try {
            // 1. Kunin ang kasalukuyang fine ng transaksyon
            $stmt = $conn->prepare("SELECT fine FROM transactions WHERE id = ?");
            $stmt->execute([$txnId]);
            $txn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentFine = isset($txn['fine']) ? floatval($txn['fine']) : 0.00;
            $totalFine = $currentFine + $damageFee;

            // 2. I-update ang transaction: status patungong 'Returned', itala ang condition_status at total fine
            $updateTxn = $conn->prepare("UPDATE transactions SET status = 'Returned', condition_status = ?, fine = ? WHERE id = ?");
            $updateTxn->execute([$condition, $totalFine, $txnId]);

            // 3. I-update ang status ng libro sa inventory ('Damaged' o 'Available')
            $bookStatus = ($condition === 'Damaged') ? 'Damaged' : 'Available';
            $updateBook = $conn->prepare("UPDATE books SET status = ? WHERE id = ?");
            $updateBook->execute([$bookStatus, $bookId]);
                 
            // 3. i-RECORD SA AUDIT LOG
            $logAction = $conn->prepare("INSERT INTO audit_logs (action, details) VALUES (?, ?)");
            $logAction->execute([
                'Book Return Processed', 
                "Transaction ID: $txnId, Book ID: $bookId, Condition: $condition, Total Fine Added/Updated: ₱$totalFine"
            ]);
        } catch (Exception $e) {
            // Optional error message kung kailangan i-troubleshoot ang SQL
            // echo "Database Error: " . $e->getMessage();
            // exit();
        }
    }
}

// Ibalik sa admin panel pagkatapos ma-process
header("Location: ../admin.php?view=transactions");
exit();
?>