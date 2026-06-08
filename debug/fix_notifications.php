<?php
/**
 * Fix notifications table structure and column issues
 */

require_once 'config/config.php';

echo "<h2>Fix Notifications Table</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check current notifications table structure
echo "<h3>Current Notifications Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $column) {
    echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
}
echo "</table>";

// Check if related_user_id column exists
$hasRelatedUserId = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'related_user_id') {
        $hasRelatedUserId = true;
        break;
    }
}

if (!$hasRelatedUserId) {
    echo "<h3>Adding missing columns...</h3>";
    
    // Add missing columns
    $sql = "ALTER TABLE notifications 
            ADD COLUMN related_user_id INT NULL AFTER user_id,
            ADD COLUMN related_student_id INT NULL AFTER related_user_id,
            ADD COLUMN action_required BOOLEAN DEFAULT FALSE AFTER priority,
            ADD COLUMN action_url VARCHAR(500) NULL AFTER action_required,
            ADD COLUMN action_text VARCHAR(100) NULL AFTER action_url";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Added missing columns to notifications table</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error adding columns: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ All required columns exist</p>";
}

// Show updated structure
echo "<h3>Updated Notifications Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $column) {
    echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
}
echo "</table>";

// Check existing notifications
echo "<h3>Existing Notifications:</h3>";
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifications) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Type</th><th>Title</th><th>Related User ID</th><th>Priority</th><th>Status</th></tr>";
    foreach ($notifications as $notification) {
        echo "<tr>";
        echo "<td>{$notification['id']}</td>";
        echo "<td>{$notification['type']}</td>";
        echo "<td>" . substr($notification['title'], 0, 50) . "...</td>";
        echo "<td>{$notification['related_user_id']}</td>";
        echo "<td>{$notification['priority']}</td>";
        echo "<td>{$notification['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No notifications found</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
?>
