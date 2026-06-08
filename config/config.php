<?php
/**
 * IUC Voting System - Configuration
 * Database and system settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'iuc_voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Blockchain configuration
define('BLOCKCHAIN_NODE_URL', 'http://localhost:8545');
define('SMART_CONTRACT_ADDRESS', '0x1234567890123456789012345678901234567890');

// System configuration
define('SYSTEM_NAME', 'IUC Voting System');
define('SYSTEM_VERSION', '1.0.0');
define('ADMIN_EMAIL', 'admin@iuc.edu');

// Security configuration
define('JWT_SECRET', 'your-secret-key-here');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 3);

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('LOG_PATH', ROOT_PATH . '/logs/');

// Blockchain settings
define('BLOCKCHAIN_ENABLED', false);
define('TRANSACTION_CONFIRMATIONS', 3);
define('GAS_LIMIT', 21000);

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'error.log');

// Timezone
date_default_timezone_set('Africa/Douala');

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Don't die, just set pdo to null for graceful handling
    $pdo = null;
    error_log("Database connection failed: " . $e->getMessage());
}

// Initialize blockchain connection (optional)
$blockchain = null;
if (BLOCKCHAIN_ENABLED && file_exists(ROOT_PATH . '/includes/blockchain.php')) {
    try {
        require_once ROOT_PATH . '/includes/blockchain.php';
        $blockchain = new BlockchainConnector();
    } catch (Exception $e) {
        error_log("Blockchain connection failed: " . $e->getMessage());
    }
}

// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function logActivity($userId, $action, $details = '') {
    $logFile = LOG_PATH . 'activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] User: $userId | Action: $action | Details: $details\n";
    
    // Create log directory if it doesn't exist
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    // Suppress file_put_contents warnings
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>
