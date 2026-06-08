<?php
/**
 * IUC Voting System - Admin Dashboard (New Design)
 * Professional sidebar-based admin interface
 */

require_once 'config/config.php';
require_once 'includes/NotificationManager.php';

// Time-ago helper
function human_time_diff($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60)       return 'Just now';
    if ($diff < 3600)     return floor($diff/60) . ' min ago';
    if ($diff < 86400)    return floor($diff/3600) . ' hour' . (floor($diff/3600)>1?'s':'') . ' ago';
    if ($diff < 604800)   return floor($diff/86400) . ' day' . (floor($diff/86400)>1?'s':'') . ' ago';
    return date('M d, Y', $timestamp);
}

// Helper functions for notifications
function getNotificationIcon($type) {
    $icons = [
        'student_registration' => 'user-plus',
        'voting_code_required' => 'key',
        'election_started' => 'play-circle',
        'system_alert' => 'exclamation-triangle',
        'security_warning' => 'shield-alt',
        'general' => 'info-circle'
    ];
    return $icons[$type] ?? 'bell';
}

function getNotificationIconClass($type) {
    $classes = [
        'student_registration' => 'success',
        'voting_code_required' => 'warning',
        'election_started' => 'info',
        'system_alert' => 'warning',
        'security_warning' => 'danger',
        'general' => 'info'
    ];
    return $classes[$type] ?? 'info';
}

function formatNotificationTime($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return date('M d, H:i', $timestamp);
    }
}

// Initialize notification manager
$notificationManager = new NotificationManager($pdo);

// Get real notifications for dashboard
$notifications = [];
$notificationCount = 0;
if ($pdo) {
    try {
        $notifications = $notificationManager->getAdminNotifications(5, 'unread');
        $notificationCount = $notificationManager->getNotificationCount('unread');
    } catch (Exception $e) {
        $notifications = [];
        $notificationCount = 0;
    }
}

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
        
        .stat-trend {
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }
        
        .stat-trend.positive {
            color: #10b981;
        }
        
        .stat-trend.negative {
            color: #ef4444;
        }
        
        .stat-trend.neutral {
            color: #64748b;
        }
        
        /* Alert Styles */
        .badge-alert {
            background: #ef4444;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .alerts-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .alert-item:hover {
            transform: translateX(4px);
        }
        
        .alert-success {
            background: #d1fae5;
            border-left-color: #10b981;
        }
        
        .alert-warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        
        .alert-info {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }
        
        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .alert-success .alert-icon {
            background: #10b981;
            color: white;
        }
        
        .alert-warning .alert-icon {
            background: #f59e0b;
            color: white;
        }
        
        .alert-info .alert-icon {
            background: #3b82f6;
            color: white;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .alert-message {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .alert-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        
        .alert-dismiss {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .alert-dismiss:hover {
            background: rgba(0,0,0,0.1);
            color: #374151;
        }
        
        /* Elections Progress Styles */
        .elections-progress {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .election-item {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .election-item:hover {
            border-color: #3282B8;
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.1);
        }
        
        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .election-info h4 {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .election-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .election-status.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .election-status.upcoming {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .election-status.closed {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .election-stats {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }
        
        .vote-count {
            font-weight: 700;
            color: #374151;
            font-size: 1.1rem;
        }
        
        .turnout {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3282B8, #0B3C5D);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 0.5rem;
            position: relative;
            transition: width 0.3s ease;
        }
        
        .progress-fill span {
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            position: absolute;
            right: 0.5rem;
            top: -16px;
        }
        
        .election-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .election-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        /* Activity Feed Styles */
        .activity-feed {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f1f5f9;
            border-left-color: #3282B8;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-message {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        /* Overview Grid Styles */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .overview-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .overview-item:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        
        .overview-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B3C5D;
            font-size: 1.2rem;
        }
        
        .overview-details {
            flex: 1;
        }
        
        .overview-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.25rem;
        }
        
        .overview-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .overview-trend {
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .overview-trend.positive {
            color: #10b981;
        }
        
        .overview-trend.negative {
            color: #ef4444;
        }
        
        .overview-trend.neutral {
            color: #64748b;
        }
        
        /* Quick Actions Styles */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .quick-action-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-action-item:hover {
            background: #f1f5f9;
            border-color: #3282B8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.1);
        }
        
        .quick-action-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0B3C5D;
            font-size: 1.1rem;
        }
        
        .quick-action-text {
            flex: 1;
        }
        
        .quick-action-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .quick-action-desc {
            font-size: 0.85rem;
            color: #64748b;
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
            
            .overview-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Notification Bell Styles */
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            background: #f8fafc;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .notification-bell:hover {
            background: #e2e8f0;
            transform: scale(1.05);
        }
        
        .notification-bell i {
            font-size: 1.2rem;
            color: #64748b;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        
        /* Notification Dropdown Styles */
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 380px;
            max-height: 480px;
            overflow: hidden;
            z-index: 1000;
            display: none;
            margin-top: 0.5rem;
        }
        
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
        }
        
        .mark-all-read {
            background: none;
            border: none;
            color: #3282B8;
            font-size: 0.8rem;
            cursor: pointer;
            font-weight: 500;
        }
        
        .mark-all-read:hover {
            color: #0B3C5D;
        }
        
        .notification-list {
            max-height: 320px;
            overflow-y: auto;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background: #f8fafc;
        }
        
        .notification-item.unread {
            background: #f0f9ff;
            border-left: 3px solid #3282B8;
        }
        
        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notification-icon.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification-icon.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            color: #64748b;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .notification-time {
            color: #94a3b8;
            font-size: 0.75rem;
        }
        
        .notification-dismiss {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .notification-dismiss:hover {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .notification-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            background: #f8fafc;
        }
        
        .view-all-notifications {
            color: #3282B8;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .view-all-notifications:hover {
            color: #0B3C5D;
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
                <a href="#" class="sidebar-item active">
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
                <a href="index.php?page=voter_registration" class="sidebar-item">
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
                
                <!-- Analytics -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        ANALYTICS
                    </div>
                </div>
                <a href="index.php?page=realtime_voting" class="sidebar-item">
                    <i class="fas fa-chart-line"></i>
                    Real-time Voting
                </a>
                <a href="index.php?page=participants" class="sidebar-item">
                    <i class="fas fa-users"></i>
                    Participants
                </a>
                
                <!-- Audit -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        AUDIT
                    </div>
                </div>
                <a href="index.php?page=audit_logs" class="sidebar-item">
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
                
                <!-- System -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        SYSTEM
                    </div>
                </div>
                <a href="index.php?page=settings" class="sidebar-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="index.php?page=report" class="sidebar-item">
                    <i class="fas fa-chart-pie"></i>
                    Report
                </a>
                <a href="index.php?page=backup" class="sidebar-item">
                    <i class="fas fa-database"></i>
                    Backup
                </a>
                <a href="index.php?page=complain" class="sidebar-item">
                    <i class="fas fa-comment-dots"></i>
                    Complain
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
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <!-- Notification Bell + Dropdown -->
                    <div style="position: relative;">
                        <div class="notification-bell" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($notificationCount > 0): ?>
                            <span class="notification-badge"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Notification Dropdown (hidden by default) -->
                        <div id="notificationDropdown" class="notification-dropdown">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                                <button class="mark-all-read" onclick="markAllAsRead()">Mark all read</button>
                            </div>
                            <div class="notification-list">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item unread" data-notification-id="<?php echo $notification['id']; ?>">
                                            <div class="notification-icon <?php echo getNotificationIconClass($notification['type']); ?>">
                                                <i class="fas fa-<?php echo getNotificationIcon($notification['type']); ?>"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                <div class="notification-time"><?php echo formatNotificationTime($notification['created_at']); ?></div>
                                                <?php if ($notification['action_required'] && $notification['action_url']): ?>
                                                <div style="margin-top:0.5rem;">
                                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" style="background:#10b981;color:white;padding:0.3rem 0.8rem;font-size:0.8rem;text-decoration:none;border-radius:6px;display:inline-block;">
                                                        <?php echo htmlspecialchars($notification['action_text'] ?: 'Take Action'); ?>
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <button class="notification-dismiss" onclick="dismissNotification(this, <?php echo $notification['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="text-align:center;padding:2rem;color:#64748b;">
                                        <i class="fas fa-bell-slash" style="font-size:2rem;margin-bottom:0.5rem;opacity:0.5;display:block;"></i>
                                        <p>No new notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-footer">
                                <a href="index.php?page=complain" class="view-all-notifications">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    <!-- Admin user info -->
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
            </div>

            <!-- Notification Dropdown is now inside the bell wrapper above -->

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
            
            <?php
                // Real stats from database
                $activeElecCount  = $pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'active'")->fetchColumn();
                $totalVotesCount  = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
                $totalStudents    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student'")->fetchColumn();
                $approvedStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'")->fetchColumn();
                $pendingStudents  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student' AND status='pending'")->fetchColumn();
                $todayVotes       = (int)$pdo->query("SELECT COUNT(*) FROM votes WHERE DATE(created_at)=CURDATE()")->fetchColumn();

                // Recent activity: last 8 events from votes + user registrations
                $recentVotes = $pdo->query("
                    SELECT 'vote' AS event_type, v.created_at,
                           CONCAT(u.name, ' voted in \"', e.title, '\"') AS message
                    FROM votes v
                    JOIN users u ON v.user_id = u.id
                    JOIN elections e ON v.election_id = e.id
                    ORDER BY v.created_at DESC LIMIT 4
                ")->fetchAll(PDO::FETCH_ASSOC);

                $recentRegs = $pdo->query("
                    SELECT 'registration' AS event_type, u.created_at,
                           CONCAT(u.name, ' registered (', u.status, ')') AS message
                    FROM users u
                    WHERE u.type = 'student'
                    ORDER BY u.created_at DESC LIMIT 4
                ")->fetchAll(PDO::FETCH_ASSOC);

                $recentElections = $pdo->query("
                    SELECT 'election' AS event_type, e.created_at,
                           CONCAT('Election \"', e.title, '\" created') AS message
                    FROM elections e
                    ORDER BY e.created_at DESC LIMIT 2
                ")->fetchAll(PDO::FETCH_ASSOC);

                // Merge and sort by date
                $allActivity = array_merge($recentVotes, $recentRegs, $recentElections);
                usort($allActivity, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
                $allActivity = array_slice($allActivity, 0, 8);

                $activityConfig = [
                    'vote'         => ['icon' => 'fa-vote-yea',   'color' => '#3282B8'],
                    'registration' => ['icon' => 'fa-user-plus',  'color' => '#10b981'],
                    'election'     => ['icon' => 'fa-poll',        'color' => '#8b5cf6'],
                ];
            ?>
            <!-- Real-time Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-number" id="activeElections"><?php echo (int)$activeElecCount; ?></div>
                    <div class="stat-label">Active Elections</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-database"></i> Live data
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-ballot-check"></i>
                    </div>
                    <div class="stat-number" id="totalVotes"><?php echo number_format((int)$totalVotesCount); ?></div>
                    <div class="stat-label">Total Votes</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-database"></i> Live data
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="stat-number" id="blockchainBlocks">—</div>
                    <div class="stat-label">Blockchain Blocks</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-link"></i> On-chain
                    </div>
                </div>
                
                <div class="stat-card-new">
                    <div class="stat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-number" id="systemUptime">99.9%</div>
                    <div class="stat-label">System Uptime</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-check"></i> Excellent
                    </div>
                </div>
            </div>
            
                        
            <!-- Active Elections with Progress Tracking -->
            <?php
                // Fetch all elections from the database for the dashboard
                $dashElecStmt = $pdo->prepare("
                    SELECT e.*,
                           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes,
                           (SELECT COUNT(*) FROM users u WHERE u.type = 'student' AND u.status = 'approved') as eligible_voters
                    FROM elections e
                    ORDER BY e.created_at DESC
                    LIMIT 5
                ");
                $dashElecStmt->execute();
                $dashElections = $dashElecStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-chart-line"></i>
                        Elections Progress
                    </h2>
                    <a href="index.php?page=elections" class="btn-new btn-primary-new" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                        <i class="fas fa-eye"></i>
                        View All
                    </a>
                </div>

                <div class="elections-progress">
                    <?php if (empty($dashElections)): ?>
                        <p style="padding:1.5rem; color:#64748b; text-align:center;">No elections created yet.</p>
                    <?php else: ?>
                        <?php foreach ($dashElections as $de): ?>
                            <?php
                                $deVotes    = (int)$de['total_votes'];
                                $deEligible = (int)$de['eligible_voters'];
                                $deTurnout  = $deEligible > 0 ? round(($deVotes / $deEligible) * 100) : 0;
                                $deStatus   = $de['status'];
                                $deEnds     = !empty($de['end_date']) ? date('M d, Y', strtotime($de['end_date'])) : 'N/A';
                            ?>
                            <div class="election-item">
                                <div class="election-header">
                                    <div class="election-info">
                                        <h4><?php echo htmlspecialchars($de['title']); ?></h4>
                                        <span class="election-status <?php echo htmlspecialchars($deStatus); ?>"><?php echo ucfirst($deStatus); ?></span>
                                    </div>
                                    <div class="election-stats">
                                        <span class="vote-count"><?php echo $deVotes; ?> votes</span>
                                        <span class="turnout"><?php echo $deTurnout; ?>% turnout</span>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $deTurnout; ?>%;">
                                        <span><?php echo $deTurnout; ?>%</span>
                                    </div>
                                </div>
                                <div class="election-meta">
                                    <span><i class="fas fa-clock"></i> Ends: <?php echo $deEnds; ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo $deEligible; ?> eligible voters</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Activity Feed -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-clock"></i>
                        Recent Activity
                    </h2>
                    <a href="index.php?page=authentication_logs" class="btn-new btn-primary-new" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; text-decoration:none;">
                        <i class="fas fa-external-link-alt"></i> View All
                    </a>
                </div>
                <div class="activity-feed">
                    <?php if (empty($allActivity)): ?>
                        <div style="text-align:center;padding:2rem;color:#94a3b8;">
                            <i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3;"></i>
                            No activity recorded yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allActivity as $act):
                            $cfg = $activityConfig[$act['event_type']] ?? ['icon'=>'fa-circle','color'=>'#64748b'];
                            $timeAgo = human_time_diff(strtotime($act['created_at']));
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background:<?php echo $cfg['color']; ?>20;color:<?php echo $cfg['color']; ?>;">
                                <i class="fas <?php echo $cfg['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-message"><?php echo htmlspecialchars($act['message']); ?></div>
                                <div class="activity-time"><?php echo $timeAgo; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Overview -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-chart-line"></i>
                        System Overview
                    </h2>
                </div>
                <div class="overview-grid">
                    <div class="overview-item">
                        <div class="overview-icon"><i class="fas fa-users"></i></div>
                        <div class="overview-details">
                            <div class="overview-number"><?php echo number_format($approvedStudents); ?></div>
                            <div class="overview-label">Approved Voters</div>
                            <div class="overview-trend <?php echo $pendingStudents > 0 ? 'positive' : 'neutral'; ?>">
                                <i class="fas fa-clock"></i> <?php echo $pendingStudents; ?> pending approval
                            </div>
                        </div>
                    </div>
                    <div class="overview-item">
                        <div class="overview-icon"><i class="fas fa-vote-yea"></i></div>
                        <div class="overview-details">
                            <div class="overview-number"><?php echo (int)$activeElecCount; ?></div>
                            <div class="overview-label">Active Elections</div>
                            <div class="overview-trend neutral">
                                <i class="fas fa-database"></i> Live data
                            </div>
                        </div>
                    </div>
                    <div class="overview-item">
                        <div class="overview-icon"><i class="fas fa-ballot-check"></i></div>
                        <div class="overview-details">
                            <div class="overview-number"><?php echo number_format($todayVotes); ?></div>
                            <div class="overview-label">Votes Cast Today</div>
                            <div class="overview-trend <?php echo $todayVotes > 0 ? 'positive' : 'neutral'; ?>">
                                <i class="fas fa-<?php echo $todayVotes > 0 ? 'arrow-up' : 'minus'; ?>"></i>
                                <?php echo $totalVotesCount; ?> total all time
                            </div>
                        </div>
                    </div>
                    <div class="overview-item">
                        <div class="overview-icon"><i class="fas fa-server"></i></div>
                        <div class="overview-details">
                            <div class="overview-number"><?php echo $approvedStudents > 0 && $totalVotesCount > 0 ? round($totalVotesCount / $approvedStudents * 100, 1) . '%' : '0%'; ?></div>
                            <div class="overview-label">Overall Turnout</div>
                            <div class="overview-trend positive">
                                <i class="fas fa-check"></i> Live calculation
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="content-card">
                <div class="content-header">
                    <h2 class="content-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="quick-actions-grid">
                    <a href="index.php?page=voter_registration" class="quick-action-item">
                        <div class="quick-action-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">Register Student</div>
                            <div class="quick-action-desc">Add a new voter</div>
                        </div>
                    </a>
                    <a href="index.php?page=elections" class="quick-action-item" onclick="setTimeout(()=>openCreateElectionModal&&openCreateElectionModal(),300)">
                        <div class="quick-action-icon"><i class="fas fa-plus-circle"></i></div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">Create Election</div>
                            <div class="quick-action-desc">Start a new election</div>
                        </div>
                    </a>
                    <a href="index.php?page=report&export=csv" class="quick-action-item">
                        <div class="quick-action-icon"><i class="fas fa-file-export"></i></div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">Export Report</div>
                            <div class="quick-action-desc">Download CSV data</div>
                        </div>
                    </a>
                    <a href="index.php?page=access_control<?php echo $pendingStudents > 0 ? '&status=pending' : ''; ?>" class="quick-action-item">
                        <div class="quick-action-icon" style="<?php echo $pendingStudents > 0 ? 'background:linear-gradient(135deg,#f59e0b,#d97706);' : ''; ?>">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">Approve Students</div>
                            <div class="quick-action-desc"><?php echo $pendingStudents > 0 ? $pendingStudents . ' pending approval' : 'No pending approvals'; ?></div>
                        </div>
                    </a>
                    <a href="index.php?page=results" class="quick-action-item">
                        <div class="quick-action-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">View Results</div>
                            <div class="quick-action-desc">Election standings</div>
                        </div>
                    </a>
                    <a href="index.php?page=settings" class="quick-action-item">
                        <div class="quick-action-icon"><i class="fas fa-cog"></i></div>
                        <div class="quick-action-text">
                            <div class="quick-action-title">Settings</div>
                            <div class="quick-action-desc">Configure system</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('active');
        }
        
        // Notification system functions
        function toggleNotifications() {
            console.log('Toggle notifications clicked');
            const dropdown = document.getElementById('notificationDropdown');
            console.log('Dropdown element:', dropdown);
            
            if (dropdown) {
                dropdown.classList.toggle('show');
                console.log('Dropdown classes after toggle:', dropdown.className);
                
                // Close dropdown when clicking outside
                if (dropdown.classList.contains('show')) {
                    setTimeout(() => {
                        document.addEventListener('click', closeNotificationsOutside);
                    }, 100);
                } else {
                    document.removeEventListener('click', closeNotificationsOutside);
                }
            } else {
                console.error('Notification dropdown not found');
            }
        }
        
        function closeNotificationsOutside(event) {
            const dropdown = document.getElementById('notificationDropdown');
            const bell = document.querySelector('.notification-bell');
            
            if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.classList.remove('show');
                document.removeEventListener('click', closeNotificationsOutside);
            }
        }
        
        function dismissNotification(button, notificationId) {
            if (notificationId) {
                // Send AJAX request to mark notification as dismissed
                fetch('index.php?page=dismiss_notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const notificationItem = button.closest('.notification-item');
                        notificationItem.style.animation = 'slideOut 0.3s ease';
                        
                        setTimeout(() => {
                            notificationItem.remove();
                            updateNotificationBadge();
                        }, 300);
                    }
                })
                .catch(error => {
                    console.error('Error dismissing notification:', error);
                });
            } else {
                // Fallback for demo notifications
                const notificationItem = button.closest('.notification-item');
                notificationItem.style.animation = 'slideOut 0.3s ease';
                
                setTimeout(() => {
                    notificationItem.remove();
                    updateNotificationBadge();
                }, 300);
            }
            
            event.stopPropagation();
        }
        
        function markAllAsRead() {
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            unreadNotifications.forEach(notification => {
                notification.classList.remove('unread');
            });
            
            updateNotificationBadge();
        }
        
        function updateNotificationBadge() {
            const unreadCount = document.querySelectorAll('.notification-item.unread').length;
            const badge = document.querySelector('.notification-badge');
            
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
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
        
        // Add slide out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
