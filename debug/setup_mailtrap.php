<?php
/**
 * Quick Mailtrap Setup for IUC Voting System
 * This will help you configure email delivery in 2 minutes
 */

echo "<h2>Mailtrap Setup for Email Delivery</h2>";

echo "<h3>Step 1: Create Mailtrap Account</h3>";
echo "<ol>";
echo "<li>Go to <a href='https://mailtrap.io/' target='_blank'>https://mailtrap.io/</a></li>";
echo "<li>Sign up for free account (no credit card required)</li>";
echo "<li>Click 'Sign Up' and use Google or email</li>";
echo "</ol>";

echo "<h3>Step 2: Create Inbox</h3>";
echo "<ol>";
echo "<li>After signing in, click 'Email Testing'</li>";
echo "<li>Click 'Create Inbox'</li>";
echo "<li>Give it a name like 'IUC Voting System'</li>";
echo "</ol>";

echo "<h3>Step 3: Get SMTP Credentials</h3>";
echo "<ol>";
echo "<li>In your inbox, click 'SMTP Settings'</li>";
echo "<li>Select 'Integrations' → 'PHP'</li>";
echo "<li>Copy the credentials (they'll look like this):</li>";
echo "</ol>";

echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "Host: smtp.mailtrap.io<br>";
echo "Port: 2525<br>";
echo "Username: xxxxxxxx<br>";
echo "Password: yyyyyyyy<br>";
echo "</div>";

echo "<h3>Step 4: Update php.ini</h3>";
echo "<p>Edit your php.ini file (usually at C:\\wamp64\\bin\\php\\php8.2.18\\php.ini):</p>";

echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "[mail function]<br>";
echo "SMTP = smtp.mailtrap.io<br>";
echo "smtp_port = 2525<br>";
echo "sendmail_from = admin@iuc.edu<br>";
echo "</div>";

echo "<h3>Step 5: Restart WAMP</h3>";
echo "<ol>";
echo "<li>Right-click WAMP icon in system tray</li>";
echo "<li>Click 'Restart All Services'</li>";
echo "<li>Wait for services to restart</li>";
echo "</ol>";

echo "<h3>Step 6: Test Email</h3>";
echo "<p>After restarting, try registering another voter. The email should now work!</p>";

echo "<h3>Alternative: Quick Test Script</h3>";
echo "<p>Create a test file to verify email works before using the voting system:</p>";

echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "&lt;?php<br>";
echo "\$to = 'your-email@gmail.com';<br>";
echo "\$subject = 'Test Email';<br>";
echo "\$message = 'This is a test from IUC Voting System';<br>";
echo "\$headers = 'From: admin@iuc.edu';<br>";
echo "if (mail(\$to, \$subject, \$message, \$headers)) {<br>";
echo "    echo 'Email sent!';<br>";
echo "} else {<br>";
echo "    echo 'Email failed';<br>";
echo "}<br>";
echo "?&gt;<br>";
echo "</div>";

echo "<h3>Benefits of Mailtrap:</h3>";
echo "<ul>";
echo "<li>✅ Free for development (100 emails/month)</li>";
echo "<li>✅ No SMTP server setup required</li>";
echo "<li>✅ Emails don't go to real recipients</li>";
echo "<li>✅ Perfect for testing voting codes</li>";
echo "<li>✅ View emails in web interface</li>";
echo "</ul>";

echo "<p><strong>Once you complete these steps, voting codes will be sent automatically via email!</strong></p>";
?>
