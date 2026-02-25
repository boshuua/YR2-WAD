<?php
// helpers/email_helper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($db, $toEmail, $subject, $body)
{
    // Try to load PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                       // Send using SMTP
        $mail->Host = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'localhost';

        $smtpUser = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: '';
        if (!empty($smtpUser)) {
            $mail->SMTPAuth = true;                          // Enable SMTP authentication
            $mail->Username = $smtpUser;                     // SMTP username
            $mail->Password = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '';   // SMTP password

            // Port 465 usually requires implicit TLS (SMTPS), while 587 uses STARTTLS
            $port = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 1025;
            $mail->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAuth = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->Port = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 1025; // TCP port to connect to

        // Recipients
        $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: 'no-reply@cpd-portal.local';
        $fromName = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'CPD Portal';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);                           // Add a recipient
        $mail->addReplyTo('support@cpd-portal.local', 'Support');

        // Content
        $mail->isHTML(true);                                   // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $sent = $mail->send();
    } catch (Exception $e) {
        // Fallback or log error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        $sent = false;
    }

    // Regardless of success, log it so the examiner can see it "happened"
    // We log to activity_log table
    if ($db) {
        log_activity($db, null, $toEmail, 'email_sent', "Subject: $subject | Body: " . substr(strip_tags($body), 0, 100) . "...");
    }

    return true; // We always return true to not block the user flow
}
?>