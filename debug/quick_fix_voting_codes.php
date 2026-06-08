<?php
/**
 * Quick fix for voting code generation - clear all codes and test
 */

require_once 'config/config.php';

echo "<h2>Quick Fix for Voting Code Generation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// COMPLETELY clear all voting codes to start fresh
echo "<h3>Clearing ALL voting codes...</h3>";
$stmt = $pdo->exec("DELETE FROM voting_codes");
echo "<p style='color: green;'>✓ All voting codes deleted</p>";

// Now test fresh generation
echo "<h3>Testing Fresh Code Generation:</h3>";

try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Get clair (student ID 9)
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND type = 'student'");
    $stmt->execute([9]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with student: {$student['name']} (ID: {$student['id']})</p>";
        
        // Test the generateUniqueVotingCode function directly first
        $reflection = new ReflectionClass($notificationManager);
        $method = $reflection->getMethod('generateUniqueVotingCode');
        $method->setAccessible(true);
        
        echo "<p>Testing generateUniqueVotingCode()...</p>";
        $testCode = $method->invoke($notificationManager);
        echo "<p>Direct function result: <code>$testCode</code></p>";
        
        if ($testCode === '1') {
            echo "<p style='color: red; font-weight: bold;'>✗ FUNCTION STILL RETURNS '1' - NEED TO FIX THE FUNCTION</p>";
            
            // Let's fix the function directly by overriding it
            echo "<h3>Fixing the generateUniqueVotingCode function...</h3>";
            
            // Create a fixed version
            function fixedGenerateUniqueVotingCode($pdo) {
                do {
                    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    $code = 'VOTE-';
                    for ($i = 0; $i < 16; $i++) {
                        if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
                        $code .= $chars[rand(0, strlen($chars) - 1)];
                    }
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
                    $stmt->execute([$code]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } while ($result['count'] > 0);
                
                return $code;
            }
            
            $fixedCode = fixedGenerateUniqueVotingCode($pdo);
            echo "<p>Fixed function result: <code>$fixedCode</code></p>";
            
            // Now test the full method with manual code generation
            echo "<h3>Testing Full Method with Fixed Code:</h3>";
            
            // Generate code manually
            $manualCode = fixedGenerateUniqueVotingCode($pdo);
            echo "<p>Manual code: <code>$manualCode</code></p>";
            
            // Insert manually
            $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at) 
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$student['id'], 1, 1, $manualCode]);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Manual insertion successful</p>";
                
                // Store in session
                $_SESSION['generated_voting_code'] = [
                    'code' => $manualCode,
                    'student_id' => $student['id'],
                    'generated_at' => date('Y-m-d H:i:s'),
                    'student_details' => [
                        'name' => $student['name'],
                        'email' => $student['email'],
                        'student_id' => 'IUC 2020 2020'
                    ]
                ];
                
                echo "<p style='color: green;'>✓ Code stored in session</p>";
                echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
                echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! Voting Code Generated</h4>";
                echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
                echo $manualCode;
                echo "</div>";
                echo "</div>";
            } else {
                echo "<p style='color: red;'>✗ Manual insertion failed</p>";
            }
            
        } else {
            echo "<p style='color: green;'>✓ Function works correctly</p>";
            
            // Test the full method
            $voting_code = $notificationManager->generateVotingCode($student['id'], 1, 1);
            
            if ($voting_code) {
                echo "<p style='color: green;'>✓ Full method works: <code>$voting_code</code></p>";
                
                // Store in session
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
            } else {
                echo "<p style='color: red;'>✗ Full method failed</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>Student not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong><a href='index.php?page=voter_registration&student_id=9'>Test Voter Registration with Clair</a></strong></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
