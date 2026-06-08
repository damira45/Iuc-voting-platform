<?php
/**
 * Simple test for voting code generation
 */

require_once 'config/config.php';

echo "<h2>Test Voting Code Generation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_generate'])) {
    $student_id = $_POST['student_id'];
    
    echo "<h3>Testing Code Generation for Student ID: {$student_id}</h3>";
    
    try {
        require_once 'includes/NotificationManager.php';
        $notificationManager = new NotificationManager($pdo);
        
        echo "<p>Attempting to generate voting code...</p>";
        
        // Generate voting code
        $voting_code = $notificationManager->generateVotingCode($student_id, 1, 1);
        
        if ($voting_code) {
            echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! Voting Code Generated</h4>";
            echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
            echo $voting_code;
            echo "</div>";
            echo "<p style='margin: 0.5rem 0 0 0; font-size: 0.9rem;'>Share this code with the student for login.</p>";
            echo "</div>";
            
            // Check if saved to database
            $stmt = $pdo->prepare("SELECT * FROM voting_codes WHERE voting_code = ?");
            $stmt->execute([$voting_code]);
            $saved = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($saved) {
                echo "<p style='color: green;'>✓ Code saved to database (ID: {$saved['id']}, Status: {$saved['status']})</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Code generated but not found in database</p>";
            }
        } else {
            echo "<div style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h4 style='margin: 0 0 0.5rem 0;'>✗ FAILED TO GENERATE CODE</h4>";
            echo "<p>Could not generate voting code. Check database and NotificationManager.</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
        echo "<h4 style='margin: 0 0 0.5rem 0;'>✗ ERROR</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
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
    echo "<form method='POST' style='margin: 1rem 0;'>";
    echo "<select name='student_id' required style='padding: 0.5rem; margin-right: 1rem;'>";
    foreach ($students as $student) {
        echo "<option value='{$student['id']}'>{$student['name']} ({$student['student_id']})</option>";
    }
    echo "</select>";
    echo "<button type='submit' name='test_generate' style='background: #10b981; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Generate Test Code</button>";
    echo "</form>";
} else {
    echo "<p style='color: orange;'>No students found</p>";
}

// Show existing codes
echo "<h3>Existing Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name, u.email 
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
