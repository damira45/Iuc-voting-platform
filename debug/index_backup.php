<?php
/**
 * IUC Voting System - Main Entry Point (Backup)
 * Blockchain-based secure voting platform
 */

session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Database connection
require_once 'config/config.php';

// Route requests
$action = $_GET['action'] ?? 'home';
$page = $_GET['page'] ?? 'home';

// Handle different page requests
switch($page) {
    case 'home':
        require_once 'pages/home.php';
        break;
    case 'login':
        // Redirect to student login form instead of old email/password login
        header('Location: index.php?page=student_login' . (isset($_GET['force']) ? '&force=1' : ''));
        exit;
        break;
    case 'register':
        require_once 'pages/register.php';
        break;
    case 'student_login':
        require_once 'pages/student_login.php';
        break;
    case 'dashboard':
        if (!$loggedIn) {
            header('Location: index.php?page=login');
            exit;
        }
        // Route to appropriate dashboard based on user type
        if ($_SESSION['user_type'] === 'admin') {
            require_once 'pages/dashboard.php';
        } else {
            require_once 'pages/student_dashboard.php';
        }
        break;
    case 'elections':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/elections.php';
        break;
    case 'voting':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        // Route to simple voting interface for students
        if ($_SESSION['user_type'] === 'student') {
            require_once 'pages/simple_voting.php';
        } else {
            require_once 'pages/voting.php';
        }
        break;
    case 'simple_voting':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'pages/simple_voting.php';
        break;
    case 'voting_receipt':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'pages/voting_receipt.php';
        break;
    case 'results':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        // Route to appropriate results page based on user type
        if ($_SESSION['user_type'] === 'admin') {
            require_once 'pages/results.php';
        } else {
            require_once 'pages/student_results.php';
        }
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
    case 'voter_registration':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/voter_registration.php';
        break;
    case 'voter_list':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/voter_list.php';
        break;
    case 'blockchain_explorer':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/blockchain_explorer.php';
        break;
    case 'transaction_monitor':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/transaction_monitor.php';
        break;
    case 'node_status':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/node_status.php';
        break;
    case 'security_center':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/security_center.php';
        break;
    case 'authentication_logs':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/authentication_logs.php';
        break;
    case 'access_control':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/access_control.php';
        break;
    case 'realtime_voting':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/realtime_voting.php';
        break;
    case 'participants':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/participants.php';
        break;
    case 'audit_logs':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/audit_logs.php';
        break;
    case 'vote_verification':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/vote_verification.php';
        break;
    case 'compliance_report':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/compliance_report.php';
        break;
    case 'settings':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/settings.php';
        break;
    case 'report':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/report.php';
        break;
    case 'backup':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/backup.php';
        break;
    case 'complain':
        // Check admin session
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            header('Location: index.php?page=admin_login');
            exit;
        }
        $userType = $_SESSION['user_type'] ?? '';
        if ($userType !== 'admin') {
            header('Location: index.php?page=admin_login');
            exit;
        }
        require_once 'pages/complain.php';
        break;
    case 'logout':
        // Handle logout
        session_destroy();
        header('Location: index.php?page=home');
        exit;
        break;
    case 'dismiss_notification':
        // Handle AJAX notification dismissal
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
            require_once 'config/config.php';
            require_once 'includes/NotificationManager.php';
            
            $notificationManager = new NotificationManager($pdo);
            $success = $notificationManager->dismissNotification($_POST['notification_id']);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }
        break;
    case 'profile':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'pages/profile.php';
        break;
    default:
        // Load home page
        require_once 'pages/home.php';
        break;
}
?>
