<?php
/**
 * Debug notification details and action URLs
 */

require_once 'config/config.php';

echo "<h2>Debug Notification Details</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Get the most recent notification for clair
echo "<h3>Recent Notification Details:</h3>";
$stmt = $pdo->prepare("SELECT n.*, u.name as student_name, u.email as student_email 
                    FROM notifications n 
                    LEFT JOIN users u ON n.related_student_id = u.id 
                    WHERE n.type = 'student_registration' 
                    ORDER BY n.created_at DESC LIMIT 5");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notifications as $notification) {
    echo "<div style='border: 1px solid #ccc; padding: 1rem; margin: 1rem 0; border-radius: 8px;'>";
    echo "<h4>Notification ID: {$notification['id']}</h4>";
    echo "<p><strong>Title:</strong> " . htmlspecialchars($notification['title']) . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($notification['message']) . "</p>";
    echo "<p><strong>Related Student ID:</strong> {$notification['related_student_id']}</p>";
    echo "<p><strong>Student Name:</strong> " . htmlspecialchars($notification['student_name']) . "</p>";
    echo "<p><strong>Student Email:</strong> " . htmlspecialchars($notification['student_email']) . "</p>";
    echo "<p><strong>Action Required:</strong> " . ($notification['action_required'] ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Action URL:</strong> " . htmlspecialchars($notification['action_url']) . "</p>";
    echo "<p><strong>Action Text:</strong> " . htmlspecialchars($notification['action_text']) . "</p>";
    echo "<p><strong>Created:</strong> {$notification['created_at']}</p>";
    
    // Test the action URL
    if ($notification['action_url']) {
        echo "<div style='margin-top: 1rem;'>";
        echo "<a href='{$notification['action_url']}' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Test Action URL</a>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Check what happens when we access the voter registration page directly with clair's ID
echo "<h3>Test Direct Access to Voter Registration:</h3>";
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE name = ? AND type = 'student'");
$stmt->execute(['clair']);
$clair = $stmt->fetch(PDO::FETCH_ASSOC);

if ($clair) {
    echo "<p>Found clair: ID {$clair['id']}, Email: {$clair['email']}</p>";
    
    $direct_url = "index.php?page=voter_registration&student_id={$clair['id']}";
    echo "<p><strong>Direct URL:</strong> <code>$direct_url</code></p>";
    echo "<a href='$direct_url' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Test Direct Access</a>";
} else {
    echo "<p style='color: red;'>Clair not found</p>";
}

// Check the voter registration page logic
echo "<h3>Voter Registration Page Student Selection:</h3>";
echo "<p>The voter registration page should:</p>";
echo "<ol>";
echo "<li>Check for student_id parameter in URL</li>";
echo "<li>If found, pre-select that student</li>";
echo "<li>If not found, show student selection dropdown</li>";
echo "</ol>";

// Show the current session data
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
?>
