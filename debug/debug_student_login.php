<?php
/**
 * Debug student login voting code validation
 */

require_once 'config/config.php';

echo "<h2>Debug Student Login Voting Code Validation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show all voting codes in database
echo "<h3>All Voting Codes in Database:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email as student_email, s.student_id 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    JOIN students s ON u.id = s.user_id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>Student</th>
            <th style='padding: 0.5rem;'>Student ID</th>
            <th style='padding: 0.5rem;'>Voting Code</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Expires</th>
            <th style='padding: 0.5rem;'>Created</th>
          </tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_id']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace; font-weight: bold;'>" . htmlspecialchars($code['voting_code']) . "</td>";
        echo "<td style='padding: 0.5rem;'>";
        echo $code['status'];
        if ($code['status'] === 'sent') {
            echo " <span style='color: green;'>✓</span>";
        } else {
            echo " <span style='color: orange;'>⚠</span>";
        }
        echo "</td>";
        echo "<td style='padding: 0.5rem;'>" . ($code['expires_at'] ?? 'Never') . "</td>";
        echo "<td style='padding: 0.5rem;'>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found in database</p>";
}

// Test the exact login validation logic
echo "<h3>Test Login Validation Logic:</h3>";

// Get the most recent voting code for testing
if (count($codes) > 0) {
    $latest_code = $codes[0];
    $test_voting_code = $latest_code['voting_code'];
    $test_student_id = $latest_code['student_id'];
    
    echo "<p><strong>Testing with:</strong></p>";
    echo "<p>Voting Code: <code>$test_voting_code</code></p>";
    echo "<p>Student ID: <code>$test_student_id</code></p>";
    echo "<p>Student Name: <strong>{$latest_code['student_name']}</strong></p>";
    
    // Test the exact query from student login
    echo "<h4>Step 1: Check if voting code exists and is valid</h4>";
    $stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, u.id as user_id, s.department, s.level 
                          FROM voting_codes vc
                          JOIN users u ON vc.student_id = u.id
                          JOIN students s ON u.id = s.user_id
                          WHERE vc.voting_code = ?");
    $stmt->execute([$test_voting_code]);
    $votingCodeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($votingCodeData) {
        echo "<p style='color: green;'>✓ Voting code found in database</p>";
        echo "<pre>";
        print_r($votingCodeData);
        echo "</pre>";
        
        // Check status
        if ($votingCodeData['status'] === 'sent') {
            echo "<p style='color: green;'>✓ Status is 'sent' - valid for login</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Status is '{$votingCodeData['status']}' - may not be valid for login</p>";
        }
        
        // Check expiration
        if ($votingCodeData['expires_at'] && $votingCodeData['expires_at'] < date('Y-m-d H:i:s')) {
            echo "<p style='color: red;'>✗ Voting code has expired</p>";
        } else {
            echo "<p style='color: green;'>✓ Voting code has not expired</p>";
        }
        
        // Test student ID match
        echo "<h4>Step 2: Test Student ID Match</h4>";
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? AND student_id = ?");
        $stmt->execute([$votingCodeData['user_id'], $test_student_id]);
        
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Student ID matches</p>";
        } else {
            echo "<p style='color: red;'>✗ Student ID does not match</p>";
            
            // Show what student IDs are available for this user
            $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
            $stmt->execute([$votingCodeData['user_id']]);
            $available_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Available Student IDs for this user: " . implode(', ', $available_ids) . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Voting code not found in database</p>";
    }
    
    // Test with different student ID formats
    echo "<h4>Step 3: Test with Different Student ID Formats</h4>";
    $stmt = $pdo->prepare("SELECT s.student_id FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$latest_code['student_id']]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student_info) {
        echo "<p>Correct Student ID from database: <code>{$student_info['student_id']}</code></p>";
        echo "<p>Try logging in with this Student ID instead of: <code>$test_student_id</code></p>";
    }
    
} else {
    echo "<p style='color: orange;'>No voting codes available for testing</p>";
}

echo "<hr>";
echo "<p><strong>Student Login Page:</strong> <a href='index.php?page=student_login'>index.php?page=student_login</a></p>";
echo "<p><strong>Working Code Generator:</strong> <a href='working_voting_code_generator.php'>Generate New Code</a></p>";
?>
