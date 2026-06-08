<?php
/**
 * Debug student self-registration notification system
 */

require_once 'config/config.php';

echo "<h2>Student Registration Notification Debug</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check recent student registrations
echo "<h3>Recent Student Registrations:</h3>";
$stmt = $pdo->query("SELECT u.*, s.student_id, s.department, s.level 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student' 
                    ORDER BY u.created_at DESC 
                    LIMIT 10");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Student ID</th><th>Department</th><th>Registered</th><th>Notification</th></tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>{$student['name']}</td>";
        echo "<td>{$student['email']}</td>";
        echo "<td>{$student['student_id']}</td>";
        echo "<td>{$student['department']}</td>";
        echo "<td>{$student['created_at']}</td>";
        
        // Check if notification exists for this student
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE related_user_id = ? OR related_student_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$student['id'], $student['id']]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notification) {
            echo "<td style='color: green;'>✓ {$notification['type']} - {$notification['status']}</td>";
        } else {
            echo "<td style='color: red;'>✗ No notification found</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No student registrations found</p>";
}

// Check all notifications
echo "<h3>All Notifications:</h3>";
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Title</th><th>Related User</th><th>Related Student</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>{$notification['id']}</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
        echo "<td>{$notification['related_user_id']}</td>";
        echo "<td>{$notification['related_student_id']}</td>";
        echo "<td>{$notification['status']}</td>";
        echo "<td>{$notification['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No notifications found</p>";
}

// Test notification creation
echo "<h3>Test Notification Creation:</h3>";

// Get the most recent student
$stmt = $pdo->query("SELECT u.*, s.student_id 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student' 
                    ORDER BY u.created_at DESC 
                    LIMIT 1");
$latestStudent = $stmt->fetch(PDO::FETCH_ASSOC);

if ($latestStudent) {
    echo "<p>Testing with student: {$latestStudent['name']} (ID: {$latestStudent['id']})</p>";
    
    try {
        require_once 'includes/NotificationManager.php';
        $notificationManager = new NotificationManager($pdo);
        
        // Create a test notification
        $result = $notificationManager->createNotification(
            'student_registration',
            'Test Notification',
            "This is a test notification for student {$latestStudent['name']}",
            null,
            $latestStudent['id'],
            'high',
            true,
            "index.php?page=voter_registration&action=generate_code&student_id={$latestStudent['id']}",
            'Generate Voting Code'
        );
        
        if ($result) {
            echo "<p style='color: green;'>✓ Test notification created successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create test notification</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating test notification: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>No students found to test with</p>";
}

// Check admin dashboard notification count
echo "<h3>Admin Dashboard Notification Test:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    $notifications = $notificationManager->getAdminNotifications(5, 'unread');
    $notificationCount = $notificationManager->getNotificationCount('unread');
    
    echo "<p>Unread notifications count: {$notificationCount}</p>";
    
    if (count($notifications) > 0) {
        echo "<table border='1'><tr><th>Type</th><th>Title</th><th>Student Name</th><th>Student Email</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
            echo "<td>{$notification['student_name']}</td>";
            echo "<td>{$notification['student_email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No unread notifications found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting admin notifications: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='index.php?page=register'>Student Registration</a></p>";
echo "<p><a href='fix_notifications.php'>Fix Notifications Table</a></p>";
?>
