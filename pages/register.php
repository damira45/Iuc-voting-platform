<?php
/**
 * IUC Voting System - Registration Page
 * Student registration interface
 */

// Allow access to registration form regardless of login status

$errors = [];
$success = '';

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/config.php';
    require_once 'includes/NotificationManager.php';
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $studentId = $_POST['student_id'];
    $department = $_POST['department'];
    $level = $_POST['level'];
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    if (empty($studentId)) $errors[] = "Student ID is required";
    if (empty($department)) $errors[] = "Department is required";
    if (empty($level)) $errors[] = "Level is required";
    
    if (empty($errors)) {
        try {
            // Check if database is available
            if (!$pdo) {
                $errors[] = "Database not available. Please <a href='setup_database.php' style='color: var(--secondary-color);'>setup the database first</a>.";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email already registered";
                } else {
                    // Check if student ID already exists
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
                    $stmt->execute([$studentId]);
                    if ($stmt->fetch()) {
                        $errors[] = "Student ID already registered";
                    } else {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Insert user
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, type, status) VALUES (?, ?, ?, 'student', 'approved')");
                        $stmt->execute([$name, $email, $hashedPassword]);
                        $userId = $pdo->lastInsertId();
                        
                        // Insert student record
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, department, level) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, $studentId, $department, $level]);
                        
                        // Create notification for admin approval
                        $notificationManager = new NotificationManager($pdo);
                        $notificationManager->createNotification(
                            'student_registration',
                            'New Student Registration - Approval Required',
                            "Student {$name} ({$email}, Student ID: {$studentId}) has registered and requires approval and voting code generation.",
                            null,
                            $userId,
                            'high',
                            true,
                            "index.php?page=voter_registration&action=generate_code&student_id={$userId}",
                            'Generate Voting Code'
                        );
                        
                        $pdo->commit();
                        $success = "Registration successful! Your account has been created and will be reviewed by an administrator. You will receive a voting code once approved.";
                    }
                }
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Database error. Please <a href='setup_database.php' style='color: var(--secondary-color);'>setup the database</a>.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div style="text-align: center; margin-bottom: var(--spacing-lg);">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-md);">
                        <i class="fas fa-user-plus" style="font-size: 2rem; color: var(--primary-color);"></i>
                    </div>
                    <h1 style="color: var(--primary-color); font-weight: 700; margin-bottom: var(--spacing-xs);">IUC Voting System</h1>
                    <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin: 0;">Secure Blockchain Voting</p>
                </div>
                <h2 style="color: var(--primary-color); font-weight: 600; margin-bottom: var(--spacing-sm);">Create Account</h2>
                <p style="color: var(--text-secondary); margin-bottom: 0;">Register to participate in secure elections</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="index.php?page=register" class="auth-form" id="registrationForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required 
                               class="form-control"
                               placeholder="Enter your full legal name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" required 
                               class="form-control"
                               placeholder="IUC-2024-1234" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           class="form-control"
                           placeholder="Enter your institutional email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required class="form-control">
                            <option value="">Select your department</option>
                            <option value="Computer Science" <?php echo (($_POST['department'] ?? '') === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Business Administration" <?php echo (($_POST['department'] ?? '') === 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                            <option value="Engineering" <?php echo (($_POST['department'] ?? '') === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Political Science" <?php echo (($_POST['department'] ?? '') === 'Political Science') ? 'selected' : ''; ?>>Political Science</option>
                            <option value="Environmental Science" <?php echo (($_POST['department'] ?? '') === 'Environmental Science') ? 'selected' : ''; ?>>Environmental Science</option>
                            <option value="Civil Engineering" <?php echo (($_POST['department'] ?? '') === 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="level">Academic Level</label>
                        <select id="level" name="level" required class="form-control">
                            <option value="">Select your academic level</option>
                            <option value="Level 1" <?php echo (($_POST['level'] ?? '') === 'Level 1') ? 'selected' : ''; ?>>Level 1</option>
                            <option value="Level 2" <?php echo (($_POST['level'] ?? '') === 'Level 2') ? 'selected' : ''; ?>>Level 2</option>
                            <option value="Level 3" <?php echo (($_POST['level'] ?? '') === 'Level 3') ? 'selected' : ''; ?>>Level 3</option>
                            <option value="Level 4" <?php echo (($_POST['level'] ?? '') === 'Level 4') ? 'selected' : ''; ?>>Level 4</option>
                            <option value="Masters" <?php echo (($_POST['level'] ?? '') === 'Masters') ? 'selected' : ''; ?>>Masters</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="form-control"
                           placeholder="Create a strong password (min 8 characters)">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           class="form-control"
                           placeholder="Re-enter your password to confirm">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" style="cursor: pointer;">
                    <i class="fas fa-user-plus" style="margin-right: var(--spacing-sm);"></i>
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p style="color: var(--text-secondary); margin: 0;">
                    Already have a voting code? 
                    <a href="index.php?page=student_login" style="color: var(--secondary-color); font-weight: 500;">Login Here</a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Simple form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Basic validation
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    const email = document.getElementById('email').value;
                    
                    // Password validation
                    if (password.length < 8) {
                        alert('Password must be at least 8 characters long');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        alert('Passwords do not match');
                        e.preventDefault();
                        return false;
                    }
                    
                    // Email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        alert('Please enter a valid email address');
                        e.preventDefault();
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                    submitBtn.disabled = true;
                    
                    // Let form submit normally
                    return true;
                });
            }
        });
    </script>
</body>
</html>
