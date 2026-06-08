<?php
/**
 * Email Configuration for IUC Voting System
 * Simple email configuration without external dependencies
 */

// Email configuration
$email_config = [
    'from_email' => 'admin@iuc.edu',
    'from_name' => 'IUC Voting System',
    'reply_to' => 'admin@iuc.edu'
];

function sendEmailSimple($to, $subject, $message) {
    // Define email config directly to avoid global variable issues
    $from_name = 'IUC Voting System';
    $from_email = 'admin@iuc.edu';
    $reply_to = 'admin@iuc.edu';
    
    $headers = [];
    $headers[] = "From: $from_name <$from_email>";
    $headers[] = "Reply-To: $reply_to";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    $headers_string = implode("\r\n", $headers);
    
    // Try to send email (suppress warnings for clean interface)
    $result = @mail($to, $subject, $message, $headers_string);
    
    if (!$result) {
        // Log error silently for debugging
        error_log("Email sending failed to: $to - SMTP not configured");
    }
    
    return $result;
}

function sendEmailWithSMTP($to, $subject, $message, $config) {
    // For now, fallback to simple email
    // You can configure SMTP in php.ini for better results
    return sendEmailSimple($to, $subject, $message);
}
?>
