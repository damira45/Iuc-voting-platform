<?php
/**
 * Regenerate voting code for existing student
 */

require_once 'config/config.php';
require_once 'includes/NotificationManager.php';

echo "<h2>Regenerate Voting Code</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Get all students
$stmt = $pdo->query("SELECT u.id, u.name, u.email, s.student_id, s.department, s.level 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student' 
                    ORDER BY u.created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    echo "<h3>Available Students:</h3>";
    echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Student ID</th><th>Department</th><th>Action</th></tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td>{$student['name']}</td>";
        echo "<td>{$student['email']}</td>";
        echo "<td>{$student['student_id']}</td>";
        echo "<td>{$student['department']}</td>";
        echo "<td>";
        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='student_id' value='{$student['id']}'>";
        echo "<button type='submit' name='generate_code' class='btn btn-primary'>Generate Code</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Handle code generation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
        $studentId = $_POST['student_id'];
        $adminId = 1; // Assuming admin ID is 1
        
        try {
            $notificationManager = new NotificationManager($pdo);
            
            // Get student details
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate voting code
            $votingCode = $notificationManager->generateVotingCode($studentId, 1, $adminId);
            
            if ($votingCode) {
                // Mark as sent immediately for testing
                $stmt = $pdo->prepare("UPDATE voting_codes SET status = 'sent', sent_at = NOW(), sent_by_admin = ? WHERE voting_code = ?");
                $stmt->execute([$adminId, $votingCode]);
                
                echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
                echo "<h3 style='margin: 0 0 0.5rem 0;'>✓ Voting Code Generated Successfully!</h3>";
                echo "<p style='margin: 0.5rem 0;'><strong>Student:</strong> {$student['name']}</p>";
                echo "<p style='margin: 0.5rem 0;'><strong>Email:</strong> {$student['email']}</p>";
                echo "<p style='margin: 0.5rem 0;'><strong>Voting Code:</strong> <code style='background: #1e293b; color: #10b981; padding: 0.5rem; border-radius: 4px; font-family: monospace;'>{$votingCode}</code></p>";
                echo "<p style='margin: 0.5rem 0; font-size: 0.9rem;'>Student can now login with this voting code at: <a href='index.php?page=student_login'>Student Login</a></p>";
                echo "</div>";
                
                // Refresh the page to show updated data
                echo "<script>setTimeout(() => window.location.reload(), 3000);</script>";
            } else {
                echo "<p style='color: red;'>Failed to generate voting code</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Show existing voting codes
    echo "<h3>Existing Voting Codes:</h3>";
    $stmt = $pdo->query("SELECT vc.*, u.name, u.email, s.student_id 
                        FROM voting_codes vc 
                        JOIN users u ON vc.student_id = u.id 
                        JOIN students s ON u.id = s.user_id 
                        ORDER BY vc.created_at DESC");
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($codes) > 0) {
        echo "<table border='1'><tr><th>Student</th><th>Voting Code</th><th>Status</th><th>Created</th><th>Expires</th></tr>";
        foreach ($codes as $code) {
            echo "<tr>";
            echo "<td>{$code['name']} ({$code['student_id']})</td>";
            echo "<td><code>{$code['voting_code']}</code></td>";
            echo "<td>{$code['status']}</td>";
            echo "<td>{$code['created_at']}</td>";
            echo "<td>{$code['expires_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No voting codes found</p>";
    }
    
} else {
    echo "<p style='color: orange;'>No students found in database</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=student_login'>Student Login</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='debug_voting_codes.php'>Debug Voting Codes</a></p>";
?>
