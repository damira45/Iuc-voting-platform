<?php
/**
 * IUC Voting System - Admin Dashboard (New Design)
 * Professional sidebar-based admin interface
 */

session_start();

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

// Handle candidate upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_candidate') {
        try {
            $name = $_POST['name'];
            $position = $_POST['position'];
            $manifesto = $_POST['manifesto'];
            $campaign_slogan = $_POST['campaign_slogan'];
            
            // Handle image upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'assets/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $file_name;
                }
            }
            
            if ($pdo) {
                $stmt = $pdo->prepare("INSERT INTO candidates (name, position, manifesto, campaign_slogan, image, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$name, $position, $manifesto, $campaign_slogan, $image_path]);
                $success_message = "Candidate added successfully!";
            } else {
                $error_message = "Database not available";
            }
        } catch (Exception $e) {
            $error_message = "Error adding candidate: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'update_status') {
        try {
            $candidate_id = $_POST['candidate_id'];
            $status = $_POST['status'];
            
            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE candidates SET status = ? WHERE id = ?");
                $stmt->execute([$status, $candidate_id]);
                $success_message = "Candidate status updated successfully!";
            } else {
                $error_message = "Database not available";
            }
        } catch (Exception $e) {
            $error_message = "Error updating status: " . $e->getMessage();
        }
    }
}

// Get all candidates
$candidates = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM candidates ORDER BY created_at DESC");
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading candidates: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IUC Voting System</title>
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
        
        .sidebar-item i {
            width: 20px;
            text-align: center;
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
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B3C5D;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card-new {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #3282B8;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B3C5D;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
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
        
        .status-pending-new {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved-new {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected-new {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons-new {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm-new {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-success-new {
            background: #10b981;
            color: white;
        }
        
        .btn-danger-new {
            background: #ef4444;
            color: white;
        }
        
        .btn-info-new {
            background: #3b82f6;
            color: white;
        }
        
        .btn-warning-new {
            background: #f59e0b;
            color: white;
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
            
            .stats-grid {
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
                <a href="#" class="sidebar-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-users"></i>
                    Candidates
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-vote-yea"></i>
                    Elections
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-chart-bar"></i>
                    Results
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-users-cog"></i>
                    Voters
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-file-alt"></i>
                    Reports
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
                    <h1 class="admin-title">Dashboard</h1>
                    <p style="color: #64748b; margin: 0;">Manage candidates and election campaigns</p>
                </div>
                <div class="admin-user">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #374151;"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></div>
                        <div style="font-size: 0.8rem; color: #64748b;">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fecaca;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo count($candidates); ?></div>
                    <div class="stat-label">Total Candidates</div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo count(array_filter($candidates, fn($c) => ($c['status'] ?? 'pending') === 'approved')); ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo count(array_filter($candidates, fn($c) => ($c['status'] ?? 'pending') === 'pending')); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo count(array_filter($candidates, fn($c) => ($c['status'] ?? 'pending') === 'rejected')); ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            
            <!-- Add Candidate Form -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-user-plus"></i>
                        Add New Candidate
                    </h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="form-new">
                    <input type="hidden" name="action" value="add_candidate">
                    
                    <div class="form-row">
                        <div class="form-group-new">
                            <label for="name">Candidate Name</label>
                            <input type="text" id="name" name="name" required 
                                   class="form-control-new"
                                   placeholder="Enter candidate full name">
                        </div>
                        
                        <div class="form-group-new">
                            <label for="position">Position</label>
                            <input type="text" id="position" name="position" required 
                                   class="form-control-new"
                                   placeholder="e.g., Student Council President">
                        </div>
                    </div>
                    
                    <div class="form-group-new">
                        <label for="campaign_slogan">Campaign Slogan</label>
                        <input type="text" id="campaign_slogan" name="campaign_slogan" required 
                               class="form-control-new"
                               placeholder="Enter campaign slogan">
                    </div>
                    
                    <div class="form-group-new">
                        <label for="manifesto">Manifesto</label>
                        <textarea id="manifesto" name="manifesto" required 
                                  class="form-control-new"
                                  rows="4"
                                  placeholder="Enter candidate manifesto and vision"></textarea>
                    </div>
                    
                    <div class="form-group-new">
                        <label for="image">Candidate Photo</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-control-new">
                        <small style="color: #64748b;">Upload candidate photo (optional)</small>
                    </div>
                    
                    <button type="submit" class="btn-new btn-primary-new">
                        <i class="fas fa-plus"></i>
                        Add Candidate
                    </button>
                </form>
            </div>
            
            <!-- Candidates Management -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-users"></i>
                        Manage Candidates
                    </h2>
                </div>
                
                <?php if (empty($candidates)): ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>No Candidates Yet</h3>
                        <p>Add your first candidate using the form above.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table-new">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Slogan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td>
                                            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #f8fafc; display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($candidate['image']) && file_exists('assets/images/' . $candidate['image'])): ?>
                                                    <img src="assets/images/<?php echo htmlspecialchars($candidate['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($candidate['name']); ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fas fa-user" style="color: #64748b;"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['campaign_slogan'] ?? ''); ?></td>
                                        <td>
                                            <span class="status-badge-new status-<?php echo $candidate['status'] ?? 'pending'; ?>-new">
                                                <?php echo ucfirst($candidate['status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons-new">
                                                <?php if (($candidate['status'] ?? 'pending') === 'pending'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn-sm-new btn-success-new" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="btn-sm-new btn-danger-new" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <button class="btn-sm-new btn-info-new" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button class="btn-sm-new btn-warning-new" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
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
