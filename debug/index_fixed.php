<?php
/**
 * IUC Voting System - Main Router
 * Clean version with profile route fixed
 */

session_start();

// Database connection
require_once 'config/config.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);

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
    case 'profile':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'pages/profile.php';
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
    case 'logout':
        // Handle logout
        session_destroy();
        header('Location: index.php?page=home');
        exit;
        break;
    default:
        // Load home page
        require_once 'pages/home.php';
        break;
}
?>
