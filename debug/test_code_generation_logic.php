<?php
/**
 * Test the voting code generation logic directly
 */

require_once 'config/config.php';

echo "<h2>Test Voting Code Generation Logic</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Test the generateUniqueVotingCode function directly
echo "<h3>Testing generateUniqueVotingCode Function:</h3>";

try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($notificationManager);
    $method = $reflection->getMethod('generateUniqueVotingCode');
    $method->setAccessible(true);
    
    echo "<p>Calling generateUniqueVotingCode()...</p>";
    
    // Generate 5 codes to test
    for ($i = 0; $i < 5; $i++) {
        $code = $method->invoke($notificationManager);
        echo "<p>Generated Code $i: <code>$code</code></p>";
        
        // Check if it's a valid format
        if (preg_match('/^VOTE-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            echo "<p style='color: green;'>✓ Valid format</p>";
        } else {
            echo "<p style='color: red;'>✗ Invalid format</p>";
        }
        
        // Check if it's unique
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
        $stmt->execute([$code]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count == 0) {
            echo "<p style='color: green;'>✓ Unique in database</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Already exists in database</p>";
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Check what's currently in voting_codes table
echo "<h3>Current voting_codes Table:</h3>";
$stmt = $pdo->query("SELECT * FROM voting_codes ORDER BY created_at DESC LIMIT 10");
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
} else {
    echo "<p style='color: orange;'>No voting codes in database</p>";
}

// Test manual code generation
echo "<h3>Manual Code Generation Test:</h3>";

function manualGenerateCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'VOTE-';
    for ($i = 0; $i < 16; $i++) {
        if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

for ($i = 0; $i < 3; $i++) {
    $manualCode = manualGenerateCode();
    echo "<p>Manual Code $i: <code>$manualCode</code></p>";
}

echo "<hr>";
echo "<p><a href='debug_generate_button.php'>Back to Debug Generate Button</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
?>
