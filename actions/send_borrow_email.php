<?php
// actions/send_borrow_email.php

// Include the tested PHPMailer configuration
require_once __DIR__ . '/../config/mailer.php';

/**
 * Function to send a confirmation email when a member borrows a book
 */
function sendBorrowConfirmationEmail($borrowerEmail, $borrowerName, $bookTitle, $borrowDays, $dueDate) {
    $subject = "BookBase: Book Borrowing Confirmation";
    
    $htmlMessage = "
        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>
            <h2 style='color: #2c3e50;'>Hello, {$borrowerName}!</h2>
            <p>You have successfully borrowed a book from the <b>BookBase Library System</b>.</p>
            <blockquote style='background: #f9f9f9; padding: 12px; border-left: 5px solid #3498db; margin: 15px 0;'>
                <b>Book Title:</b> {$bookTitle}<br>
                <b>Borrow Duration:</b> {$borrowDays} day(s)<br>
                <b>Due Date:</b> <span style='color: #e74c3c;'><b>{$dueDate}</b></span>
            </blockquote>
            <p>Please return the book on or before the due date to avoid any penalties.</p>
            <br>
            <p>Thank you,<br><b>BookBase Library Management Team</b></p>
        </div>
    ";

    // Call the main mailer function
    return sendLibraryEmail($borrowerEmail, $borrowerName, $subject, $htmlMessage);
}
?>