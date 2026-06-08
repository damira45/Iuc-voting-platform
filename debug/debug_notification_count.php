<?php
/**
 * Debug notification count for admin dashboard
 */

require_once 'config/config.php';

echo "<h2>Debug Notification Count</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Test direct SQL query
echo "<h3>Direct SQL Query Test:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE status = 'unread'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p>Direct query result: {$result['count']}</p>";

// Test NotificationManager
echo "<h3>NotificationManager Test:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    $count = $notificationManager->getNotificationCount('unread');
    echo "<p>NotificationManager count: {$count}</p>";
    
    $notifications = $notificationManager->getAdminNotifications(5, 'unread');
    echo "<p>NotificationManager notifications count: " . count($notifications) . "</p>";
    
    if (count($notifications) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Title</th><th>Status</th><th>Priority</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['id']}</td>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
            echo "<td>{$notification['status']}</td>";
            echo "<td>{$notification['priority']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Show all notifications with details
echo "<h3>All Notifications Details:</h3>";
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Title</th><th>Status</th><th>Priority</th><th>Related User</th><th>Related Student</th><th>Action Required</th></tr>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>{$notification['id']}</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
        echo "<td><strong>{$notification['status']}</strong></td>";
        echo "<td>{$notification['priority']}</td>";
        echo "<td>{$notification['user_id']}</td>";
        echo "<td>{$notification['related_student_id']}</td>";
        echo "<td>" . ($notification['action_required'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No notifications found</p>";
}

// Test admin dashboard variables
echo "<h3>Admin Dashboard Variables Test:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Simulate admin dashboard code
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
    
    echo "<p>Admin dashboard notificationCount: {$notificationCount}</p>";
    echo "<p>Admin dashboard notifications array count: " . count($notifications) . "</p>";
    
    // Test what the bell badge should show
    echo "<h3>Bell Badge Test:</h3>";
    echo "<p>Bel badge should show: {$notificationCount}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error in admin dashboard test: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='recreate_notifications.php'>Recreate Notifications</a></p>";
?>
