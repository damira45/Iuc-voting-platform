<?php
/**
 * Permanent fix for voting code generation
 */

require_once 'config/config.php';

echo "<h2>Permanent Fix for Voting Code Generation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Clear any problematic codes
echo "<h3>Clearing Problematic Codes:</h3>";

// Delete voting code '1'
$stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
$result = $stmt->execute(['1']);
$deleted = $stmt->rowCount();

if ($deleted > 0) {
    echo "<p style='color: green;'>✓ Deleted $deleted problematic voting code(s)</p>";
} else {
    echo "<p style='color: orange;'>No problematic codes found</p>";
}

// Test the fixed function
echo "<h3>Testing Fixed generateUniqueVotingCode Function:</h3>";

try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Test the private function directly
    $reflection = new ReflectionClass($notificationManager);
    $method = $reflection->getMethod('generateUniqueVotingCode');
    $method->setAccessible(true);
    
    echo "<p>Testing fixed function...</p>";
    $code = $method->invoke($notificationManager);
    echo "<p>Generated code: <code>$code</code></p>";
    
    // Verify it's not '1'
    if ($code === '1') {
        echo "<p style='color: red; font-weight: bold;'>✗ STILL RETURNING '1' - FUNCTION NOT FIXED</p>";
    } else {
        echo "<p style='color: green;'>✓ Function working correctly - not '1'</p>";
        
        // Check format
        if (preg_match('/^VOTE-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            echo "<p style='color: green;'>✓ Valid format</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Invalid format but not '1'</p>";
        }
    }
    
    // Test full method with clair
    echo "<h3>Testing Full Method with Clair:</h3>";
    
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND type = 'student'");
    $stmt->execute([9]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with: {$student['name']}</p>";
        
        $voting_code = $notificationManager->generateVotingCode($student['id'], 1, 1);
        
        if ($voting_code) {
            echo "<p style='color: green;'>✓ Full method success: <code>$voting_code</code></p>";
            
            if ($voting_code === '1') {
                echo "<p style='color: red; font-weight: bold;'>✗ FULL METHOD STILL RETURNS '1'</p>";
            } else {
                echo "<p style='color: green;'>✓ Full method working correctly</p>";
                
                // Store for voter registration page
                $_SESSION['generated_voting_code'] = [
                    'code' => $voting_code,
                    'student_id' => $student['id'],
                    'generated_at' => date('Y-m-d H:i:s'),
                    'student_details' => [
                        'name' => $student['name'],
                        'email' => $student['email'],
                        'student_id' => 'IUC 2020 2020'
                    ]
                ];
                
                echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
                echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! Voting Code Generated</h4>";
                echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
                echo $voting_code;
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: red;'>✗ Full method failed</p>";
        }
    } else {
        echo "<p style='color: orange;'>Student not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong><a href='index.php?page=voter_registration&student_id=9'>Test Voter Registration Page with Clair</a></strong></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
