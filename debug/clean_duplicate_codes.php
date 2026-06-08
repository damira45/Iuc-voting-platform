<?php
/**
 * Clean up duplicate voting codes and test generation
 */

require_once 'config/config.php';

echo "<h2>Clean Duplicate Voting Codes</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show current voting codes
echo "<h3>Current Voting Codes:</h3>";
$stmt = $pdo->query("SELECT * FROM voting_codes ORDER BY created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student</th><th>Voting Code</th><th>Status</th><th>Created</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td>{$code['id']}</td>";
        echo "<td>{$code['student_id']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "<td>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Clean up all voting codes to start fresh
    echo "<h3>Cleaning up all voting codes...</h3>";
    $stmt = $pdo->exec("DELETE FROM voting_codes");
    echo "<p style='color: green;'>✓ All voting codes deleted</p>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Test new code generation
echo "<h3>Testing New Code Generation:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Get the first student
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE type = 'student' LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with student: {$student['name']}</p>";
        
        $votingCode = $notificationManager->generateVotingCode($student['id'], 1, 1);
        
        if ($votingCode) {
            echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! New Voting Code Generated</h4>";
            echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
            echo $votingCode;
            echo "</div>";
            echo "<p style='margin: 0.5rem 0 0 0; font-size: 0.9rem;'>Format: VOTE-XXXX-XXXX-XXXX-XXXX</p>";
            echo "</div>";
            
            // Check if saved properly
            $stmt = $pdo->prepare("SELECT * FROM voting_codes WHERE voting_code = ?");
            $stmt->execute([$votingCode]);
            $saved = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($saved) {
                echo "<p style='color: green;'>✓ Code saved to database properly</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Code generated but not saved to database</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to generate voting code</p>";
        }
    } else {
        echo "<p style='color: orange;'>No students found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_generate_code.php'>Test Code Generation Again</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
