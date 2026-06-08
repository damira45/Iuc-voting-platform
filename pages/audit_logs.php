<?php
/**
 * IUC Voting System - Audit Logs Page
 * Comprehensive audit trail, system logs, and compliance monitoring
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

// Generate audit log data
$audit_logs = [];
$log_categories = ['SYSTEM', 'SECURITY', 'VOTING', 'ADMIN', 'BLOCKCHAIN', 'USER_MANAGEMENT', 'ELECTION', 'COMPLIANCE'];
$log_levels = ['INFO', 'WARNING', 'ERROR', 'CRITICAL'];
$log_actions = [
    'SYSTEM' => ['System Startup', 'System Shutdown', 'Configuration Change', 'Backup Created', 'Backup Restored', 'Database Maintenance'],
    'SECURITY' => ['Login Attempt', 'Login Failed', 'Password Change', '2FA Enabled', '2FA Disabled', 'Security Scan', 'Threat Detected'],
    'VOTING' => ['Vote Cast', 'Vote Verified', 'Receipt Generated', 'Blockchain Confirmed', 'Vote Rejected', 'Manual Override'],
    'ADMIN' => ['User Created', 'User Modified', 'User Deleted', 'Permission Changed', 'Role Assigned', 'Settings Updated'],
    'BLOCKCHAIN' => ['Block Mined', 'Transaction Confirmed', 'Chain Reorganized', 'Node Synchronized', 'Consensus Reached'],
    'USER_MANAGEMENT' => ['Voter Registered', 'Voter Verified', 'Voter Suspended', 'Voter Reactivated', 'Profile Updated'],
    'ELECTION' => ['Election Created', 'Election Started', 'Election Ended', 'Results Published', 'Election Modified'],
    'COMPLIANCE' => ['Audit Report Generated', 'Compliance Check', 'Regulation Verified', 'Data Export', 'Log Archived']
];

// Generate audit logs
for ($i = 1; $i <= 100; $i++) {
    $category = $log_categories[array_rand($log_categories)];
    $level = $log_levels[array_rand($log_levels)];
    $action = $log_actions[$category][array_rand($log_actions[$category])];
    
    $audit_logs[] = [
        'id' => 'AUDIT-' . str_pad($i, 6, '0', STR_PAD_LEFT),
        'timestamp' => date('Y-m-d H:i:s', time() - ($i * 300)),
        'category' => $category,
        'level' => $level,
        'action' => $action,
        'user' => $category === 'SYSTEM' ? 'System' : 'admin_' . rand(1, 5),
        'ip_address' => rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255),
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'description' => generateLogDescription($category, $action),
        'affected_resource' => generateAffectedResource($category),
        'session_id' => 'sess_' . substr(md5($i), 0, 16),
        'request_id' => 'REQ-' . str_pad($i, 8, '0', STR_PAD_LEFT),
        'duration_ms' => rand(10, 5000),
        'status_code' => rand(200, 500),
        'details' => generateLogDetails($category, $action)
    ];
}

// Sort logs by timestamp (newest first)
usort($audit_logs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Audit statistics
$audit_stats = [
    'total_logs' => count($audit_logs),
    'critical_logs' => count(array_filter($audit_logs, function($log) { return $log['level'] === 'CRITICAL'; })),
    'error_logs' => count(array_filter($audit_logs, function($log) { return $log['level'] === 'ERROR'; })),
    'warning_logs' => count(array_filter($audit_logs, function($log) { return $log['level'] === 'WARNING'; })),
    'info_logs' => count(array_filter($audit_logs, function($log) { return $log['level'] === 'INFO'; })),
    'system_logs' => count(array_filter($audit_logs, function($log) { return $log['category'] === 'SYSTEM'; })),
    'security_logs' => count(array_filter($audit_logs, function($log) { return $log['category'] === 'SECURITY'; })),
    'voting_logs' => count(array_filter($audit_logs, function($log) { return $log['category'] === 'VOTING'; })),
    'unique_users' => count(array_unique(array_column($audit_logs, 'user')))
];

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$level_filter = $_GET['level'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Filter logs
if (!empty($search) || !empty($category_filter) || !empty($level_filter) || !empty($date_filter)) {
    $filtered_logs = [];
    foreach ($audit_logs as $log) {
        $match = true;
        
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $match = (
                strpos(strtolower($log['id']), $search_lower) !== false ||
                strpos(strtolower($log['action']), $search_lower) !== false ||
                strpos(strtolower($log['description']), $search_lower) !== false ||
                strpos(strtolower($log['user']), $search_lower) !== false
            );
        }
        
        if (!empty($category_filter) && $log['category'] !== $category_filter) {
            $match = false;
        }
        
        if (!empty($level_filter) && $log['level'] !== $level_filter) {
            $match = false;
        }
        
        if (!empty($date_filter)) {
            $log_date = date('Y-m-d', strtotime($log['timestamp']));
            if ($log_date !== $date_filter) {
                $match = false;
            }
        }
        
        if ($match) {
            $filtered_logs[] = $log;
        }
    }
    $audit_logs = $filtered_logs;
}

// Helper functions
function generateLogDescription($category, $action) {
    $descriptions = [
        'SYSTEM' => [
            'System Startup' => 'IUC Voting System started successfully on server node',
            'System Shutdown' => 'IUC Voting System shutdown initiated by administrator',
            'Configuration Change' => 'System configuration updated with new parameters',
            'Backup Created' => 'System backup completed successfully',
            'Backup Restored' => 'System backup restored from previous version'
        ],
        'SECURITY' => [
            'Login Attempt' => 'User attempted to access the system',
            'Login Failed' => 'Failed login attempt due to invalid credentials',
            'Password Change' => 'User password changed successfully',
            '2FA Enabled' => 'Two-factor authentication enabled for user account',
            '2FA Disabled' => 'Two-factor authentication disabled for user account'
        ],
        'VOTING' => [
            'Vote Cast' => 'Vote successfully cast and recorded on blockchain',
            'Vote Verified' => 'Vote verification completed successfully',
            'Receipt Generated' => 'Voting receipt generated for voter',
            'Blockchain Confirmed' => 'Vote transaction confirmed on blockchain',
            'Vote Rejected' => 'Vote rejected due to validation failure'
        ]
    ];
    
    return $descriptions[$category][$action] ?? $action . ' operation completed';
}

function generateAffectedResource($category) {
    $resources = [
        'SYSTEM' => ['System Configuration', 'Database', 'Backup Files', 'Server Resources'],
        'SECURITY' => ['User Account', 'Authentication System', 'Security Policy', 'Access Control'],
        'VOTING' => ['Vote Transaction', 'Blockchain', 'Voting Receipt', 'Election Data'],
        'ADMIN' => ['User Account', 'Role Assignment', 'System Settings', 'Permission Set'],
        'BLOCKCHAIN' => ['Block', 'Transaction', 'Node', 'Chain State'],
        'USER_MANAGEMENT' => ['Voter Profile', 'Registration Data', 'Verification Status', 'Account Status'],
        'ELECTION' => ['Election Configuration', 'Candidate Data', 'Results Data', 'Voting Period'],
        'COMPLIANCE' => ['Audit Report', 'Compliance Check', 'Regulation Data', 'Export File']
    ];
    
    return $resources[$category][array_rand($resources[$category])] ?? 'System Resource';
}

function generateLogDetails($category, $action) {
    $details = [
        'SYSTEM' => [
            'System Startup' => ['Server: web-01', 'Version: 2.1.3', 'Uptime: 99.8%', 'Memory: 4.2GB/8GB'],
            'Configuration Change' => ['Setting: session_timeout', 'Old: 30min', 'New: 45min', 'Applied: Yes']
        ],
        'SECURITY' => [
            'Login Attempt' => ['IP: 192.168.1.100', 'Method: Password', '2FA: No', 'Success: Yes'],
            'Password Change' => ['User: admin_1', 'Method: Web', 'Complexity: Strong', '2FA: Required']
        ],
        'VOTING' => [
            'Vote Cast' => ['Voter: STU1234', 'Election: Student Council 2024', 'Candidate: Alice Johnson', 'Block: #1045'],
            'Vote Verified' => ['Receipt: VOTE-ABCD-1234', 'Transaction: 0x123...', 'Confirmations: 12', 'Status: Valid']
        ]
    ];
    
    return $details[$category][$action] ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }

        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-subtitle {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.7;
            letter-spacing: 0.05em;
            margin: 0 1.5rem 0.5rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.25rem 0.5rem;
            border-radius: 8px;
        }

        .sidebar-item:hover {
            background: rgba(255,255,255,0.1);
            color: #f1f5f9;
            transform: translateX(4px);
        }

        .sidebar-item.active {
            background: rgba(50, 130, 184, 0.2);
            color: #60a5fa;
            border-left: 3px solid #60a5fa;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1rem;
            height: 100vh;
            overflow-y: auto;
        }

        .page-header {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.9rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: white;
            padding: 0.75rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-critical { border-left: 4px solid #dc2626; }
        .stat-error { border-left: 4px solid #ef4444; }
        .stat-warning { border-left: 4px solid #f59e0b; }
        .stat-info { border-left: 4px solid #3b82f6; }
        .stat-success { border-left: 4px solid #10b981; }

        .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .stat-critical .stat-number { color: #dc2626; }
        .stat-error .stat-number { color: #ef4444; }
        .stat-warning .stat-number { color: #f59e0b; }
        .stat-info .stat-number { color: #3b82f6; }
        .stat-success .stat-number { color: #10b981; }

        .stat-label {
            color: #64748b;
            font-size: 0.75rem;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }

        .audit-table th {
            background: #f8fafc;
            padding: 0.5rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
        }

        .audit-table td {
            padding: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8rem;
        }

        .audit-table tr:hover {
            background: #f8fafc;
        }

        .level-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .level-critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .level-error {
            background: #fecaca;
            color: #dc2626;
        }

        .level-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .level-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .category-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
        }

        .search-filter-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            padding: 0.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
        }

        .filter-select {
            min-width: 120px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .audit-details {
            background: #f8fafc;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: #64748b;
            display: none;
        }

        .audit-details.show {
            display: block;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #64748b;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #dcfce7;
            color: #166534;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .search-filter-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">
                    <i class="fas fa-vote-yea"></i>
                    IUC Voting System
                </h1>
                <p class="sidebar-subtitle">Admin Panel</p>
            </div>
            
            <!-- Navigation -->
            <nav class="sidebar-nav">
                <!-- Dashboard -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">MAIN</div>
                    <a href="index.php?page=admin" class="sidebar-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                
                <!-- Election Management -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">ELECTION MANAGEMENT</div>
                    <a href="index.php?page=elections" class="sidebar-item">
                        <i class="fas fa-poll"></i>
                        Elections
                    </a>
                    <a href="index.php?page=results" class="sidebar-item">
                        <i class="fas fa-chart-bar"></i>
                        Results
                    </a>
                </div>
                
                <!-- Voter Management -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">VOTER MANAGEMENT</div>
                    <a href="index.php?page=voter_registration" class="sidebar-item">
                        <i class="fas fa-user-plus"></i>
                        Register Voters
                    </a>
                    <a href="index.php?page=voter_list" class="sidebar-item">
                        <i class="fas fa-users-cog"></i>
                        Voter List
                    </a>
                </div>
                
                <!-- Blockchain -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">BLOCKCHAIN</div>
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
                </div>
                
                <!-- Security -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">SECURITY</div>
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
                </div>
                
                <!-- Analytics -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">ANALYTICS</div>
                    <a href="index.php?page=realtime_voting" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Real-time Voting
                    </a>
                    <a href="index.php?page=participants" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Participants
                    </a>
                </div>
                
                <!-- Audit -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">AUDIT</div>
                    <a href="index.php?page=audit_logs" class="sidebar-item active">
                        <i class="fas fa-history"></i>
                        Audit Logs
                    </a>
                    <a href="index.php?page=vote_verification" class="sidebar-item">
                        <i class="fas fa-check-circle"></i>
                        Vote Verification
                    </a>
                    <a href="index.php?page=compliance_report" class="sidebar-item">
                        <i class="fas fa-file-alt"></i>
                        Compliance Report
                    </a>
                </div>
                
                <!-- Logout -->
                <div class="sidebar-section">
                    <a href="index.php?page=logout" class="sidebar-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 class="page-title">Audit Logs</h1>
                        <p class="page-subtitle">Comprehensive audit trail and system monitoring</p>
                    </div>
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        <span>LIVE</span>
                    </div>
                </div>
            </div>
            
            <!-- Audit Statistics -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Audit Statistics
                    </h2>
                    <div>
                        <button class="btn btn-success">
                            <i class="fas fa-download"></i>
                            Export Logs
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card stat-info">
                        <div class="stat-number"><?php echo $audit_stats['total_logs']; ?></div>
                        <div class="stat-label">Total Logs</div>
                    </div>
                    <div class="stat-card stat-critical">
                        <div class="stat-number"><?php echo $audit_stats['critical_logs']; ?></div>
                        <div class="stat-label">Critical</div>
                    </div>
                    <div class="stat-card stat-error">
                        <div class="stat-number"><?php echo $audit_stats['error_logs']; ?></div>
                        <div class="stat-label">Errors</div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-number"><?php echo $audit_stats['warning_logs']; ?></div>
                        <div class="stat-label">Warnings</div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-number"><?php echo $audit_stats['system_logs']; ?></div>
                        <div class="stat-label">System</div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-number"><?php echo $audit_stats['security_logs']; ?></div>
                        <div class="stat-label">Security</div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-number"><?php echo $audit_stats['voting_logs']; ?></div>
                        <div class="stat-label">Voting</div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-number"><?php echo $audit_stats['unique_users']; ?></div>
                        <div class="stat-label">Unique Users</div>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-search"></i>
                        Search & Filter
                    </h2>
                </div>
                
                <form method="GET" class="search-filter-bar">
                    <input type="text" name="search" class="search-input" placeholder="Search by ID, action, user, or description..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($log_categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>><?php echo $category; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="level" class="filter-select">
                        <option value="">All Levels</option>
                        <?php foreach ($log_levels as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo $level_filter === $level ? 'selected' : ''; ?>><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="date" class="filter-select" value="<?php echo htmlspecialchars($date_filter); ?>">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    
                    <a href="index.php?page=audit_logs" class="btn btn-primary">
                        <i class="fas fa-redo"></i>
                        Clear
                    </a>
                </form>
            </div>
            
            <!-- Audit Logs -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history"></i>
                        Audit Trail
                        <span style="font-size: 0.9rem; color: #64748b; margin-left: 0.5rem;">
                            (<?php echo count($audit_logs); ?> logs)
                        </span>
                    </h2>
                </div>
                
                <?php if (count($audit_logs) > 0): ?>
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Timestamp</th>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Resource</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo date('M d, H:i:s', strtotime($log['timestamp'])); ?></td>
                                    <td><span class="category-badge"><?php echo $log['category']; ?></span></td>
                                    <td>
                                        <span class="level-badge level-<?php echo strtolower($log['level']); ?>">
                                            <?php echo $log['level']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $log['action']; ?></td>
                                    <td><?php echo $log['user']; ?></td>
                                    <td><?php echo $log['ip_address']; ?></td>
                                    <td><?php echo $log['affected_resource']; ?></td>
                                    <td><?php echo $log['duration_ms']; ?>ms</td>
                                    <td><?php echo $log['status_code']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="toggleAuditDetails('<?php echo $log['id']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="details-<?php echo $log['id']; ?>" style="display: none;">
                                    <td colspan="11">
                                        <div class="audit-details show">
                                            <h4 style="margin-bottom: 0.5rem; color: #0B3C5D;">Audit Log Details</h4>
                                            <div class="detail-row">
                                                <span class="detail-label">Log ID:</span>
                                                <span class="detail-value"><?php echo $log['id']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Timestamp:</span>
                                                <span class="detail-value"><?php echo $log['timestamp']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Category:</span>
                                                <span class="detail-value"><?php echo $log['category']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Level:</span>
                                                <span class="detail-value"><?php echo $log['level']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Action:</span>
                                                <span class="detail-value"><?php echo $log['action']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">User:</span>
                                                <span class="detail-value"><?php echo $log['user']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">IP Address:</span>
                                                <span class="detail-value"><?php echo $log['ip_address']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">User Agent:</span>
                                                <span class="detail-value"><?php echo $log['user_agent']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Description:</span>
                                                <span class="detail-value"><?php echo $log['description']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Affected Resource:</span>
                                                <span class="detail-value"><?php echo $log['affected_resource']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Session ID:</span>
                                                <span class="detail-value"><?php echo $log['session_id']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Request ID:</span>
                                                <span class="detail-value"><?php echo $log['request_id']; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Duration:</span>
                                                <span class="detail-value"><?php echo $log['duration_ms']; ?>ms</span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Status Code:</span>
                                                <span class="detail-value"><?php echo $log['status_code']; ?></span>
                                            </div>
                                            
                                            <?php if (!empty($log['details'])): ?>
                                                <h4 style="margin-top: 0.5rem; margin-bottom: 0.25rem; color: #0B3C5D;">Additional Details</h4>
                                                <?php foreach ($log['details'] as $key => $value): ?>
                                                    <div class="detail-row">
                                                        <span class="detail-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                                                        <span class="detail-value"><?php echo $value; ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No audit logs found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function toggleAuditDetails(logId) {
            const detailsRow = document.getElementById('details-' + logId);
            if (detailsRow) {
                detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            console.log('Refreshing audit logs...');
            // In production, this would fetch fresh audit data
        }, 30000);
        
        // Simulate live updates
        setInterval(function() {
            const liveIndicator = document.querySelector('.live-dot');
            if (liveIndicator) {
                liveIndicator.style.background = liveIndicator.style.background === 'rgb(34, 197, 94)' ? '#ef4444' : '#22c55e';
            }
        }, 2000);
    </script>
</body>
</html>
