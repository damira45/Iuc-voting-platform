<?php
/**
 * Debug Code Generation Comparison
 * Compare working manual code vs problematic automatic code generation
 */

require_once 'config/config.php';

echo "<h2>Debug Code Generation Comparison</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>Recent Voting Codes (All):</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email, s.student_id 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    JOIN students s ON u.id = s.user_id 
                    ORDER BY vc.created_at DESC 
                    LIMIT 10");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>Student</th>";
    echo "<th style='padding: 0.5rem;'>Code</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Created</th>";
    echo "<th style='padding: 0.5rem;'>Method</th>";
    echo "</tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace; font-weight: bold;'>" . htmlspecialchars($code['voting_code']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $code['status'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $code['created_at'] . "</td>";
        echo "<td style='padding: 0.5rem;'>";
        if (strpos($code['voting_code'], 'VOTE-') === 0) {
            echo "<span style='color: red;'>⚠ Invalid Format</span>";
        } else {
            echo "<span style='color: green;'>✓ Valid Format</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

echo "<h3>Test Manual Code Generation:</h3>";
echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Generate Test Code:</h4>";

// Manual code generation (like the working one)
function generateManualVotingCode($pdo, $student_id) {
    do {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = 'VOTE-';
        for ($i = 0; $i < 16; $i++) {
            if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Ensure it's not '1'
        if ($code === '1' || strlen($code) < 19) {
            continue;
        }
        
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    return $code;
}

// Get a test student
$stmt = $pdo->prepare("SELECT u.id, u.name, s.student_id FROM users u JOIN students s ON u.id = s.user_id WHERE u.type = 'student' LIMIT 1");
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    $manual_code = generateManualVotingCode($pdo, $student['id']);
    
    echo "<p><strong>Generated Manual Code:</strong> <code style='color: #10b981; font-size: 1.2rem; background: #1e293b; padding: 0.5rem; border-radius: 4px;'>$manual_code</code></p>";
    
    // Insert manually with proper status
    $insert_stmt = $pdo->prepare("INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at, status) 
                                         VALUES (?, 1, ?, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent')");
    $result = $insert_stmt->execute([$student['id'], $manual_code]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Manual code inserted successfully</p>";
        
        // Test the manual code immediately
        echo "<h4>Testing Manual Code:</h4>";
        
        $test_stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, s.student_id 
                                      FROM voting_codes vc
                                      JOIN users u ON vc.student_id = u.id
                                      JOIN students s ON u.id = s.user_id
                                      WHERE vc.voting_code = ?");
        $test_stmt->execute([$manual_code]);
        $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_result) {
            echo "<p style='color: green;'>✓ Manual code found and valid</p>";
            echo "<p><strong>Status:</strong> " . $test_result['status'] . "</p>";
            echo "<p><strong>Expires:</strong> " . ($test_result['expires_at'] ?? 'Never') . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Manual code not found</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to insert manual code</p>";
    }
    
    echo "</div>";
} else {
    echo "<p style='color: orange;'>No students found for testing</p>";
}

echo "<h3>Analysis:</h3>";
echo "<div style='background: #fef3c7; border: 1px solid #fbbf24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #92400e; margin: 0 0 1rem 0;'>Problem Identification:</h4>";
echo "<p><strong>Issue:</strong> Automatic code generation creates codes that student login rejects, but manual generation works</p>";
echo "<p><strong>Root Cause:</strong> Likely status or format issue in automatic generation</p>";
echo "<p><strong>Solution:</strong> Use manual generation method or fix automatic generation to match working method</p>";

echo "<h4 style='color: #92400e; margin: 1rem 0;'>Quick Fix Options:</h4>";
echo "<p><a href='working_voting_code_generator.php' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Use Working Generator</a></p>";
echo "<p><a href='index.php?page=student_login' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Test Student Login</a></p>";

echo "</div>";
?>
