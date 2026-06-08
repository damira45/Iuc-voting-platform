<?php
/**
 * Final cleanup of problematic voting codes
 */

require_once 'config/config.php';

echo "<h2>Final Cleanup of Voting Codes</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Show all current voting codes
echo "<h3>Current Voting Codes (Before Cleanup):</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email as student_email 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC");
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Student</th>
            <th style='padding: 0.5rem;'>Voting Code</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Created</th>
            <th style='padding: 0.5rem;'>Action</th>
          </tr>";
    
    foreach ($codes as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$code['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace;'>";
        echo htmlspecialchars($code['voting_code']);
        if ($code['voting_code'] === '1') {
            echo " <span style='color: red; font-weight: bold;'>[PROBLEMATIC]</span>";
        }
        echo "</td>";
        echo "<td style='padding: 0.5rem;'>{$code['status']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['created_at']}</td>";
        echo "<td style='padding: 0.5rem;'>";
        if ($code['voting_code'] === '1') {
            echo "<span style='color: red;'>DELETE</span>";
        } else {
            echo "<span style='color: green;'>KEEP</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No voting codes found</p>";
}

// Delete all problematic codes
echo "<h3>Cleaning Up Problematic Codes:</h3>";

// Delete voting code '1' specifically
$stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code = ?");
$result = $stmt->execute(['1']);
$deleted_count = $stmt->rowCount();

if ($result && $deleted_count > 0) {
    echo "<p style='color: green;'>✓ Deleted $deleted_count problematic voting code(s) '1'</p>";
} else {
    echo "<p style='color: orange;'>No problematic voting code '1' found to delete</p>";
}

// Also delete any codes that are just numbers or invalid formats
$stmt = $pdo->prepare("DELETE FROM voting_codes WHERE voting_code REGEXP '^[0-9]+$'");
$result = $stmt->execute();
$numeric_deleted = $stmt->rowCount();

if ($result && $numeric_deleted > 0) {
    echo "<p style='color: green;'>✓ Deleted $numeric_deleted numeric-only voting codes</p>";
}

// Show voting codes after cleanup
echo "<h3>Voting Codes After Cleanup:</h3>";
$stmt = $pdo->query("SELECT vc.*, u.name as student_name, u.email as student_email 
                    FROM voting_codes vc 
                    JOIN users u ON vc.student_id = u.id 
                    ORDER BY vc.created_at DESC");
$codes_after = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codes_after) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>
            <th style='padding: 0.5rem;'>ID</th>
            <th style='padding: 0.5rem;'>Student</th>
            <th style='padding: 0.5rem;'>Voting Code</th>
            <th style='padding: 0.5rem;'>Status</th>
            <th style='padding: 0.5rem;'>Created</th>
          </tr>";
    
    foreach ($codes_after as $code) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$code['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($code['student_name']) . "</td>";
        echo "<td style='padding: 0.5rem; font-family: monospace;'>" . htmlspecialchars($code['voting_code']) . "</td>";
        echo "<td style='padding: 0.5rem;'>{$code['status']}</td>";
        echo "<td style='padding: 0.5rem;'>{$code['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✓ All voting codes cleaned up - no codes remaining</p>";
}

// Test code generation
echo "<h3>Test Code Generation After Cleanup:</h3>";
try {
    require_once 'includes/NotificationManager.php';
    $notificationManager = new NotificationManager($pdo);
    
    // Get clair (the most recent student)
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE name = ? AND type = 'student'");
    $stmt->execute(['clair']);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "<p>Testing with student: {$student['name']}</p>";
        
        $voting_code = $notificationManager->generateVotingCode($student['id'], 1, 1);
        
        if ($voting_code) {
            echo "<div style='background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
            echo "<h4 style='margin: 0 0 0.5rem 0;'>✓ SUCCESS! Voting Code Generated</h4>";
            echo "<div style='background: #1e293b; color: #10b981; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 1.2rem; text-align: center; margin: 0.5rem 0;'>";
            echo $voting_code;
            echo "</div>";
            echo "<p style='margin: 0.5rem 0 0 0; font-size: 0.9rem;'>Format: VOTE-XXXX-XXXX-XXXX-XXXX</p>";
            echo "</div>";
            
            // Store in session for voter registration page
            $_SESSION['generated_voting_code'] = [
                'code' => $voting_code,
                'student_id' => $student['id'],
                'generated_at' => date('Y-m-d H:i:s'),
                'student_details' => [
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'student_id' => 'IUC 2020 2020'
                ]
            ];
            
            echo "<p style='color: green;'>✓ Code stored in session for voter registration page</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to generate voting code</p>";
        }
    } else {
        echo "<p style='color: orange;'>Student 'clair' not found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong><a href='index.php?page=voter_registration&student_id=9'>Test Voter Registration with Clair (ID 9)</a></strong></p>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
echo "<p><a href='index.php?page=register'>Register New Student</a></p>";
?>
