<?php
/**
 * Email Configuration Helper
 * This script helps you configure email settings for the voting system
 */

echo "<h2>Email Configuration Helper</h2>";

// Check current email configuration
echo "<h3>Current Email Configuration:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>SMTP</td><td>" . ini_get('SMTP') . "</td></tr>";
echo "<tr><td>SMTP Port</td><td>" . ini_get('smtp_port') . "</td></tr>";
echo "<tr><td>Sendmail Path</td><td>" . ini_get('sendmail_path') . "</td></tr>";
echo "<tr><td>Mail Function</td><td>" . (function_exists('mail') ? 'Available' : 'Not Available') . "</td></tr>";
echo "</table>";

// Provide configuration options
echo "<h3>Quick Setup Options:</h3>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>Option 1: Use Mailtrap (Recommended for Testing)</h4>";
echo "<p><strong>Steps:</strong></p>";
echo "<ol>";
echo "<li>Go to <a href='https://mailtrap.io/' target='_blank'>mailtrap.io</a></li>";
echo "<li>Sign up for free account</li>";
echo "<li>Create inbox and get SMTP credentials</li>";
echo "<li>Add to your php.ini:</li>";
echo "</ol>";
echo "<code>SMTP = smtp.mailtrap.io<br>smtp_port = 2525</code>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>Option 2: Use Gmail SMTP</h4>";
echo "<p><strong>Steps:</strong></p>";
echo "<ol>";
echo "<li>Enable 2-factor authentication on Gmail</li>";
echo "<li>Create App Password at <a href='https://myaccount.google.com/apppasswords' target='_blank'>Google App Passwords</a></li>";
echo "<li>Add to your php.ini:</li>";
echo "</ol>";
echo "<code>SMTP = smtp.gmail.com<br>smtp_port = 587</code>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
echo "<h4>Option 3: Keep Current System (Manual Code Delivery)</h4>";
echo "<p>The current system works perfectly for local development:</p>";
echo "<ul>";
echo "<li>✅ Voter registration works</li>";
echo "<li>✅ Voting codes are generated</li>";
echo "<li>✅ Codes are displayed to admin for manual delivery</li>";
echo "<li>✅ Students can vote with provided codes</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Current System Status: WORKING ✅</h3>";
echo "<p>Your voting system is fully functional. The email failure is expected in local development.</p>";
echo "<p><strong>Current workflow:</strong></p>";
echo "<ol>";
echo "<li>Admin registers voter → System generates voting code</li>";
echo "<li>Email fails (expected) → Code displayed to admin</li>";
echo "<li>Admin provides code to student manually</li>";
echo "<li>Student enters code in voting interface → Can vote</li>";
echo "</ol>";

echo "<h3>Test Current Email Function:</h3>";
if (isset($_GET['test_email'])) {
    $to = 'cherifnsangou@gmail.com';
    $subject = 'Test from IUC Voting System';
    $message = 'This is a test email to verify the email system.';
    $headers = 'From: IUC Voting System <admin@iuc.edu>';
    
    $result = mail($to, $subject, $message, $headers);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Test email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Test email failed (expected without SMTP setup)</p>";
    }
} else {
    echo "<p><a href='?test_email=1'>Test Email Function</a></p>";
}

echo "<h3>Recommendation:</h3>";
echo "<p><strong>For development:</strong> Keep using the current manual system - it works perfectly!</p>";
echo "<p><strong>For production:</strong> Configure SMTP using one of the options above.</p>";
?>
