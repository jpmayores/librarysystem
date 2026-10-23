<?php
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendLibraryEmail($toEmail, $recipientName, $subject, $htmlMessage)
{
    $mail = new PHPMailer(true);

    try {
        // Brevo SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;

        // I-paste ang iyong Brevo Login mula sa screen
        $mail->Username = 'bfa08b001@smtp-brevo.com';

        // I-paste dito ang iyong SMTP Key (ang mahabang key na kinuha mo kanina)
        $mail->Password = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender Info
        $mail->setFrom('jhonphilipmayores@gmail.com', 'PageFlow Library System');
        $mail->addAddress($toEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlMessage;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Error: {$mail->ErrorInfo}";
    }
}
?>