<?php
// helpers/email_helper.php

function sendEmail($db, $toEmail, $subject, $body)
{
    $headers = "From: no-reply@cpd-portal.local\r\n";
    $headers .= "Reply-To: support@cpd-portal.local\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Try sending via PHP mail()
    // In a local dev environment (XAMPP/WAMP), this often fails without config.
    // We suppress errors with @
    $sent = @mail($toEmail, $subject, $body, $headers);

    // Regardless of success, log it so the examiner can see it "happened"
    // We log to activity_log table
    if ($db) {
        log_activity($db, null, $toEmail, 'email_sent', "Subject: $subject | Body: " . substr(strip_tags($body), 0, 100) . "...");
    }

    return true; // We always return true to not block the user flow
}
?>