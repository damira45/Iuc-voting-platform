<?php
/**
 * Simple Email Test Script
 * Test email functionality without complex dependencies
 */

// Test basic mail function
echo "<h2>Email Configuration Test</h2>";

echo "<h3>PHP Mail Function Status:</h3>";
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "<br>";
echo "Sendmail path: " . ini_get('sendmail_path') . "<br>";
echo "SMTP: " . ini_get('SMTP') . "<br>";
echo "SMTP port: " . ini_get('smtp_port') . "<br>";

// Test email sending
if (function_exists('mail')) {
    echo "<h3>Testing Email Send:</h3>";
    
    $to = 'cherifnsangou@gmail.com';
    $subject = 'Test Email from IUC Voting System';
    $message = 'This is a test email to verify the email system is working.';
    
    $headers = [
        'From: IUC Voting System <admin@iuc.edu>',
        'Reply-To: admin@iuc.edu',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headers_string = implode("\r\n", $headers);
    
    $result = mail($to, $subject, $message, $headers_string);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Email sending failed.</p>";
        echo "<p>Last error: " . (error_get_last()['message'] ?? 'No error message') . "</p>";
        
        echo "<h3>Solutions:</h3>";
        echo "<ol>";
        echo "<li><strong>Configure php.ini:</strong> Edit your php.ini file and set SMTP settings</li>";
        echo "<li><strong>Use Mailtrap:</strong> Create a free account at mailtrap.io for testing</li>";
        echo "<li><strong>Install local SMTP:</strong> Use hMailServer for local testing</li>";
        echo "</ol>";
        
        echo "<h3>Quick Fix - Mailtrap Setup:</h3>";
        echo "<ol>";
        echo "<li>Go to <a href='https://mailtrap.io/' target='_blank'>mailtrap.io</a></li>";
        echo "<li>Sign up for free account</li>";
        echo "<li>Create new inbox</li>";
        echo "<li>Copy SMTP credentials</li>";
        echo "<li>Add to php.ini: <code>SMTP = smtp.mailtrap.io</code></li>";
        echo "<li>Add to php.ini: <code>smtp_port = 2525</code></li>";
        echo "<li>Restart WAMP server</li>";
        echo "</ol>";
    }
} else {
    echo "<p style='color: red;'>Mail function is not available.</p>";
}

echo "<h3>Alternative: Use Browser Email</h3>";
echo "<p>If email configuration is complex, you can:</p>";
echo "<ul>";
echo "<li>Display voting codes to admin (current working method)</li>";
echo "<li>Use a web-based email service</li>";
echo "<li>Configure SMTP later when ready for production</li>";
echo "</ul>";

// Test the current email function
echo "<h3>Testing Current Email Function:</h3>";
if (file_exists('email_config.php')) {
    require_once 'email_config.php';
    
    $test_result = sendEmailSimple('cherifnsangou@gmail.com', 'Test from Email Config', 'Testing the email_config.php function');
    
    if ($test_result) {
        echo "<p style='color: green;'>✓ Email function works!</p>";
    } else {
        echo "<p style='color: red;'>✗ Email function failed (expected without SMTP setup)</p>";
    }
} else {
    echo "<p style='color: orange;'>email_config.php not found</p>";
}
?>
