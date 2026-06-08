<?php
/**
 * Recreate notifications table with correct structure
 */

require_once 'config/config.php';

echo "<h2>Recreate Notifications Table</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Drop existing table
echo "<h3>Dropping existing notifications table...</h3>";
try {
    $pdo->exec("DROP TABLE IF EXISTS notifications");
    echo "<p style='color: green;'>✓ Dropped existing notifications table</p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'>Table didn't exist or couldn't be dropped</p>";
}

// Create new table with correct structure
echo "<h3>Creating new notifications table...</h3>";
$sql = "CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('student_registration', 'voting_code_required', 'election_started', 'system_alert', 'security_warning', 'general', 'info', 'success', 'warning', 'error') DEFAULT 'general',
    status ENUM('unread', 'read', 'dismissed') DEFAULT 'unread',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    related_user_id INT NULL,
    related_student_id INT NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_related_user (related_user_id),
    INDEX idx_related_student (related_student_id)
)";

try {
    $pdo->exec($sql);
    echo "<p style='color: green;'>✓ Created new notifications table with correct structure</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error creating table: " . $e->getMessage() . "</p>";
}

// Show new table structure
echo "<h3>New Notifications Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $column) {
    echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
}
echo "</table>";

// Create notification for the existing student
echo "<h3>Creating notification for Anifa mimche...</h3>";

// Find the student
$stmt = $pdo->prepare("SELECT u.id, u.name, u.email, s.student_id 
                      FROM users u 
                      JOIN students s ON u.id = s.user_id 
                      WHERE u.name = 'Anifa mimche' 
                      ORDER BY u.created_at DESC 
                      LIMIT 1");
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    try {
        require_once 'includes/NotificationManager.php';
        $notificationManager = new NotificationManager($pdo);
        
        // Create notification for admin
        $result = $notificationManager->createNotification(
            'student_registration',
            'New Student Registration - Approval Required',
            "Student {$student['name']} ({$student['email']}, Student ID: {$student['student_id']}) has registered and requires approval and voting code generation.",
            null,
            $student['id'],
            'high',
            true,
            "index.php?page=voter_registration&action=generate_code&student_id={$student['id']}",
            'Generate Voting Code'
        );
        
        if ($result) {
            echo "<p style='color: green;'>✓ Notification created successfully for {$student['name']}</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create notification</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating notification: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>Student Anifa mimche not found</p>";
}

// Show all notifications
echo "<h3>All Notifications:</h3>";
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Title</th><th>Priority</th><th>Status</th><th>Related Student</th><th>Action Required</th></tr>";
    
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>{$notification['id']}</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
        echo "<td>{$notification['priority']}</td>";
        echo "<td>{$notification['status']}</td>";
        echo "<td>{$notification['related_student_id']}</td>";
        echo "<td>" . ($notification['action_required'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No notifications found</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a> (Check bell icon for notifications)</p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
echo "<p><a href='debug_student_registration.php'>Debug Student Registration</a></p>";
?>
