<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/helpers/email_helper.php';

$toEmail = 'admin@ws369808-wad.remote.ac'; // Change this if you want to test a real address
$subject = 'Terminal Test Email';
$body = '<h1>It works!</h1><p>This is a test email sent from the command line via PHPMailer.</p>';

echo "Attempting to send an email to {$toEmail}...\n";

// Passing null for $db since we just want to test the email sending, not the activity logging
$result = sendEmail(null, $toEmail, $subject, $body);

if ($result === true) {
    echo "Done! The sendEmail function executed and successfully dispatched the email.\n";
    echo "Check your terminal for any error logs, or check your mail catcher to see if it arrived.\n";
} else {
    echo "Failed to send email.\n";
    echo "Reason: " . $result . "\n";
}
