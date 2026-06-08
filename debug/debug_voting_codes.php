<?php
/**
 * Debug script to check voting codes table and data
 */

require_once 'config/config.php';

echo "<h2>Voting Codes Debug</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check if voting_codes table exists
echo "<h3>Checking Tables:</h3>";
$stmt = $pdo->query("SHOW TABLES LIKE 'voting_codes'");
$tableExists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tableExists) {
    echo "<p style='color: green;'>✓ voting_codes table exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE voting_codes");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
    
    // Show all voting codes
    echo "<h3>All Voting Codes:</h3>";
    $stmt = $pdo->query("SELECT * FROM voting_codes ORDER BY created_at DESC");
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($codes) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Election ID</th><th>Voting Code</th><th>Status</th><th>Created</th><th>Expires</th></tr>";
        foreach ($codes as $code) {
            echo "<tr><td>{$code['id']}</td><td>{$code['student_id']}</td><td>{$code['election_id']}</td><td><strong>{$code['voting_code']}</strong></td><td>{$code['status']}</td><td>{$code['created_at']}</td><td>{$code['expires_at']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No voting codes found in database</p>";
    }
    
    // Check users table
    echo "<h3>Users Table:</h3>";
    $stmt = $pdo->query("SELECT id, name, email, type, status FROM users WHERE type = 'student' ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Status</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>{$user['name']}</td><td>{$user['email']}</td><td>{$user['type']}</td><td>{$user['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No student users found</p>";
    }
    
    // Check students table
    echo "<h3>Students Table:</h3>";
    $stmt = $pdo->query("SELECT * FROM students ORDER BY created_at DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Student ID</th><th>Department</th><th>Level</th></tr>";
        foreach ($students as $student) {
            echo "<tr><td>{$student['id']}</td><td>{$student['user_id']}</td><td>{$student['student_id']}</td><td>{$student['department']}</td><td>{$student['level']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No students found</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ voting_codes table does not exist</p>";
    
    // Try to create the table
    echo "<h3>Creating voting_codes table...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS voting_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        election_id INT NOT NULL,
        voting_code VARCHAR(30) UNIQUE NOT NULL,
        status ENUM('generated', 'sent', 'used', 'expired') DEFAULT 'generated',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        used_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        generated_by_admin INT NOT NULL,
        sent_by_admin INT NULL,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (election_id) REFERENCES elections(id),
        FOREIGN KEY (generated_by_admin) REFERENCES users(id),
        FOREIGN KEY (sent_by_admin) REFERENCES users(id)
    )";
    
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ voting_codes table created successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating table: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php?page=student_login'>Back to Student Login</a></p>";
echo "<p><a href='index.php?page=admin'>Back to Admin Dashboard</a></p>";
?>
