<?php
/**
 * Debug the generate code button click
 */

require_once 'config/config.php';

echo "<h2>Debug Generate Code Button</h2>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['action']) && $_POST['action'] === 'generate_code') {
        echo "<h3>Generate Code Action Detected</h3>";
        
        $student_id = $_POST['student_id'] ?? null;
        $election_id = $_POST['election_id'] ?? 1;
        
        echo "<p><strong>Student ID:</strong> $student_id</p>";
        echo "<p><strong>Election ID:</strong> $election_id</p>";
        
        if ($student_id) {
            try {
                require_once 'includes/NotificationManager.php';
                $notificationManager = new NotificationManager($pdo);
                
                echo "<p>Calling NotificationManager->generateVotingCode...</p>";
                
                $voting_code = $notificationManager->generateVotingCode($student_id, $election_id, 1);
                
                if ($voting_code) {
                    echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
                    echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS!</h4>";
                    echo "<p><strong>Voting Code:</strong> <code>$voting_code</code></p>";
                    echo "</div>";
                    
                    // Check database
                    $stmt = $pdo->prepare("SELECT * FROM voting_codes WHERE voting_code = ?");
                    $stmt->execute([$voting_code]);
                    $saved = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($saved) {
                        echo "<p style='color: green;'>✓ Code saved to database (ID: {$saved['id']})</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠ Code not found in database</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ generateVotingCode returned false</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ No student_id provided</p>";
        }
    }
} else {
    echo "<p>No form submission detected</p>";
}

// Show available students
echo "<h3>Available Students:</h3>";
$stmt = $pdo->query("SELECT u.id, u.name, u.email, s.student_id 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student' 
                    ORDER BY u.created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    echo "<form method='POST' style='margin: 1rem 0; padding: 1rem; border: 1px solid #ccc; border-radius: 8px;'>";
    echo "<h4>Test Generate Code:</h4>";
    
    echo "<div style='margin: 0.5rem 0;'>";
    echo "<label>Student: </label>";
    echo "<select name='student_id' required style='padding: 0.5rem; margin-left: 0.5rem;'>";
    foreach ($students as $student) {
        echo "<option value='{$student['id']}'>{$student['name']} ({$student['student_id']})</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin: 0.5rem 0;'>";
    echo "<label>Election ID: </label>";
    echo "<input type='number' name='election_id' value='1' style='padding: 0.5rem; margin-left: 0.5rem;'>";
    echo "</div>";
    
    echo "<input type='hidden' name='action' value='generate_code'>";
    echo "<button type='submit' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; margin-top: 1rem;'>Generate Code Test</button>";
    echo "</form>";
} else {
    echo "<p style='color: orange;'>No students found</p>";
}

// Show existing codes
echo "<h3>Existing Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC LIMIT 5");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'><th style='padding: 0.5rem;'>Student</th><th style='padding: 0.5rem;'>Voting Code</th><th style='padding: 0.5rem;'>Status</th><th style='padding: 0.5rem;'>Created</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$code['name']}</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace;'>{$code['voting_code']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['status']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=voter_registration'>Back to Voter Registration</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
