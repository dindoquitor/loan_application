<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // If using Composer

function sendProcessingEmail($to_email, $to_name, $processing_id)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'your-smtp-host.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@domain.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('noreply@yourdomain.com', 'Loan Application System');
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Processing ID for Co-borrower';
        $mail->Body = "
            <h3>Loan Application Processing ID</h3>
            <p>Dear $to_name,</p>
            <p>Your loan application has been submitted successfully.</p>
            <p><strong>Processing ID:</strong> $processing_id</p>
            <p>Please share this Processing ID with your co-borrower to complete the application process.</p>
            <p>Thank you,<br>Loan Application System</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
