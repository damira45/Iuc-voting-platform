<?php
/**
 * IUC Voting System - Contact Page
 */

require_once 'config/config.php';
require_once 'includes/header.php';
require_once 'includes/NotificationManager.php';

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Save to DB
            $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message, status, created_at)
                VALUES (?, ?, ?, ?, 'unread', NOW())
            ")->execute([$name, $email, $subject, $message]);

            $msgId = $pdo->lastInsertId();

            // Send notification to admin
            $nm = new NotificationManager($pdo);
            $nm->createNotification(
                'general',
                'New Contact Message: ' . $subject,
                "From: $name ($email)\n\n$message",
                null,
                null,
                'high',
                true,
                'index.php?page=complain',
                'View Messages'
            );

            // Log the activity
            $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, created_at)
                VALUES (NULL, 'CONTACT_MESSAGE', ?, ?, NOW())
            ")->execute(["Contact from $name ($email): $subject", $_SERVER['REMOTE_ADDR'] ?? '']);

            $success_message = 'Your message has been sent successfully! An administrator will respond to your email soon.';
        } catch (Exception $e) {
            $error_message = 'Failed to send message. Please try again.';
        }
    }
}
?>

<section class="page-section">
    <div class="container">
        <div class="section-header">
            <h1>Contact Us</h1>
            <p>Get in touch with our team for support and inquiries</p>
        </div>
        
        <div class="contact-content">
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-form">
                        <h2>Send us a Message</h2>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="form">
                            <div class="form-group">
                                <label for="name">Your Name</label>
                                <input type="text" id="name" name="name" required 
                                       class="form-control"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required 
                                       class="form-control"
                                       placeholder="Enter your email">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required 
                                       class="form-control"
                                       placeholder="What is this regarding?">
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required 
                                          class="form-control"
                                          rows="5"
                                          placeholder="Tell us more about your inquiry..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="contact-info">
                        <h2>Get in Touch</h2>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Email</h3>
                                <p>support@iucvoting.edu</p>
                                <p>admin@iucvoting.edu</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Phone</h3>
                                <p>+1 (555) 123-4567</p>
                                <p>Mon-Fri, 9AM-5PM EST</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Address</h3>
                                <p>IUC Administration Building</p>
                                <p>123 University Avenue</p>
                                <p>Education City, EC 12345</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Support Hours</h3>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                <p>Saturday: 10:00 AM - 2:00 PM</p>
                                <p>Sunday: Closed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
