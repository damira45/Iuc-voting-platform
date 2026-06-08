<?php
/**
 * Database Migration Script
 * Add missing columns to existing students table
 */

require_once 'config/config.php';

try {
    if (!$pdo) {
        die("Database connection failed");
    }
    
    echo "<h2>Database Migration</h2>";
    
    // Check if email column exists in students table
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'email'");
    $emailColumnExists = $stmt->rowCount() > 0;
    
    if (!$emailColumnExists) {
        echo "<p>Adding email column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN email VARCHAR(191) UNIQUE AFTER name");
        echo "<p>✓ Email column added</p>";
    } else {
        echo "<p>✓ Email column already exists</p>";
    }
    
    // Check if voting_code column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'voting_code'");
    $votingCodeColumnExists = $stmt->rowCount() > 0;
    
    if (!$votingCodeColumnExists) {
        echo "<p>Adding voting_code column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN voting_code VARCHAR(50) UNIQUE AFTER year");
        echo "<p>✓ Voting code column added</p>";
    } else {
        echo "<p>✓ Voting code column already exists</p>";
    }
    
    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'status'");
    $statusColumnExists = $stmt->rowCount() > 0;
    
    if (!$statusColumnExists) {
        echo "<p>Adding status column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER voting_code");
        echo "<p>✓ Status column added</p>";
    } else {
        echo "<p>✓ Status column already exists</p>";
    }
    
    // Check if email_sent column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'email_sent'");
    $emailSentColumnExists = $stmt->rowCount() > 0;
    
    if (!$emailSentColumnExists) {
        echo "<p>Adding email_sent column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN email_sent BOOLEAN DEFAULT FALSE AFTER status");
        echo "<p>✓ Email sent column added</p>";
    } else {
        echo "<p>✓ Email sent column already exists</p>";
    }
    
    // Check if year column exists (some databases might have 'level' instead)
    $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'year'");
    $yearColumnExists = $stmt->rowCount() > 0;
    
    if (!$yearColumnExists) {
        echo "<p>Adding year column to students table...</p>";
        $pdo->exec("ALTER TABLE students ADD COLUMN year VARCHAR(50) AFTER department");
        echo "<p>✓ Year column added</p>";
    } else {
        echo "<p>✓ Year column already exists</p>";
    }
    
    // Generate voting codes for existing students that don't have one
    $stmt = $pdo->query("SELECT id, student_id FROM students WHERE voting_code IS NULL OR voting_code = ''");
    $studentsWithoutCode = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($studentsWithoutCode) > 0) {
        echo "<p>Generating voting codes for " . count($studentsWithoutCode) . " existing students...</p>";
        
        foreach ($studentsWithoutCode as $student) {
            $votingCode = generateVotingCode();
            $stmt = $pdo->prepare("UPDATE students SET voting_code = ? WHERE id = ?");
            $stmt->execute([$votingCode, $student['id']]);
            echo "<p>✓ Generated voting code for student ID: " . htmlspecialchars($student['student_id']) . "</p>";
        }
    } else {
        echo "<p>✓ All students already have voting codes</p>";
    }
    
    echo "<h3>Migration completed successfully!</h3>";
    echo "<p><a href='index.php?page=voter_registration'>Go to Voter Registration</a></p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

function generateVotingCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'VOTE-';
    for ($i = 0; $i < 16; $i++) {
        if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}
?>
