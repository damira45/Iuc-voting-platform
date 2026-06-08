<?php
/**
 * IUC Voting System - Student Login Page
 * Students login with Student ID + Voting Code
 */

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $votingCode = strtoupper(trim($_POST['voting_code'] ?? ''));
    $studentId  = trim($_POST['student_id'] ?? '');

    if (empty($votingCode)) $errors[] = "Voting code is required.";
    if (empty($studentId))  $errors[] = "Student ID is required.";

    if (empty($errors)) {
        try {
            // Find the voting code — no status restriction so used/sent both work
            $stmt = $pdo->prepare("
                SELECT vc.*, u.name, u.email, u.id AS user_id, u.status AS user_status,
                       s.student_id AS s_student_id, s.department, s.level
                FROM voting_codes vc
                JOIN users u ON vc.student_id = u.id
                LEFT JOIN students s ON s.user_id = u.id
                WHERE vc.voting_code = ?
                LIMIT 1
            ");
            $stmt->execute([$votingCode]);
            $codeData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$codeData) {
                $errors[] = "Invalid voting code. Please check and try again.";
            } elseif ($codeData['user_status'] !== 'approved') {
                $errors[] = "Your account is pending admin approval. Please contact the administrator.";
            } else {
                // Verify student ID matches
                $stmt2 = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? AND student_id = ?");
                $stmt2->execute([$codeData['user_id'], $studentId]);
                if (!$stmt2->fetch()) {
                    $errors[] = "Student ID does not match this voting code.";
                } else {
                    // Login successful
                    $_SESSION['user_id']    = $codeData['user_id'];
                    $_SESSION['user_name']  = $codeData['name'];
                    $_SESSION['user_email'] = $codeData['email'];
                    $_SESSION['user_type']  = 'student';
                    $_SESSION['student_id'] = $studentId;

                    header("Location: index.php?page=dashboard");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Login error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        .voting-code-input {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            letter-spacing: 0.1rem;
            text-transform: uppercase;
        }
        
        .login-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .login-info h4 {
            color: #0369a1;
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .login-info p {
            color: #64748b;
            margin: 0.25rem 0;
            font-size: 0.8rem;
        }
        
        .code-format {
            background: #1e293b;
            color: #10b981;
            padding: 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            text-align: center;
            margin: 0.5rem 0;
        }
    </style>
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
                    <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin: 0;">Student Portal</p>
                </div>
                <h2 style="color: var(--primary-color); font-weight: 600; margin-bottom: var(--spacing-sm);">Student Login</h2>
                <p style="color: var(--text-secondary); margin-bottom: 0;">Enter your voting code to access the voting system</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="login-info">
                <h4><i class="fas fa-info-circle"></i> How to Login</h4>
                <p>1. Enter your Student ID (e.g., IUC-2024-1234)</p>
                <p>2. Enter the voting code sent to your email</p>
                <p>3. Click "Login" to access the voting system</p>
                <div class="code-format">VOTE-7KOU-R9SW-Y1C2-II7K</div>
                <p style="font-size: 0.7rem; text-align: center; margin-top: 0.5rem;">Format: VOTE-XXXX-XXXX-XXXX-XXXX</p>
            </div>
            
            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" required 
                           class="form-control"
                           placeholder="Enter your Student ID (e.g., IUC-2024-1234)" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="voting_code">Voting Code</label>
                    <input type="text" id="voting_code" name="voting_code" required 
                           class="form-control voting-code-input"
                           placeholder="Enter your voting code" value="<?php echo htmlspecialchars($_POST['voting_code'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" style="cursor: pointer;">
                    <i class="fas fa-sign-in-alt" style="margin-right: var(--spacing-sm);"></i>
                    Login to Vote
                </button>
            </form>
            
            <div class="auth-footer">
                <p style="color: var(--text-secondary); margin: 0;">
                    Contact your administrator if you don't have a voting code.
                </p>
                <p style="color: var(--text-secondary); margin: 0.5rem 0 0;">
                    <a href="index.php?page=home" style="color: var(--secondary-color); font-weight: 500;">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const votingCode = document.getElementById('voting_code').value.trim();
                    const studentId  = document.getElementById('student_id').value.trim();

                    if (!votingCode || !studentId) {
                        alert('Please fill in both Student ID and Voting Code.');
                        e.preventDefault();
                        return false;
                    }

                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
                    submitBtn.disabled = true;
                    return true;
                });
            }
        });
    </script>
</body>
</html>
