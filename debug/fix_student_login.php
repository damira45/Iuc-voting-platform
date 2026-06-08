<?php
/**
 * Fix student login by removing problematic '1' code and testing with proper codes
 */

require_once 'config/config.php';

echo "<h2>Fix Student Login - Remove '1' Code and Test Proper Codes</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Step 1: Remove the problematic '1' code
echo "<h3>Step 1: Remove Problematic '1' Code</h3>";
$stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
$result = $stmt->execute(['1']);
$deleted = $stmt->rowCount();

if ($deleted > 0) {
    echo "<p style='color: green;'>✓ Deleted $deleted problematic voting code(s)</p>";
} else {
    echo "<p style='color: orange;'>No '1' codes found to delete</p>";
}

// Step 2: Show remaining proper voting codes
echo "<h3>Step 2: Available Voting Codes for Testing</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email as student_email, s.student_id 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    JOIN students s ON u.id = s.user_id 
                    WHERE vc.voting_code != '1'
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>Student</th>
            <th style='padding: 0.5rem;'>Student ID</th>
            <th style='padding: 0.5rem;'>Voting Code</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Test Link</th>
          </tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_id']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace; font-weight: bold; color: #10b981;'>" . htmlspecialchars($code['voting_code']) . "</td>";
        echo "<td style='padding: 0.5rem;'>{$code['status']}</td>";
        echo "<td style='padding: 0.5rem;'>";
        echo "<a href='test_login_code.php?code=" . urlencode($code['voting_code']) . "&student_id=" . urlencode($code['student_id']) . "' style='background: #10b981; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 3px; font-size: 0.8rem;'>Test Login</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Step 3: Test the most recent proper code
if (count($codes) > 0) {
    $latest_code = $codes[0];
    $test_voting_code = $latest_code['voting_code'];
    $test_student_id = $latest_code['student_id'];
    
    echo "<h3>Step 3: Test Latest Proper Voting Code</h3>";
    echo "<p><strong>Testing with:</strong></p>";
    echo "<p>Voting Code: <code style='color: #10b981; font-weight: bold;'>$test_voting_code</code></p>";
    echo "<p>Student ID: <code>$test_student_id</code></p>";
    echo "<p>Student Name: <strong>{$latest_code['student_name']}</strong></p>";
    
    // Test the exact login validation logic
    echo "<h4>Login Validation Test:</h4>";
    $stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, u.id as user_id, s.department, s.level 
                          FROM voting_codes vc
                          JOIN users u ON vc.student_id = u.id
                          JOIN students s ON u.id = s.user_id
                          WHERE vc.voting_code = ?");
    $stmt->execute([$test_voting_code]);
    $votingCodeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($votingCodeData) {
        echo "<p style='color: green;'>✓ Voting code found and valid</p>";
        
        // Check status
        if ($votingCodeData['status'] === 'sent') {
            echo "<p style='color: green;'>✓ Status is 'sent' - valid for login</p>";
        }
        
        // Check expiration
        if ($votingCodeData['expires_at'] && $votingCodeData['expires_at'] < date('Y-m-d H:i:s')) {
            echo "<p style='color: red;'>✗ Voting code has expired</p>";
        } else {
            echo "<p style='color: green;'>✓ Voting code has not expired</p>";
        }
        
        // Test student ID match
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? AND student_id = ?");
        $stmt->execute([$votingCodeData['user_id'], $test_student_id]);
        
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Student ID matches</p>";
            echo "<p style='color: green; font-weight: bold;'>✓ This voting code should work for login!</p>";
        } else {
            echo "<p style='color: red;'>✗ Student ID does not match</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Voting code not found</p>";
    }
    
    echo "<h4>Manual Login Instructions:</h4>";
    echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<p><strong>Go to:</strong> <a href='index.php?page=student_login'>index.php?page=student_login</a></p>";
    echo "<p><strong>Student ID:</strong> <code>$test_student_id</code></p>";
    echo "<p><strong>Voting Code:</strong> <code style='color: #10b981; font-weight: bold;'>$test_voting_code</code></p>";
    echo "<p><strong>This should work now that the '1' code is removed!</strong></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong><a href='index.php?page=student_login'>Test Student Login Now</a></strong></p>";
echo "<p><a href='working_voting_code_generator.php'>Generate New Code</a></p>";
echo "<p><a href='debug_student_login.php'>Back to Debug</a></p>";
?>
