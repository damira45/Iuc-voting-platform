<?php
/**
 * Debug student login process step by step
 */

require_once 'config/config.php';

echo "<h2>Student Login Debug</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Simulate login with the voting code you received
$votingCode = isset($_GET['code']) ? $_GET['code'] : '';
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : '';

echo "<h3>Testing Login Process:</h3>";

if ($votingCode && $studentId) {
    echo "<p><strong>Voting Code:</strong> {$votingCode}</p>";
    echo "<p><strong>Student ID:</strong> {$studentId}</p>";
    
    // Step 1: Check if voting code exists
    echo "<h4>Step 1: Check voting code exists</h4>";
    $stmt = $pdo->prepare("SELECT * FROM voting_codes WHERE voting_code = ?");
    $stmt->execute([$votingCode]);
    $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($codeData) {
        echo "<p style='color: green;'>✓ Voting code found in database</p>";
        echo "<pre>" . print_r($codeData, true) . "</pre>";
        
        // Step 2: Check if user exists
        echo "<h4>Step 2: Check user exists</h4>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$codeData['student_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            echo "<p style='color: green;'>✓ User found in database</p>";
            echo "<pre>" . print_r($userData, true) . "</pre>";
            
            // Step 3: Check student record
            echo "<h4>Step 3: Check student record</h4>";
            $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
            $stmt->execute([$userData['id']]);
            $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($studentData) {
                echo "<p style='color: green;'>✓ Student record found in database</p>";
                echo "<pre>" . print_r($studentData, true) . "</pre>";
                
                // Step 4: Check student ID match
                echo "<h4>Step 4: Check student ID match</h4>";
                if ($studentData['student_id'] === $studentId) {
                    echo "<p style='color: green;'>✓ Student ID matches</p>";
                    
                    // Step 5: Check voting code status
                    echo "<h4>Step 5: Check voting code status</h4>";
                    if ($codeData['status'] === 'sent') {
                        echo "<p style='color: green;'>✓ Voting code status is 'sent'</p>";
                        
                        // Step 6: Check expiration
                        echo "<h4>Step 6: Check expiration</h4>";
                        if ($codeData['expires_at'] && strtotime($codeData['expires_at']) > time()) {
                            echo "<p style='color: green;'>✓ Voting code not expired</p>";
                            echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>✓ LOGIN SHOULD WORK!</p>";
                        } else {
                            echo "<p style='color: orange;'>⚠ Voting code expired or no expiration set</p>";
                        }
                    } else {
                        echo "<p style='color: orange;'>⚠ Voting code status is: {$codeData['status']}</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ Student ID mismatch</p>";
                    echo "<p>Expected: {$studentData['student_id']}</p>";
                    echo "<p>Provided: {$studentId}</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ Student record not found</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ User not found in database</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Voting code not found in database</p>";
    }
} else {
    echo "<p>Please provide voting code and student ID in URL parameters:</p>";
    echo "<p>Example: debug_login.php?code=VOTE-XXXX-XXXX-XXXX-XXXX&student_id=IUC-2024-1234</p>";
}

// Show all voting codes
echo "<h3>All Voting Codes in Database:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name, u.email, s.student_id 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    JOIN students s ON u.id = s.user_id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1'><tr><th>Name</th><th>Student ID</th><th>Voting Code</th><th>Status</th><th>Expires</th><th>Test Link</th></tr>";
    foreach ($codes as $code) {
        $testLink = "debug_login.php?code=" . urlencode($code['voting_code']) . "&student_id=" . urlencode($code['student_id']);
        echo "<tr>";
        echo "<td>{$code['name']}</td>";
        echo "<td>{$code['student_id']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "<td>{$code['expires_at']}</td>";
        echo "<td><a href='{$testLink}'>Test</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=student_login'>Student Login</a></p>";
echo "<p><a href='clean_voting_codes.php'>Clean Voting Codes</a></p>";
?>
