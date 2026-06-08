<?php
/**
 * Clean up duplicate voting codes and fix generation
 */

require_once 'config/config.php';

echo "<h2>Clean Voting Codes</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show current voting codes
echo "<h3>Current Voting Codes:</h3>";
$stmt = $pdo->query("SELECT * FROM voting_codes ORDER BY created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Voting Code</th><th>Status</th><th>Created</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td>{$code['id']}</td>";
        echo "<td>{$code['student_id']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "<td>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Clean up all voting codes
    echo "<h3>Cleaning up all voting codes...</h3>";
    $stmt = $pdo->exec("DELETE FROM voting_codes");
    echo "<p style='color: green;'>✓ All voting codes deleted</p>";
    
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Create a simple voting code generation function
function generateSimpleVotingCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'VOTE-';
    for ($i = 0; $i < 16; $i++) {
        if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// Get students and generate new codes
echo "<h3>Generating New Voting Codes:</h3>";
$stmt = $pdo->query("SELECT u.id, u.name, u.email, s.student_id 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    WHERE u.type = 'student'");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($students as $student) {
    // Generate unique voting code
    do {
        $votingCode = generateSimpleVotingCode();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
        $stmt->execute([$votingCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    } while ($result['count'] > 0);
    
    // Insert voting code
    $stmt = $pdo->prepare("INSERT INTO voting_codes (student_id, election_id, voting_code, status, created_at, expires_at, generated_by_admin, sent_by_admin) 
                          VALUES (?, 1, ?, 'sent', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 1)");
    $result = $stmt->execute([$student['id'], $votingCode]);
    
    if ($result) {
        echo "<div style='background: #d1fae5; color: #065f46; padding: 0.5rem; margin: 0.5rem 0; border-radius: 4px;'>";
        echo "<strong>{$student['name']}</strong> ({$student['student_id']}) - <code>{$votingCode}</code> ✓</div>";
    }
}

echo "<hr>";
echo "<p><a href='index.php?page=student_login'>Test Student Login</a></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
