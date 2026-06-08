<?php
/**
 * IUC Voting System - Voter Registration Page
 * Admin interface for registering voters and sending voting codes via email
 */

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=admin_login');
    exit;
}

// Check if user is admin
$userType = $_SESSION['user_type'] ?? '';
if ($userType !== 'admin') {
    header('Location: index.php?page=admin_login');
    exit;
}

require_once 'config/config.php';
require_once 'includes/NotificationManager.php';

// Initialize notification manager
$notificationManager = new NotificationManager($pdo);

// Check for student_id parameter in URL (for pre-selection from notifications)
$preselected_student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$preselected_student = null;

if ($preselected_student_id) {
    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, s.student_id, s.department, s.level 
                          FROM users u 
                          JOIN students s ON u.id = s.user_id 
                          WHERE u.id = ? AND u.type = 'student'");
    $stmt->execute([$preselected_student_id]);
    $preselected_student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle voter registration and voting code sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'register_voter') {
        try {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $student_id = $_POST['student_id'];
            $department = $_POST['department'];
            $year = $_POST['year'];
            
            // Use manual voting code if provided, otherwise generate one
            $voting_code = !empty($_POST['voting_code']) ? $_POST['voting_code'] : generateVotingCode();
            
            if ($pdo) {
                // Get all available columns in students table
                $stmt = $pdo->query("DESCRIBE students");
                $allColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $availableColumns = array_column($allColumns, 'Field');
                
                // Check for essential columns
                $hasStudentId = in_array('student_id', $availableColumns);
                $hasEmail = in_array('email', $availableColumns);
                $hasName = in_array('name', $availableColumns);
                $hasDepartment = in_array('department', $availableColumns);
                $hasYear = in_array('year', $availableColumns);
                $hasLevel = in_array('level', $availableColumns);
                $hasVotingCode = in_array('voting_code', $availableColumns);
                $hasCreatedAt = in_array('created_at', $availableColumns);
                $hasUserId = in_array('user_id', $availableColumns);
                
                // Check if user_id is required (foreign key)
                $userIdRequired = false;
                if ($hasUserId) {
                    foreach ($allColumns as $column) {
                        if ($column['Field'] === 'user_id' && $column['Null'] === 'NO') {
                            $userIdRequired = true;
                            break;
                        }
                    }
                }
                
                // Check if student already exists
                if ($hasStudentId && $hasEmail) {
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                    $stmt->execute([$student_id, $email]);
                } elseif ($hasStudentId) {
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                } else {
                    $error_message = "Cannot check for existing students - missing student_id column";
                }
                
                if (!isset($error_message) && $stmt->rowCount() > 0) {
                    $error_message = "Student with this ID already exists";
                } elseif (!isset($error_message)) {
                    // Handle user_id constraint if required
                    if ($userIdRequired) {
                        // Create a corresponding user record first
                        try {
                            $password = password_hash('temp123', PASSWORD_DEFAULT);
                            $userSql = "INSERT INTO users (name, email, password, type, status) VALUES (?, ?, ?, 'student', 'approved')";
                            $userStmt = $pdo->prepare($userSql);
                            $userStmt->execute([$name, $email, $password]);
                            $userId = $pdo->lastInsertId();
                        } catch (Exception $e) {
                            $error_message = "Error creating user record: " . $e->getMessage();
                        }
                    }
                    
                    if (!isset($error_message)) {
                        // Build INSERT query based on available columns
                        $insertColumns = [];
                        $insertValues = [];
                        $placeholders = [];
                        
                        // Add columns that exist
                        if ($hasName) {
                            $insertColumns[] = 'name';
                            $insertValues[] = $name;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasUserId && isset($userId)) {
                            $insertColumns[] = 'user_id';
                            $insertValues[] = $userId;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasStudentId) {
                            $insertColumns[] = 'student_id';
                            $insertValues[] = $student_id;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasEmail) {
                            $insertColumns[] = 'email';
                            $insertValues[] = $email;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasDepartment) {
                            $insertColumns[] = 'department';
                            $insertValues[] = $department;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasYear) {
                            $insertColumns[] = 'year';
                            $insertValues[] = $year;
                            $placeholders[] = '?';
                        } elseif ($hasLevel) {
                            $insertColumns[] = 'level';
                            $insertValues[] = $year;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasVotingCode) {
                            $insertColumns[] = 'voting_code';
                            $insertValues[] = $voting_code;
                            $placeholders[] = '?';
                        }
                        
                        if ($hasCreatedAt) {
                            $insertColumns[] = 'created_at';
                            $placeholders[] = 'NOW()';
                        }
                        
                        if (empty($insertColumns)) {
                            $error_message = "No valid columns found for insertion";
                        } else {
                            // Build and execute the query
                            $sql = "INSERT INTO students (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($insertValues);

                            // Save voting code to voting_codes table so student can login
                            if (isset($userId)) {
                                try {
                                    $adminId = $_SESSION['user_id'] ?? 1;
                                    // election_id is NOT NULL — use the first available election or default to 1
                                    $firstElection = $pdo->query("SELECT id FROM elections ORDER BY id ASC LIMIT 1")->fetchColumn();
                                    $electionIdForCode = $firstElection ?: 1;
                                    $vcStmt = $pdo->prepare("
                                        INSERT INTO voting_codes (student_id, election_id, voting_code, status, generated_by_admin, created_at)
                                        VALUES (?, ?, ?, 'sent', ?, NOW())
                                    ");
                                    $vcStmt->execute([$userId, $electionIdForCode, $voting_code, $adminId]);
                                } catch (Exception $vcEx) {
                                    error_log("Could not save voting code: " . $vcEx->getMessage());
                                }
                            }
                            
                            // Send email with voting code
                            $email_sent = sendVotingCodeEmail($email, $name, $student_id, $voting_code);

                            if ($email_sent) {
                                $success_message = "Voter registered successfully! Voting code sent to " . $email . " — Code: <strong>" . $voting_code . "</strong>";
                            } else {
                                $success_message = "Voter registered successfully! Give this voting code to the student: <strong>" . $voting_code . "</strong>";
                            }
                            
                            // Create notification for admin about successful registration
                            $notificationManager->createNotification(
                                'general',
                                'Voter Registration Complete',
                                "Voter {$name} ({$email}) has been successfully registered and voting code has been sent.",
                                null,
                                $userId ?? null,
                                'low',
                                false,
                                null,
                                null
                            );
                        }
                    }
                }
            } else {
                $error_message = "Database not available";
            }
        } catch (Exception $e) {
            $error_message = "Error registering voter: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'generate_code') {
        try {
            $student_id = $_POST['student_id'];
            $election_id = $_POST['election_id'] ?? 1; // Default to first election
            $admin_id = $_SESSION['admin_id'] ?? 1;
            
            // Debug: Log the attempt
            error_log("Attempting to generate voting code for student_id: $student_id, election_id: $election_id, admin_id: $admin_id");
            
            // WORKING FIX: Use the same logic as the working generator
            // Clear any existing '1' codes first
            $cleanup_stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
            $cleanup_stmt->execute(['1']);
            
            // Generate voting code using working logic
            do {
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $voting_code = 'VOTE-';
                for ($i = 0; $i < 16; $i++) {
                    if ($i === 4 || $i === 8 || $i === 12) $voting_code .= '-';
                    $voting_code .= $chars[rand(0, strlen($chars) - 1)];
                }
                
                // Double check it's not '1'
                if ($voting_code === '1') continue;
                
                // Check uniqueness
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
                $stmt->execute([$voting_code]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } while ($result['count'] > 0);
            
            // Insert with all required fields like the working generator
            $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at, status) 
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent')";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$student_id, $election_id, $voting_code, $admin_id]);
            
            // Debug: Log the result
            error_log("Direct voting code generation result: " . ($result ? "SUCCESS - $voting_code" : "FAILED"));
            
            if ($result) {
                $success_message = "Voting code generated successfully!";
                
                // Store in session for better display
                $_SESSION['generated_voting_code'] = [
                    'code' => $voting_code,
                    'student_id' => $student_id,
                    'generated_at' => date('Y-m-d H:i:s')
                ];
                
                // Get student details for display
                $stmt = $pdo->prepare("SELECT u.name, u.email, s.student_id FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
                $stmt->execute([$student_id]);
                $student_details = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $_SESSION['generated_voting_code']['student_details'] = $student_details;
                
                // Create notification for admin
                $notificationManager->createNotification(
                    'voting_code_required',
                    'Voting Code Generated',
                    "Voting code has been generated for student {$student_details['name']} ({$student_details['email']}). Please send the code to the student.",
                    null,
                    $student_id,
                    'high',
                    true,
                    "index.php?page=voter_registration&student_id={$student_id}",
                    'Send Voting Code'
                );
                
            } else {
                $error_message = "Failed to save voting code to database. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Exception in generate_code: " . $e->getMessage());
            $error_message = "Error generating voting code: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'send_voting_code') {
        try {
            $email = $_POST['send_email'];
            $voting_code = $_POST['send_voting_code'];
            $student_name = $_POST['student_name'];
            $student_id = $_POST['student_id_send'];
            $admin_id = $_SESSION['admin_id'] ?? 1;
            
            // Send voting code to student
            $sent = $notificationManager->sendVotingCodeToStudent($student_id, $voting_code, $admin_id);
            
            if ($sent) {
                $success_message = "Voting code sent successfully to " . $email;
            } else {
                $success_message = "Email delivery failed. Please check the voting code: " . $voting_code;
            }
        } catch (Exception $e) {
            $error_message = "Error sending voting code: " . $e->getMessage();
        }
    }
}

// Get all registered voters
$voters = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM students ORDER BY created_at DESC");
        $stmt->execute();
        $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading voters: " . $e->getMessage();
    }
}

// Get pending voting code requests
$pendingRequests = [];
if ($pdo) {
    try {
        $pendingRequests = $notificationManager->getPendingVotingCodeRequests();
    } catch (Exception $e) {
        $pendingRequests = [];
    }
}

function generateVotingCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'VOTE-';
    for ($i = 0; $i < 16; $i++) {
        if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

function sendVotingCodeEmail($email, $name, $student_id, $voting_code) {
    // Try to use SMTP first if configured
    if (file_exists('email_config.php')) {
        require_once 'email_config.php';
        
        $subject = "Your Voting Code - IUC Voting System";
        $message = "Dear $name,\n\nYour voter registration has been approved for the IUC Voting System.\n\nStudent ID: $student_id\nVoting Code: $voting_code\n\nPlease keep this voting code secure. You will need it to cast your vote.\n\nBest regards,\nIUC Voting System Administration";
        
        if (function_exists('sendEmailWithSMTP')) {
            $email_sent = sendEmailWithSMTP($email, $subject, $message, null);
            
            if ($email_sent) {
                return true;
            }
        }
    }
    
    // Fallback to local mail() function
    $subject = "Your Voting Code - IUC Voting System";
    $message = "
    Dear $name,
    
    Your voter registration has been approved for the IUC Voting System.
    
    Student ID: $student_id
    Voting Code: $voting_code
    
    Please keep this voting code secure. You will need it to cast your vote.
    
    Best regards,
    IUC Voting System Administration
    ";
    
    $headers = "From: admin@iuc.edu\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Try to send email, but handle failure gracefully (suppress warnings)
    $email_sent = @mail($email, $subject, $message, $headers);
    
    // Log the email attempt for debugging (silently)
    if (!$email_sent) {
        error_log("Email sending failed to: $email, Voting Code: $voting_code");
        
        // Store the failed email info for display
        $_SESSION['failed_email'] = [
            'email' => $email,
            'name' => $name,
            'student_id' => $student_id,
            'voting_code' => $voting_code,
            'message' => $message
        ];
    }
    
    return $email_sent;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #0B3C5D, #3282B8);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-item {
            display: block;
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .sidebar-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #BBE1FA;
        }
        
        .sidebar-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #BBE1FA;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .admin-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0B3C5D;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .content-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0B3C5D;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-new {
            display: grid;
            gap: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group-new {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group-new label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-control-new {
            padding: 0.8rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control-new:focus {
            outline: none;
            border-color: #3282B8;
            box-shadow: 0 0 0 3px rgba(50, 130, 184, 0.1);
        }
        
        .btn-new {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-new {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
        }
        
        .btn-primary-new:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.3);
        }
        
        .table-new {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table-new th,
        .table-new td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-new th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .table-new tbody tr:hover {
            background: #f8fafc;
        }
        
        .status-badge-new {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active-new {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive-new {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .voting-code {
            font-family: 'Courier New', monospace;
            background: #f1f5f9;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #0B3C5D;
        }
        
        .email-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
        }
        
        .email-sent {
            color: #10b981;
        }
        
        .email-failed {
            color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sidebar-title">Admin Panel</div>
                <div class="sidebar-subtitle">IUC Voting System</div>
            </div>
            
            <nav class="sidebar-menu">
                <!-- Dashboard -->
                <a href="index.php?page=admin" class="sidebar-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <!-- Election Management -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        ELECTION MANAGEMENT
                    </div>
                </div>
                <a href="index.php?page=elections" class="sidebar-item">
                    <i class="fas fa-vote-yea"></i>
                    Elections
                </a>
                <a href="index.php?page=results" class="sidebar-item">
                    <i class="fas fa-chart-bar"></i>
                    Results
                </a>
                
                <!-- Voter Management -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        VOTER MANAGEMENT
                    </div>
                </div>
                <a href="index.php?page=voter_registration" class="sidebar-item active">
                    <i class="fas fa-user-plus"></i>
                    Register Voters
                </a>
                <a href="index.php?page=voter_list" class="sidebar-item">
                    <i class="fas fa-users-cog"></i>
                    Voter List
                </a>
                
                <!-- Blockchain -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        BLOCKCHAIN
                    </div>
                </div>
                <a href="index.php?page=blockchain_explorer" class="sidebar-item">
                    <i class="fas fa-link"></i>
                    Blockchain Explorer
                </a>
                <a href="index.php?page=transaction_monitor" class="sidebar-item">
                    <i class="fas fa-exchange-alt"></i>
                    Transaction Monitor
                </a>
                <a href="index.php?page=node_status" class="sidebar-item">
                    <i class="fas fa-server"></i>
                    Node Status
                </a>
                
                <!-- Security -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        SECURITY
                    </div>
                </div>
                <a href="index.php?page=security_center" class="sidebar-item">
                    <i class="fas fa-shield-alt"></i>
                    Security Center
                </a>
                <a href="index.php?page=authentication_logs" class="sidebar-item">
                    <i class="fas fa-fingerprint"></i>
                    Authentication Logs
                </a>
                <a href="index.php?page=access_control" class="sidebar-item">
                    <i class="fas fa-lock"></i>
                    Access Control
                </a>
                
                <div style="margin-top: 2rem; padding: 0 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="index.php?page=admin_login" class="sidebar-item" style="color: #fbbf24;">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <div>
                    <h1 class="admin-title">Voter Registration</h1>
                    <p style="color: #64748b; margin: 0;">Register new voters and send voting codes via email</p>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['generated_voting_code'])): ?>
                <div style="background: linear-gradient(135deg, #1e293b, #334155); color: #10b981; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; border: 2px solid #10b981; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-key" style="font-size: 1.5rem; color: white;"></i>
                        </div>
                        <h3 style="margin: 0 0 0.5rem 0; font-weight: 700; color: #10b981; font-size: 1.5rem;">
                            Voting Code Generated Successfully!
                        </h3>
                        <p style="margin: 0; color: #94a3b8; font-size: 1rem;">
                            Share this code with the student for voting access
                        </p>
                    </div>
                    
                    <div style="background: #000; color: #10b981; padding: 1.5rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 1.4rem; text-align: center; margin: 1.5rem 0; letter-spacing: 3px; font-weight: bold; border: 1px solid #10b981;">
                        <?php echo htmlspecialchars($_SESSION['generated_voting_code']['code']); ?>
                    </div>
                    
                    <?php if (isset($_SESSION['generated_voting_code']['student_details'])): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; margin: 1.5rem 0; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <h4 style="margin: 0 0 0.5rem 0; color: #10b981; font-size: 0.9rem;">STUDENT DETAILS</h4>
                            <div style="font-size: 0.9rem; line-height: 1.6;">
                                <p style="margin: 0.25rem 0;"><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['generated_voting_code']['student_details']['name']); ?></p>
                                <p style="margin: 0.25rem 0;"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['generated_voting_code']['student_details']['email']); ?></p>
                                <p style="margin: 0.25rem 0;"><strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['generated_voting_code']['student_details']['student_id']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button onclick="copyVotingCode('<?php echo htmlspecialchars($_SESSION['generated_voting_code']['code']); ?>')" style="flex: 1; background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fas fa-copy"></i> Copy Code
                        </button>
                        <button onclick="window.print()" style="flex: 1; background: #64748b; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(16, 185, 129, 0.3);">
                        <p style="margin: 0; font-size: 0.85rem; color: #94a3b8; text-align: center;">
                            <i class="fas fa-info-circle"></i> Student can login at: <strong>index.php?page=student_login</strong> using this voting code
                        </p>
                    </div>
                </div>
                
                <script>
                function copyVotingCode(code) {
                    navigator.clipboard.writeText(code).then(function() {
                        alert('Voting code copied to clipboard!');
                    }, function(err) {
                        console.error('Could not copy text: ', err);
                        alert('Failed to copy voting code');
                    });
                }
                </script>
                
                <?php unset($_SESSION['generated_voting_code']); ?>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fecaca;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['failed_email'])): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
                    <h4 style="margin: 0 0 0.5rem 0; font-weight: 600;">
                        <i class="fas fa-check-circle"></i>
                        Voter Registered Successfully - Voting Code Generated
                    </h4>
                    <p style="margin: 0.5rem 0;">Please provide this voting code to the student:</p>
                    <div style="background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 1.1rem; margin: 0.5rem 0;">
                        <?php echo htmlspecialchars($_SESSION['failed_email']['voting_code']); ?>
                    </div>
                    <div style="font-size: 0.9rem; margin-top: 0.5rem;">
                        <strong>Student:</strong> <?php echo htmlspecialchars($_SESSION['failed_email']['name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['failed_email']['email']); ?><br>
                        <strong>Student ID:</strong> <?php echo htmlspecialchars($_SESSION['failed_email']['student_id']); ?>
                    </div>
                </div>
                <?php unset($_SESSION['failed_email']); ?>
            <?php endif; ?>
            
            <!-- Preselected Student from Notification -->
            <?php if ($preselected_student): ?>
                <div class="content-card" style="border-left: 4px solid #10b981;">
                    <div class="content-header">
                        <h2 class="content-title">
                            <i class="fas fa-user-check" style="color: #10b981;"></i>
                            Generate Voting Code for Selected Student
                        </h2>
                    </div>
                    
                    <div style="background: #f0fdf4; border: 1px solid #10b981; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">
                        <h3 style="margin: 0 0 1rem 0; color: #059669;">Student Information</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($preselected_student['name']); ?>
                            </div>
                            <div>
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($preselected_student['email']); ?>
                            </div>
                            <div>
                                <strong>Student ID:</strong><br>
                                <?php echo htmlspecialchars($preselected_student['student_id']); ?>
                            </div>
                            <div>
                                <strong>Department:</strong><br>
                                <?php echo htmlspecialchars($preselected_student['department']); ?>
                            </div>
                            <div>
                                <strong>Level:</strong><br>
                                <?php echo htmlspecialchars($preselected_student['level']); ?>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #10b981;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="generate_code">
                                <input type="hidden" name="student_id" value="<?php echo $preselected_student['id']; ?>">
                                <input type="hidden" name="election_id" value="1">
                                <button type="submit" class="btn-new" style="background: #10b981; color: white; padding: 0.75rem 1.5rem; font-size: 1rem; border-radius: 6px;">
                                    <i class="fas fa-key"></i> Generate Voting Code
                                </button>
                            </form>
                            <p style="margin: 1rem 0 0 0; font-size: 0.9rem; color: #059669;">
                                <i class="fas fa-info-circle"></i> Click "Generate Voting Code" to create a unique voting code for this student.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Pending Voting Code Requests -->
            <?php if (count($pendingRequests) > 0): ?>
                <div class="content-card" style="border-left: 4px solid #f59e0b;">
                    <div class="content-header">
                        <h2 class="content-title">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                            Pending Voting Code Requests
                            <span style="background: #f59e0b; color: white; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; margin-left: 0.5rem;">
                                <?php echo count($pendingRequests); ?> New
                            </span>
                        </h2>
                    </div>
                    
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($pendingRequests as $request): ?>
                            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <div>
                                        <h4 style="margin: 0; color: #92400e; font-size: 1rem;">
                                            <?php echo htmlspecialchars($request['student_name']); ?>
                                        </h4>
                                        <p style="margin: 0.25rem 0; color: #64748b; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($request['student_email']); ?>
                                        </p>
                                        <p style="margin: 0; color: #94a3b8; font-size: 0.8rem;">
                                            Registered: <?php echo date('M d, H:i', strtotime($request['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="generate_code">
                                            <input type="hidden" name="student_id" value="<?php echo $request['related_student_id']; ?>">
                                            <button type="submit" class="btn-new" style="background: #10b981; color: white; padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                <i class="fas fa-key"></i> Generate Code
                                            </button>
                                        </form>
                                        <button class="btn-new" style="background: #ef4444; color: white; padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="dismissNotification(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-times"></i> Dismiss
                                        </button>
                                    </div>
                                </div>
                                <div style="background: white; padding: 0.5rem; border-radius: 4px; font-size: 0.8rem; color: #64748b;">
                                    <i class="fas fa-info-circle"></i> This student has registered and needs a voting code to participate in elections.
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Voter Registration Form -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-user-plus"></i>
                        Register New Voter
                    </h2>
                </div>
                
                <form method="POST" class="form-new">
                    <input type="hidden" name="action" value="register_voter">
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required 
                                   class="form-control-new"
                                   placeholder="Enter student full name">
                        </div>
                        
                        <div class="form-group-new">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   class="form-control-new"
                                   placeholder="student@iuc.edu">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="student_id">Student ID</label>
                            <input type="text" id="student_id" name="student_id" required 
                                   class="form-control-new"
                                   placeholder="e.g., 2024001">
                        </div>
                        
                        <div class="form-group-new">
                            <label for="department">Department</label>
                            <select id="department" name="department" required class="form-control-new">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Social Sciences">Social Sciences</option>
                                <option value="Natural Sciences">Natural Sciences</option>
                                <option value="Arts & Humanities">Arts & Humanities</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="year">Academic Year</label>
                            <select id="year" name="year" required class="form-control-new">
                                <option value="">Select Year</option>
                                <option value="1">Year 1</option>
                                <option value="2">Year 2</option>
                                <option value="3">Year 3</option>
                                <option value="4">Year 4</option>
                                <option value="5">Year 5</option>
                            </select>
                        </div>
                        
                        <div class="form-group-new">
                            <label for="voting_code">Voting Code (Optional)</label>
                            <input type="text" id="voting_code" name="voting_code" 
                                   class="form-control-new"
                                   placeholder="Enter custom voting code or leave blank to auto-generate">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-new btn-primary-new">
                                <i class="fas fa-user-plus"></i>
                                Register Voter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Send Voting Code Form -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-envelope"></i>
                        Send Voting Code
                    </h2>
                </div>
                
                <form method="POST" class="form-new">
                    <input type="hidden" name="action" value="send_voting_code">
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="send_email">Student Email</label>
                            <input type="email" id="send_email" name="send_email" required 
                                   class="form-control-new"
                                   placeholder="student@iuc.edu">
                        </div>
                        
                        <div class="form-group-new">
                            <label for="send_voting_code">Voting Code</label>
                            <input type="text" id="send_voting_code" name="send_voting_code" required 
                                   class="form-control-new"
                                   placeholder="Enter voting code to send">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="student_name">Student Name</label>
                            <input type="text" id="student_name" name="student_name" required 
                                   class="form-control-new"
                                   placeholder="Student full name">
                        </div>
                        
                        <div class="form-group-new">
                            <label for="student_id_send">Student ID</label>
                            <input type="text" id="student_id_send" name="student_id_send" required 
                                   class="form-control-new"
                                   placeholder="Student ID">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-new btn-primary-new">
                                <i class="fas fa-envelope"></i>
                                Send Voting Code
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Registered Voters List -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-users"></i>
                        Registered Voters
                    </h2>
                    <span style="color: #64748b; font-size: 0.9rem;">
                        Total: <?php echo count($voters); ?> voters
                    </span>
                </div>
                
                <?php if (empty($voters)): ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>No Voters Registered Yet</h3>
                        <p>Register your first voter using the form above.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table-new">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Student ID</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Voting Code</th>
                                    <th>Status</th>
                                    <th>Email Status</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($voters as $voter): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($voter['name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($voter['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($voter['student_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($voter['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($voter['year'] ?? $voter['level'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="voting-code">
                                                <?php echo htmlspecialchars($voter['voting_code'] ?? 'Not Generated'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge-new status-<?php echo $voter['status'] ?? 'active'; ?>-new">
                                                <?php echo ucfirst($voter['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="email-status email-sent">
                                                <i class="fas fa-check-circle"></i>
                                                Sent
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($voter['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('active');
        }
        
        // Add mobile menu button if needed
        if (window.innerWidth <= 768) {
            const header = document.querySelector('.admin-header');
            const menuBtn = document.createElement('button');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            menuBtn.style.cssText = 'background: #3282B8; color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer;';
            menuBtn.onclick = toggleSidebar;
            header.insertBefore(menuBtn, header.firstChild);
        }
    </script>
</body>
</html>
