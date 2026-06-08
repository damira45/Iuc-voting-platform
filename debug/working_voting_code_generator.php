<?php
/**
 * Working voting code generator - simple and reliable
 */

require_once 'config/config.php';

echo "<h2>Working Voting Code Generator</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Simple function to generate voting code
function generateSimpleVotingCode($pdo, $student_id) {
    // Clear any existing '1' codes first
    $stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
    $stmt->execute(['1']);
    
    // Generate unique code
    do {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = 'VOTE-';
        for ($i = 0; $i < 16; $i++) {
            if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Double check it's not '1'
        if ($code === '1') continue;
        
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    // Insert with all required fields
    $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at, status) 
            VALUES (?, 1, ?, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent')";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$student_id, $code]);
    
    if ($success) {
        return $code;
    } else {
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_for_student'])) {
    $student_id = $_POST['student_id'];
    
    echo "<h3>Generating Code for Student ID: $student_id</h3>";
    
    $code = generateSimpleVotingCode($pdo, $student_id);
    
    if ($code) {
        // Get student details
        $stmt = $pdo->prepare("SELECT u.name, u.email, s.student_id FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store in session
        $_SESSION['generated_voting_code'] = [
            'code' => $code,
            'student_id' => $student_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'student_details' => $student
        ];
        
        echo "<div style='background: #d1fae5; color: #065f46; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
        echo "<h3 style='margin: 0 0 1rem 0; color: #059669;'>✓ SUCCESS! Voting Code Generated</h3>";
        echo "<div style='background: #1e293b; color: #10b981; padding: 1.5rem; border-radius: 8px; font-family: monospace; font-size: 1.4rem; text-align: center; margin: 1rem 0; letter-spacing: 2px; font-weight: bold;'>";
        echo $code;
        echo "</div>";
        echo "<div style='margin-top: 1rem;'>";
        echo "<p style='margin: 0.25rem 0;'><strong>Student:</strong> " . htmlspecialchars($student['name']) . "</p>";
        echo "<p style='margin: 0.25rem 0;'><strong>Email:</strong> " . htmlspecialchars($student['email']) . "</p>";
        echo "<p style='margin: 0.25rem 0;'><strong>Student ID:</strong> " . htmlspecialchars($student['student_id']) . "</p>";
        echo "<p style='margin: 0.25rem 0;'><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
        echo "</div>";
        echo "<div style='margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #10b981;'>";
        echo "<p style='margin: 0; font-size: 0.85rem; color: #059669;'>";
        echo "<i class='fas fa-info-circle'></i> Student can login at: <strong>index.php?page=student_login</strong><br>";
        echo "Use Student ID: <strong>" . htmlspecialchars($student['student_id']) . "</strong><br>";
        echo "Use Voting Code: <strong>$code</strong>";
        echo "</p>";
        echo "</div>";
        echo "</div>";
        
        echo "<p><strong><a href='index.php?page=student_login'>Test Student Login</a></strong></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to generate voting code</p>";
    }
}

// Show all students
echo "<h3>Generate Voting Code for Student:</h3>";
$stmt = $pdo->query("SELECT u.id, u.name, u.email, s.student_id FROM users u JOIN students s ON u.id = s.user_id WHERE u.type = 'student' ORDER BY u.created_at DESC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) > 0) {
    echo "<form method='POST' style='margin: 1rem 0; padding: 1rem; border: 1px solid #ccc; border-radius: 8px;'>";
    echo "<div style='margin: 0.5rem 0;'>";
    echo "<label>Select Student: </label>";
    echo "<select name='student_id' required style='padding: 0.5rem; margin-left: 0.5rem; min-width: 200px;'>";
    foreach ($students as $student) {
        echo "<option value='{$student['id']}'>{$student['name']} ({$student['student_id']})</option>";
    }
    echo "</select>";
    echo "</div>";
    echo "<button type='submit' name='generate_for_student' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; margin-top: 1rem;'>Generate Voting Code</button>";
    echo "</form>";
} else {
    echo "<p style='color: orange;'>No students found</p>";
}

// Show existing codes
echo "<h3>Existing Voting Codes:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name FROM voting_codes vc JOIN users u ON vc.student_id = u.id ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'><th style='padding: 0.5rem;'>Student</th><th style='padding: 0.5rem;'>Voting Code</th><th style='padding: 0.5rem;'>Status</th><th style='padding: 0.5rem;'>Created</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$code['student_name']}</td>";
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
echo "<p><strong><a href='index.php?page=voter_registration&student_id=9'>Test Voter Registration Page</a></strong></p>";
echo "<p><strong><a href='index.php?page=student_login'>Test Student Login</a></strong></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
