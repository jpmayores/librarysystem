<?php
require_once '../config/db.php';

// Itakda ang headers para ma-download ito bilang CSV file na bubukas sa Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=library_transactions_report_' . date('Y-m-d') . '.csv');

// Buksan ang output stream
$output = fopen('php://output', 'w');

// I-print ang column headers para sa CSV
fputcsv($output, ['TXN ID', 'Member ID', 'Book Title', 'Due Date', 'Status', 'Overdue Fine (PHP)']);

try {
    // Kunin ang lahat ng transactions mula sa database
    $stmt = $conn->query("SELECT t.id as txn_id, t.member_id, b.title as book_title, t.due_date, t.status, t.fine as overdue_fine 
                          FROM transactions t 
                          JOIN books b ON t.book_id = b.id 
                          ORDER BY t.borrow_date DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($transactions as $txn) {
        fputcsv($output, [
            $txn['txn_id'],
            $txn['member_id'],
            $txn['book_title'],
            $txn['due_date'],
            $txn['status'],
            $txn['overdue_fine'] ?? 0
        ]);
    }
} catch (Exception $e) {
    // Handle error kung mayroon man
}

fclose($output);
exit();
?>