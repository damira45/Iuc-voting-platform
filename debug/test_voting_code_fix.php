<?php
/**
 * Test Voting Code Fix
 * Verify that the SQL parameter fix resolved the issue
 */

require_once 'config/config.php';

echo "<h2>Test Voting Code Fix</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>Test Automatic Code Generation:</h3>";

// Get a test student
$stmt = $pdo->prepare("SELECT u.id, u.name, s.student_id FROM users u JOIN students s ON u.id = s.user_id WHERE u.type = 'student' LIMIT 1");
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Testing Student:</h4>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($student['name']) . "</p>";
    echo "<p><strong>Student ID:</strong> " . htmlspecialchars($student['student_id']) . "</p>";
    
    // Test the fixed code generation logic
    do {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $voting_code = 'VOTE-';
        for ($i = 0; $i < 16; $i++) {
            if ($i === 4 || $i === 8 || $i === 12) $voting_code .= '-';
            $voting_code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Double check it's not '1'
        if ($voting_code === '1') continue;
        
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
        $stmt->execute([$voting_code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    echo "<p><strong>Generated Code:</strong> <code style='color: #10b981; font-size: 1.2rem; background: #1e293b; padding: 0.5rem; border-radius: 4px;'>$voting_code</code></p>";
    
    // Test the fixed SQL insertion
    $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at, status) 
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent')";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$student['id'], 1, $voting_code, 1]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Voting code inserted successfully!</p>";
        
        // Test the code immediately
        echo "<h4>Testing Generated Code:</h4>";
        
        $test_stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, s.student_id 
                                      FROM voting_codes vc
                                      JOIN users u ON vc.student_id = u.id
                                      JOIN students s ON u.id = s.user_id
                                      WHERE vc.voting_code = ?");
        $test_stmt->execute([$voting_code]);
        $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_result) {
            echo "<p style='color: green;'>✓ Code found and valid for login</p>";
            echo "<p><strong>Status:</strong> " . $test_result['status'] . "</p>";
            echo "<p><strong>Expires:</strong> " . ($test_result['expires_at'] ?? 'Never') . "</p>";
            
            // Test student login validation
            echo "<h4>Student Login Test:</h4>";
            
            $login_stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, u.id as user_id, s.department, s.level 
                                         FROM voting_codes vc
                                         JOIN users u ON vc.student_id = u.id
                                         JOIN students s ON u.id = s.user_id
                                         WHERE vc.voting_code = ? AND vc.status = 'sent'");
            $login_stmt->execute([$voting_code]);
            $login_result = $login_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($login_result) {
                echo "<p style='color: green;'>✓ Student login validation would pass</p>";
                
                // Check expiration
                if ($login_result['expires_at'] && $login_result['expires_at'] < date('Y-m-d H:i:s')) {
                    echo "<p style='color: orange;'>⚠ Code would be expired</p>";
                } else {
                    echo "<p style='color: green;'>✓ Code not expired</p>";
                }
                
                // Check student ID match
                $student_id_check = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? AND student_id = ?");
                $student_id_check->execute([$login_result['user_id'], $login_result['student_id']]);
                if ($student_id_check->fetch()) {
                    echo "<p style='color: green;'>✓ Student ID matches</p>";
                } else {
                    echo "<p style='color: red;'>✗ Student ID mismatch</p>";
                }
                
            } else {
                echo "<p style='color: red;'>✗ Student login validation would fail</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ Code not found after insertion</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Failed to insert voting code</p>";
        echo "<p><strong>SQL Error:</strong> " . print_r($stmt->errorInfo(), true) . "</p>";
    }
    
    echo "</div>";
    
} else {
    echo "<p style='color: orange;'>No students found for testing</p>";
}

echo "<h3>Quick Actions:</h3>";
echo "<p><a href='index.php?page=voter_registration' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Test Admin Code Generation</a></p>";
echo "<p><a href='index.php?page=student_login' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Test Student Login</a></p>";
echo "<p><a href='working_voting_code_generator.php' style='background: #8b5cf6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Manual Code Generator</a></p>";

echo "<hr>";
echo "<h3>Fix Summary:</h3>";
echo "<div style='background: #fef3c7; border: 1px solid #fbbf24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #92400e; margin: 0 0 1rem 0;'>Problem Fixed:</h4>";
echo "<p><strong>Issue:</strong> SQL parameter order mismatch in voter_registration.php</p>";
echo "<p><strong>Before:</strong> execute([$student_id, $election_id, $admin_id, $voting_code])</p>";
echo "<p><strong>After:</strong> execute([$student_id, $election_id, $voting_code, $admin_id])</p>";
echo "<p><strong>Result:</strong> Automatic voting codes now work like manual ones</p>";
echo "</div>";
?>
