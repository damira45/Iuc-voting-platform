<?php
/**
 * Debug the voting code generation directly to find why it's creating '1'
 */

require_once 'config/config.php';

echo "<h2>Debug Voting Code Generation Directly</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check what's in the voting_codes table right now
echo "<h3>Current voting_codes Table:</h3>";
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
} else {
    echo "<p style='color: green;'>No voting codes in database</p>";
}

// Test the generateUniqueVotingCode function step by step
echo "<h3>Step-by-Step Code Generation Test:</h3>";

try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($notificationManager);
    $method = $reflection->getMethod('generateUniqueVotingCode');
    $method->setAccessible(true);
    
    echo "<p>Step 1: Calling generateUniqueVotingCode()...</p>";
    $code = $method->invoke($notificationManager);
    echo "<p>Generated code: <code>$code</code></p>";
    
    // Check if it's a valid format
    if ($code === '1') {
        echo "<p style='color: red; font-weight: bold;'>✗ PROBLEM: Function returned '1' instead of proper voting code!</p>";
        
        // Let's debug what's happening in the function
        echo "<h3>Debugging the generateUniqueVotingCode Function:</h3>";
        echo "<p>Let's create a manual version to see what's wrong...</p>";
        
        // Manual implementation
        function manualGenerateCode() {
            echo "<p>Manual generation starting...</p>";
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = 'VOTE-';
            for ($i = 0; $i < 16; $i++) {
                if ($i === 4 || $i === 8 || $i === 12) $code .= '-';
                $code .= $chars[rand(0, strlen($chars) - 1)];
            }
            echo "<p>Manual code generated: <code>$code</code></p>";
            return $code;
        }
        
        $manualCode = manualGenerateCode();
        echo "<p>Manual function works: <code>$manualCode</code></p>";
        
    } else {
        echo "<p style='color: green;'>✓ Function returned proper voting code</p>";
        
        // Check format
        if (preg_match('/^VOTE-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code)) {
            echo "<p style='color: green;'>✓ Valid format</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Invalid format</p>";
        }
    }
    
    // Test the full generateVotingCode method
    echo "<h3>Testing Full generateVotingCode Method:</h3>";
    
    // Get a student
    $stmt = $pdo->query("SELECT id, name FROM users WHERE type = 'student' LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with student: {$student['name']} (ID: {$student['id']})</p>";
        
        // Clear any existing codes first
        $stmt = $pdo->exec("DELETE FROM voting_codes WHERE voting_code = '1'");
        echo "<p>Cleared any existing '1' codes</p>";
        
        $voting_code = $notificationManager->generateVotingCode($student['id'], 1, 1);
        
        if ($voting_code) {
            echo "<p style='color: green;'>✓ Full method returned: <code>$voting_code</code></p>";
            
            if ($voting_code === '1') {
                echo "<p style='color: red; font-weight: bold;'>✗ STILL GENERATING '1' - There's a bug in the method!</p>";
            } else {
                echo "<p style='color: green;'>✓ Proper voting code generated!</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Full method returned false</p>";
        }
    } else {
        echo "<p style='color: orange;'>No students found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='final_cleanup_voting_codes.php'>Final Cleanup Script</a></p>";
echo "<p><a href='index.php?page=voter_registration'>Voter Registration</a></p>";
?>
