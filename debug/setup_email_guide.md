# Email Setup Guide for IUC Voting System

## Option 1: Configure PHP Mail (Easiest)

### Step 1: Configure php.ini
Edit your `php.ini` file (usually in `C:\wamp64\bin\php\php8.2.18\php.ini`):

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = admin@iuc.edu
smtp_port = 587
username = your-gmail@gmail.com
password = your-app-password
```

### Step 2: Gmail App Password (Optional but Recommended)
1. Go to: https://myaccount.google.com/apppasswords
2. Enable 2-factor authentication if needed
3. Create App Password for "Mail"
4. Use this password in php.ini

### Step 3: Restart Apache
Restart WAMP server to apply php.ini changes

## Option 2: Use Local SMTP Server

### Step 1: Install hMailServer (Windows)
1. Download from: https://www.hmailserver.com/
2. Install and configure with default settings
3. Set up local domain in hMailServer

### Step 2: Update php.ini
```ini
[mail function]
SMTP = localhost
smtp_port = 25
sendmail_from = admin@iuc.edu
```

## Option 3: Use Online SMTP Service

### Step 1: Create Mailtrap Account
1. Go to: https://mailtrap.io/
2. Sign up for free account
3. Create new inbox

### Step 2: Configure php.ini
```ini
[mail function]
SMTP = smtp.mailtrap.io
smtp_port = 2525
sendmail_from = admin@iuc.edu
username = your-mailtrap-username
password = your-mailtrap-password
```

## Testing Email Functionality

### Test Script
Create `test_email.php`:
```php
<?php
require_once 'email_config.php';

$test_result = sendEmailSimple(
    'your-test-email@gmail.com',
    'Test Email from IUC Voting System',
    'This is a test email to verify email functionality is working.'
);

if ($test_result) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed. Check php.ini configuration.";
}
?>
```

## Quick Fix for Local Development

If you want emails to work immediately without complex setup:

1. **Use a fake SMTP service** like Mailtrap for testing
2. **Configure php.ini** with basic settings
3. **Test with the test script** above

## Troubleshooting

### Common Issues:
1. **"SMTP connect() failed"** - Check firewall/antivirus blocking port 587
2. **"Authentication failed"** - Use App Password, not regular password
3. **"Could not instantiate mail function"** - Check php.ini mail settings
4. **"Connection timed out"** - Check internet connection and SMTP settings

### Check PHP Mail Status:
Create `check_mail.php`:
```php
<?php
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "\n";
echo "Sendmail path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "SMTP port: " . ini_get('smtp_port') . "\n";
?>
```

## Option 2: Mailtrap (Testing Only)

### Step 1: Create Mailtrap Account
1. Go to: https://mailtrap.io/
2. Sign up for free account
3. Create a new inbox

### Step 2: Get SMTP Credentials
1. Go to your inbox settings
2. Copy the SMTP credentials

### Step 3: Update Configuration
Edit `email_config.php` with your Mailtrap credentials

## Option 3: Use Local Mail Server

### Step 1: Install hMailServer (Windows)
1. Download from: https://www.hmailserver.com/
2. Install and configure
3. Set up local domain

### Step 2: Configure php.ini
Edit your `php.ini` file:
```ini
[mail function]
SMTP = localhost
smtp_port = 25
sendmail_from = admin@iuc.edu
```

### Step 3: Restart Apache
Restart WAMP server to apply changes

## Testing Email Functionality

### Test Script
Create `test_email.php`:
```php
<?php
require_once 'email_config.php';

$test_result = sendEmailWithSMTP(
    'your-test-email@gmail.com',
    'Test Email',
    'This is a test email from IUC Voting System',
    $smtp_config
);

if ($test_result) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed.";
}
?>
```

## Troubleshooting

### Common Issues:
1. **"SMTP connect() failed"** - Check firewall/antivirus blocking port 587
2. **"Authentication failed"** - Use App Password, not regular password
3. **"Could not instantiate mail function"** - Install PHPMailer correctly
4. **"Connection timed out"** - Check internet connection and SMTP settings

### Debug Mode:
Add this to your email function for debugging:
```php
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
```

## Security Notes:
- Never commit passwords to version control
- Use environment variables in production
- Consider using a dedicated email service for production
