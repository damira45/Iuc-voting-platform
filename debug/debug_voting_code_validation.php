<?php
/**
 * Debug Voting Code Validation Issue
 * Check why valid voting codes are being rejected
 */

require_once 'config/config.php';

echo "<h2>Debug Voting Code Validation Issue</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>Recent Voting Codes Generated:</h3>";
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
    echo "<th style='padding: 0.5rem;'>Student ID</th>";
    echo "<th style='padding: 0.5rem;'>Voting Code</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Created</th>";
    echo "<th style='padding: 0.5rem;'>Expires</th>";
    echo "</tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_id']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace; font-weight: bold; color: #10b981;'>" . htmlspecialchars($code['voting_code']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $code['status'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $code['created_at'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . ($code['expires_at'] ?? 'Never') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

echo "<h3>Test Voting Code Validation:</h3>";

if (count($codes) > 0) {
    $latest_code = $codes[0];
    echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Testing with Latest Code:</h4>";
    echo "<p><strong>Student:</strong> " . htmlspecialchars($latest_code['student_name']) . "</p>";
    echo "<p><strong>Student ID:</strong> " . htmlspecialchars($latest_code['student_id']) . "</p>";
    echo "<p><strong>Voting Code:</strong> <code style='color: #10b981; font-size: 1.2rem;'>" . htmlspecialchars($latest_code['voting_code']) . "</code></p>";
    echo "<p><strong>Status:</strong> " . $latest_code['status'] . "</p>";
    echo "<p><strong>Created:</strong> " . $latest_code['created_at'] . "</p>";
    echo "<p><strong>Expires:</strong> " . ($latest_code['expires_at'] ?? 'Never') . "</p>";
    
    // Test the validation logic manually
    echo "<h4>Manual Validation Test:</h4>";
    
    // Check if voting code exists
    $stmt = $pdo->prepare("SELECT vc.*, u.name, u.email, u.id as user_id, s.department, s.level 
                          FROM voting_codes vc
                          JOIN users u ON vc.student_id = u.id
                          JOIN students s ON u.id = s.user_id
                          WHERE vc.voting_code = ?");
    $stmt->execute([$latest_code['voting_code']]);
    $votingCodeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($votingCodeData) {
        echo "<p style='color: green;'>✓ Voting code found in database</p>";
        
        // Check status
        if ($votingCodeData['status'] === 'sent') {
            echo "<p style='color: green;'>✓ Status is 'sent' - should be valid</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Status is '{$votingCodeData['status']}' - may not be valid</p>";
        }
        
        // Check expiration
        if ($votingCodeData['expires_at'] && $votingCodeData['expires_at'] < date('Y-m-d H:i:s')) {
            echo "<p style='color: red;'>✗ Voting code has expired</p>";
        } else {
            echo "<p style='color: green;'>✓ Voting code has not expired</p>";
        }
        
        // Test student ID match
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ? AND student_id = ?");
        $stmt->execute([$votingCodeData['user_id'], $latest_code['student_id']]);
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✓ Student ID matches</p>";
        } else {
            echo "<p style='color: red;'>✗ Student ID does not match</p>";
            echo "<p><strong>Expected:</strong> " . htmlspecialchars($latest_code['student_id']) . "</p>";
            echo "<p><strong>Available Student IDs for User {$votingCodeData['user_id']}:</strong> ";
            $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
            $stmt->execute([$votingCodeData['user_id']]);
            $available_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo implode(', ', $available_ids);
            echo "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Voting code not found in database</p>";
    }
    
    echo "</div>";
} else {
    echo "<p style='color: orange;'>No voting codes to test</p>";
}

echo "<hr>";
echo "<p><strong>Quick Actions:</strong></p>";
echo "<p><a href='working_voting_code_generator.php' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Generate New Voting Code</a></p>";
echo "<p><a href='index.php?page=student_login' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Test Student Login</a></p>";
echo "<p><a href='fix_student_login.php' style='background: #8b5cf6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Fix Student Login Issues</a></p>";
?>
