<?php
/**
 * IUC Voting System - Voter List Page
 * Comprehensive voter management with search, filter, and remove functionality
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

// Handle voter removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove_voter') {
        try {
            $voter_id = $_POST['voter_id'];
            
            if ($pdo) {
                // Get voter info for confirmation
                $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
                $stmt->execute([$voter_id]);
                $voter = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($voter) {
                    // Remove voter from database
                    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                    $stmt->execute([$voter_id]);
                    
                    $success_message = "Voter " . htmlspecialchars($voter['name'] ?? 'Unknown') . " has been removed successfully.";
                } else {
                    $error_message = "Voter not found.";
                }
            } else {
                $error_message = "Database not available";
            }
        } catch (Exception $e) {
            $error_message = "Error removing voter: " . $e->getMessage();
        }
    }
}

// Get all voters with search and filter
$voters = [];
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';

if ($pdo) {
    try {
        $sql = "SELECT * FROM students WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }
        
        if (!empty($department_filter)) {
            $sql .= " AND department = ?";
            $params[] = $department_filter;
        }
        
        if (!empty($status_filter)) {
            $sql .= " AND status = ?";
            $params[] = $status_filter;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading voters: " . $e->getMessage();
    }
}

// Get unique departments for filter
$departments = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Ignore filter errors
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter List - IUC Voting System</title>
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
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
        }

        .filter-select {
            min-width: 150px;
        }

        .search-input:focus, .filter-select:focus {
            outline: none;
            border-color: #3282B8;
            box-shadow: 0 0 0 3px rgba(50, 130, 184, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .voters-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .voters-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e2e8f0;
        }

        .voters-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .voters-table tr:hover {
            background: #f8fafc;
        }

        .voting-code {
            font-family: 'Courier New', monospace;
            background: #1e293b;
            color: #10b981;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3282B8;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
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
            
            .voters-table {
                font-size: 0.875rem;
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
                    <a href="index.php?page=voter_list" class="sidebar-item active">
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
                <h1 class="page-title">Voter List</h1>
                <p class="page-subtitle">Manage registered voters and their voting codes</p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($voters); ?></div>
                    <div class="stat-label">Total Voters</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($voters, function($v) { return ($v['status'] ?? 'active') === 'active'; })); ?></div>
                    <div class="stat-label">Active Voters</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($voters, function($v) { return !empty($v['voting_code']); })); ?></div>
                    <div class="stat-label">With Voting Codes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($voters, function($v) { return !empty($v['email']); })); ?></div>
                    <div class="stat-label">Email Registered</div>
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
                    <input type="text" name="search" class="search-input" placeholder="Search by name, email, or student ID..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="department" class="filter-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    
                    <a href="index.php?page=voter_list" class="btn btn-primary">
                        <i class="fas fa-redo"></i>
                        Clear
                    </a>
                </form>
            </div>
            
            <!-- Voters Table -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i>
                        Registered Voters
                        <span style="font-size: 0.9rem; color: #64748b; margin-left: 0.5rem;">
                            (<?php echo count($voters); ?> voters)
                        </span>
                    </h2>
                </div>
                
                <?php if (count($voters) > 0): ?>
                    <table class="voters-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Voting Code</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
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
                                        <?php if (!empty($voter['voting_code'])): ?>
                                            <span class="voting-code">
                                                <?php echo htmlspecialchars($voter['voting_code']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #64748b;">Not Generated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $voter['status'] ?? 'active'; ?>">
                                            <?php echo ucfirst($voter['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($voter['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to remove this voter? This action cannot be undone.');">
                                                <input type="hidden" name="action" value="remove_voter">
                                                <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">
                                                    <i class="fas fa-trash"></i>
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No voters found matching your criteria.</p>
                        <p><a href="index.php?page=voter_registration" class="btn btn-primary">Register New Voter</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
