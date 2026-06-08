<?php
/**
 * IUC Voting System - Student Login Page
 * Student authentication interface
 */

// Allow access to login form regardless of login status

$error = '';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/config.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Check if database exists
        if ($pdo) {
            // Query user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND type = 'student' AND status = 'approved'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user['type'];
                $_SESSION['login_time'] = time();
                
                header("Location: index.php?page=dashboard");
                exit;
            } else {
                $error = "Invalid email or password";
            }
        } else {
            // Database not available - show setup message
            $error = "Database not set up. Please <a href='setup_database.php' style='color: var(--secondary-color);'>setup the database first</a>.";
        }
    } catch (PDOException $e) {
        $error = "Database error. Please <a href='setup_database.php' style='color: var(--secondary-color);'>setup the database</a>.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-md);">
                        <i class="fas fa-vote-yea" style="font-size: 2rem; color: var(--primary-color);"></i>
                    </div>
                    <h1 style="color: var(--primary-color); font-weight: 700; margin-bottom: var(--spacing-xs);">IUC Voting System</h1>
                    <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin: 0;">Secure Blockchain Voting</p>
                </div>
                <h2 style="color: var(--primary-color); font-weight: 600; margin-bottom: var(--spacing-sm);">Welcome Back</h2>
                <p style="color: var(--text-secondary); margin-bottom: 0;">Sign in to access your voting dashboard</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['message']) && $_GET['message'] === 'registration_success'): ?>
                <div class="success-message">
                    Registration successful! Please sign in with your new account.
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           class="form-control"
                           placeholder="Enter your email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="form-control"
                           placeholder="Enter your password">
                </div>
                
                                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt" style="margin-right: var(--spacing-sm);"></i>
                    Sign In
                </button>
            </form>
            
            <div class="auth-footer">
                <p style="color: var(--text-secondary); margin: 0;">
                    Don't have an account? 
                    <a href="index.php?page=register" style="color: var(--secondary-color); font-weight: 500;">Create Account</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>
