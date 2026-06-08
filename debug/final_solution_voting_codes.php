<?php
/**
 * Final solution - completely clear database and provide working voting codes
 */

require_once 'config/config.php';

echo "<h2>Final Solution - Voting Code Generation</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Step 1: Check what's in the database
echo "<h3>Step 1: Current Database State</h3>";
$stmt = $pdo->query("SELECT * FROM voting_codes ORDER BY created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<p style='color: red;'>Found " . count($codes) . " voting codes in database:</p>";
    echo "<table border='1'><tr><th>ID</th><th>Student ID</th><th>Voting Code</th><th>Status</th></tr>";
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td>{$code['id']}</td>";
        echo "<td>{$code['student_id']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>No voting codes in database</p>";
}

// Step 2: Clear ALL voting codes
echo "<h3>Step 2: Clearing All Voting Codes</h3>";
$stmt = $pdo->exec("DELETE FROM voting_codes");
echo "<p style='color: green;'>✓ All voting codes deleted</p>";

// Step 3: Generate a working voting code for clair
echo "<h3>Step 3: Generate Working Voting Code for Clair</h3>";

try {
    // Get clair's info
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND type = 'student'");
    $stmt->execute([9]);
    $clair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clair) {
        echo "<p>Found student: {$clair['name']}</p>";
        
        // Generate a proper voting code
        do {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $voting_code = 'VOTE-';
            for ($i = 0; $i < 16; $i++) {
                if ($i === 4 || $i === 8 || $i === 12) $voting_code .= '-';
                $voting_code .= $chars[rand(0, strlen($chars) - 1)];
            }
            
            // Ensure it's not '1'
            if ($voting_code === '1' || strlen($voting_code) < 19) {
                continue;
            }
            
            // Check uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voting_codes WHERE voting_code = ?");
            $stmt->execute([$voting_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } while ($result['count'] > 0);
        
        echo "<p>Generated voting code: <code>$voting_code</code></p>";
        
        // Insert directly into database
        $sql = "INSERT INTO voting_codes (student_id, election_id, voting_code, generated_by_admin, expires_at, status) 
                VALUES (?, 1, ?, 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent')";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$clair['id'], $voting_code]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Voting code saved to database</p>";
            
            // Store in session for display
            $_SESSION['generated_voting_code'] = [
                'code' => $voting_code,
                'student_id' => $clair['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'student_details' => [
                    'name' => $clair['name'],
                    'email' => $clair['email'],
                    'student_id' => 'IUC 2020 2020'
                ]
            ];
            
            echo "<div style='background: #d1fae5; color: #065f46; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h3 style='margin: 0 0 1rem 0; color: #059669;'>✓ SUCCESS! Voting Code Ready</h3>";
            echo "<div style='background: #1e293b; color: #10b981; padding: 1.5rem; border-radius: 8px; font-family: monospace; font-size: 1.4rem; text-align: center; margin: 1rem 0; letter-spacing: 2px; font-weight: bold;'>";
            echo $voting_code;
            echo "</div>";
            echo "<p style='margin: 0.5rem 0; font-size: 0.9rem;'><strong>Student:</strong> {$clair['name']}</p>";
            echo "<p style='margin: 0.5rem 0; font-size: 0.9rem;'><strong>Email:</strong> {$clair['email']}</p>";
            echo "<p style='margin: 0.5rem 0; font-size: 0.9rem;'><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
            echo "</div>";
            
            echo "<h3>Step 4: Test Student Login</h3>";
            echo "<p>Student can now login with this voting code at:</p>";
            echo "<p><strong>index.php?page=student_login</strong></p>";
            echo "<p>Use Student ID: <strong>IUC 2020 2020</strong></p>";
            echo "<p>Use Voting Code: <strong>$voting_code</strong></p>";
            
        } else {
            echo "<p style='color: red;'>✗ Failed to save voting code</p>";
        }
    } else {
        echo "<p style='color: red;'>Student clair not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Step 5: Show final state
echo "<h3>Step 5: Final Database State</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name FROM voting_codes vc JOIN users u ON vc.student_id = u.id ORDER BY vc.created_at DESC");
$final_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($final_codes) > 0) {
    echo "<table border='1'><tr><th>Student</th><th>Voting Code</th><th>Status</th><th>Created</th></tr>";
    foreach ($final_codes as $code) {
        echo "<tr>";
        echo "<td>{$code['student_name']}</td>";
        echo "<td><code>{$code['voting_code']}</code></td>";
        echo "<td>{$code['status']}</td>";
        echo "<td>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes in final state</p>";
}

echo "<hr>";
echo "<p><strong><a href='index.php?page=voter_registration&student_id=9'>Test Voter Registration Page</a></strong></p>";
echo "<p><strong><a href='index.php?page=student_login'>Test Student Login with Generated Code</a></strong></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
