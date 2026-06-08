<?php
/**
 * IUC Voting System - Admin Login
 * Separate login for administrators
 */

// Check if admin is already logged in
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin' && !empty($_SESSION['user_id'])) {
    header('Location: index.php?page=admin');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/config.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Query admin from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND type = 'admin' AND status = 'approved'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful — set ALL session keys consistently
            $_SESSION['user_id']    = $admin['id'];
            $_SESSION['user_name']  = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_type']  = 'admin';
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_email']= $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['login_time'] = time();
            
            header('Location: index.php?page=admin');
            exit;
        } else {
            // Fallback hardcoded admin
            if ($email === 'admin@iuc.edu' && $password === 'admin123') {
                $_SESSION['user_id']    = 1;
                $_SESSION['user_name']  = 'Administrator';
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type']  = 'admin';
                $_SESSION['admin_id']   = 1;
                $_SESSION['admin_email']= $email;
                $_SESSION['admin_name'] = 'Administrator';
                $_SESSION['login_time'] = time();
                
                header('Location: index.php?page=admin');
                exit;
            } else {
                $error = 'Invalid admin credentials';
            }
        }
    } catch (PDOException $e) {
        $error = 'Login failed. Please try again.';
    }
}
?>

<style>
    /* Admin Login Page Styles */
    .auth-section {
        min-height: 100vh;
        background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        font-family: 'Inter', sans-serif;
    }
    
    .container {
        width: 100%;
        max-width: 400px;
    }
    
    .auth-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .auth-header h1 {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }
    
    .auth-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .auth-header p {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3282B8;
        background: white;
        box-shadow: 0 0 0 3px rgba(50, 130, 184, 0.1);
    }
    
    .form-control::placeholder {
        color: #9ca3af;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
        color: white;
        width: 100%;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(11, 60, 93, 0.3);
    }
    
    .btn-full {
        width: 100%;
    }
    
    .error-message {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 2px solid #ef4444;
        color: #dc2626;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .auth-footer a {
        color: #0B3C5D;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    
    .auth-footer a:hover {
        color: #764ba2;
    }
    
    /* Icon styling */
    .auth-header .fa-shield-alt {
        font-size: 2rem;
        color: #667eea;
    }
    
    /* Responsive design */
    @media (max-width: 480px) {
        .auth-section {
            padding: 1rem;
        }
        
        .auth-card {
            padding: 2rem;
        }
        
        .auth-header h1 {
            font-size: 1.75rem;
        }
        
        .auth-header h2 {
            font-size: 1.25rem;
        }
    }
</style>

<!-- Admin Login Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-md);">
                        <i class="fas fa-shield-alt" style="font-size: 2rem; color: var(--primary-color);"></i>
                    </div>
                    <h1 style="color: var(--primary-color); font-weight: 700; margin-bottom: var(--spacing-xs);">Admin Access</h1>
                    <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin: 0;">IUC Voting System</p>
                </div>
                <h2 style="color: var(--primary-color); font-weight: 600; margin-bottom: var(--spacing-sm);">Administrator Login</h2>
                <p style="color: var(--text-secondary); margin-bottom: 0;">Access the admin dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" required 
                           class="form-control"
                           placeholder="Enter admin email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="form-control"
                           placeholder="Enter admin password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt" style="margin-right: var(--spacing-sm);"></i>
                    Login to Admin
                </button>
            </form>
            
            <div class="auth-footer">
                <p style="color: var(--text-secondary); margin: 0;">
                    <a href="index.php" style="color: var(--secondary-color); font-weight: 500;">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
