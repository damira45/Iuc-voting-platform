<?php
/**
 * Debug voting code generation process
 */

require_once 'config/config.php';

echo "<h2>Debug Voting Code Generation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check if we have the student_id parameter
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : null;

if ($studentId) {
    echo "<h3>Testing Code Generation for Student ID: {$studentId}</h3>";
    
    // Get student details
    $stmt = $pdo->prepare("SELECT u.*, s.student_id, s.department, s.level 
                          FROM users u 
                          JOIN students s ON u.id = s.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p><strong>Student:</strong> {$student['name']}</p>";
        echo "<p><strong>Email:</strong> {$student['email']}</p>";
        echo "<p><strong>Student ID:</strong> {$student['student_id']}</p>";
        
        // Test NotificationManager
        echo "<h3>Testing NotificationManager:</h3>";
        try {
            require_once 'includes/NotificationManager.php';
            $notificationManager = new NotificationManager($pdo);
            
            // Test voting code generation
            echo "<p>Attempting to generate voting code...</p>";
            $votingCode = $notificationManager->generateVotingCode($studentId, 1, 1); // election_id=1, admin_id=1
            
            if ($votingCode) {
                echo "<p style='color: green;'>✓ Voting code generated: <code>{$votingCode}</code></p>";
                
                // Check if it was saved to database
                $stmt = $pdo->prepare("SELECT * FROM voting_codes WHERE voting_code = ?");
                $stmt->execute([$votingCode]);
                $savedCode = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($savedCode) {
                    echo "<p style='color: green;'>✓ Voting code saved to database</p>";
                    echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Voting Code</th><th>Status</th><th>Created</th></tr>";
                    echo "<tr>";
                    echo "<td>{$savedCode['id']}</td>";
                    echo "<td>{$savedCode['student_id']}</td>";
                    echo "<td><code>{$savedCode['voting_code']}</code></td>";
                    echo "<td>{$savedCode['status']}</td>";
                    echo "<td>{$savedCode['created_at']}</td>";
                    echo "</tr></table>";
                } else {
                    echo "<p style='color: red;'>✗ Voting code not found in database after generation</p>";
                }
                
                // Test sending code
                echo "<h3>Testing Code Sending:</h3>";
                $sendResult = $notificationManager->sendVotingCodeToStudent($studentId, $votingCode, 1);
                
                if ($sendResult) {
                    echo "<p style='color: green;'>✓ Voting code sent successfully</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to send voting code</p>";
                }
                
            } else {
                echo "<p style='color: red;'>✗ Failed to generate voting code</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Student not found with ID: {$studentId}</p>";
    }
} else {
    echo "<p>No student_id parameter provided</p>";
    
    // Show available students
    echo "<h3>Available Students:</h3>";
    $stmt = $pdo->query("SELECT u.id, u.name, u.email, s.student_id 
                        FROM users u 
                        JOIN students s ON u.id = s.user_id 
                        WHERE u.type = 'student' 
                        ORDER BY u.created_at DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Student ID</th><th>Test Link</th></tr>";
        foreach ($students as $student) {
            $testLink = "debug_code_generation.php?student_id=" . $student['id'];
            echo "<tr>";
            echo "<td>{$student['id']}</td>";
            echo "<td>{$student['name']}</td>";
            echo "<td>{$student['email']}</td>";
            echo "<td>{$student['student_id']}</td>";
            echo "<td><a href='{$testLink}'>Test Code Generation</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No students found</p>";
    }
}

// Show existing voting codes
echo "<h3>Existing Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name, u.email 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student</th><th>Voting Code</th><th>Status</th><th>Created</th><th>Expires</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td>{$code['id']}</td>";
        echo "<td>{$code['name']}</td>";
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

echo "<hr>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
