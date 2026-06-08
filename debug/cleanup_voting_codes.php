<?php
/**
 * Clean up the problematic voting code '1' from database
 */

require_once 'config/config.php';

echo "<h2>Clean Up Voting Codes</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show current voting codes
echo "<h3>Current Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name, u.email 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student</th><th>Voting Code</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td>{$code['id']}</td>";
        echo "<td>{$code['name']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "<td>{$code['created_at']}</td>";
        echo "<td>";
        if ($code['voting_code'] === '1') {
            echo "<span style='color: red;'>PROBLEMATIC</span>";
        } else {
            echo "<span style='color: green;'>OK</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Clean up the problematic code '1'
    echo "<h3>Cleaning Up Problematic Code '1'...</h3>";
    $stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
    $result = $stmt->execute(['1']);
    
    if ($result) {
        $deleted = $stmt->rowCount();
        echo "<p style='color: green;'>✓ Deleted $deleted problematic voting code(s)</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to delete problematic codes</p>";
    }
    
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Test code generation after cleanup
echo "<h3>Test Code Generation After Cleanup:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Get first student
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE type = 'student' LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with student: {$student['name']}</p>";
        
        $voting_code = $notificationManager->generateVotingCode($student['id'], 1, 1);
        
        if ($voting_code) {
            echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! Voting Code Generated</h4>";
            echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
            echo $voting_code;
            echo "</div>";
            echo "<p style='margin: 0.5rem 0 0 0; font-size: 0.9rem;'>Format: VOTE-XXXX-XXXX-XXXX-XXXX</p>";
            echo "</div>";
            
            // Store in session for voter registration page display
            $_SESSION['generated_voting_code'] = [
                'code' => $voting_code,
                'student_id' => $student['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'student_details' => [
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'student_id' => 'TEST-' . $student['id']
                ]
            ];
            
            echo "<p style='color: green;'>✓ Code stored in session for voter registration page</p>";
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
echo "<p><strong><a href='index.php?page=voter_registration'>Test Voter Registration Page</a></strong></p>";
echo "<p><a href='debug_generate_button.php'>Debug Generate Button</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
