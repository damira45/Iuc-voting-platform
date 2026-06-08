<?php
/**
 * IUC Voting System - Main Entry Point
 * Blockchain-based secure voting platform
 */

session_start();

// Check if user is logged in
$loggedIn  = !empty($_SESSION['user_id']);
$userType  = $_SESSION['user_type'] ?? null;
$isAdmin   = $loggedIn && $userType === 'admin';
$isStudent = $loggedIn && $userType === 'student';

// Include configuration
require_once 'config/config.php';

// Helper: require admin or redirect to login
function requireAdmin() {
    global $isAdmin;
    if (!$isAdmin) {
        header('Location: index.php?page=admin_login');
        exit;
    }
}

// Helper: require any logged-in user
function requireLogin() {
    global $loggedIn;
    if (!$loggedIn) {
        header('Location: index.php?page=student_login');
        exit;
    }
}

// Route requests
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        require_once 'pages/home.php';
        break;

    case 'login':
        header('Location: index.php?page=student_login' . (isset($_GET['force']) ? '&force=1' : ''));
        exit;

    case 'register':
        // Registration is admin-only — redirect to login
        header('Location: index.php?page=student_login');
        exit;

    case 'student_login':
        require_once 'pages/student_login.php';
        break;

    case 'dashboard':
        requireLogin();
        if ($isAdmin) {
            require_once 'pages/dashboard.php';
        } else {
            require_once 'pages/student_dashboard.php';
        }
        break;

    case 'elections':
        requireAdmin();
        require_once 'pages/elections.php';
        break;

    case 'voting':
        requireLogin();
        if ($isStudent) {
            require_once 'pages/simple_voting.php';
        } else {
            require_once 'pages/voting.php';
        }
        break;

    case 'simple_voting':
        requireLogin();
        require_once 'pages/simple_voting.php';
        break;

    case 'voting_receipt':
        requireLogin();
        require_once 'pages/voting_receipt.php';
        break;

    case 'admin':
        requireAdmin();
        require_once 'pages/admin.php';
        break;

    case 'admin_login':
        // If already logged in as admin, go straight to dashboard
        if ($isAdmin) {
            header('Location: index.php?page=admin');
            exit;
        }
        require_once 'pages/admin_login.php';
        break;

    case 'about':
        require_once 'pages/about.php';
        break;

    case 'services':
        require_once 'pages/services.php';
        break;

    case 'contact':
        require_once 'pages/contact.php';
        break;

    case 'results':
        requireLogin();
        if ($isAdmin) {
            require_once 'pages/results.php';
        } else {
            require_once 'pages/student_results.php';
        }
        break;

    case 'voter_registration':
        requireAdmin();
        require_once 'pages/voter_registration.php';
        break;

    case 'voter_list':
        requireAdmin();
        require_once 'pages/voter_list.php';
        break;

    case 'blockchain_explorer':
        requireAdmin();
        require_once 'pages/blockchain_explorer.php';
        break;

    case 'transaction_monitor':
        requireAdmin();
        require_once 'pages/transaction_monitor.php';
        break;

    case 'node_status':
        requireAdmin();
        require_once 'pages/node_status.php';
        break;

    case 'security_center':
        requireAdmin();
        require_once 'pages/security_center.php';
        break;

    case 'authentication_logs':
        requireAdmin();
        require_once 'pages/authentication_logs.php';
        break;

    case 'access_control':
        requireAdmin();
        require_once 'pages/access_control.php';
        break;

    case 'realtime_voting':
        requireAdmin();
        require_once 'pages/realtime_voting.php';
        break;

    case 'participants':
        requireAdmin();
        require_once 'pages/participants.php';
        break;

    case 'audit_logs':
        requireAdmin();
        require_once 'pages/audit_logs.php';
        break;

    case 'verify':
        requireLogin();
        require_once 'pages/verify.php';
        break;

    case 'vote_verification':
        requireAdmin();
        require_once 'pages/vote_verification.php';
        break;

    case 'compliance_report':
        requireAdmin();
        require_once 'pages/compliance_report.php';
        break;

    case 'settings':
        requireAdmin();
        require_once 'pages/settings.php';
        break;

    case 'report':
        requireAdmin();
        require_once 'pages/report.php';
        break;

    case 'backup':
        requireAdmin();
        require_once 'pages/backup.php';
        break;

    case 'complain':
        requireAdmin();
        require_once 'pages/complain.php';
        break;

    case 'logout':
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        header('Location: index.php?page=home');
        exit;

    case 'dismiss_notification':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
            require_once 'includes/NotificationManager.php';
            $notificationManager = new NotificationManager($pdo);
            $success = $notificationManager->dismissNotification($_POST['notification_id']);
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }
        break;

    case 'profile':
        requireLogin();
        require_once 'pages/profile.php';
        break;

    default:
        require_once 'pages/home.php';
        break;
}
?>
