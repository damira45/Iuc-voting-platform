<?php
/**
 * Debug Admin Session
 * Check admin session state for debugging
 */

session_start();

echo "<h2>Admin Session Debug</h2>";

echo "<h3>Current Session State:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Session Variables Check:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='padding: 0.5rem;'>Variable</th>";
echo "<th style='padding: 0.5rem;'>Value</th>";
echo "<th style='padding: 0.5rem;'>Status</th>";
echo "</tr>";

$sessionVars = [
    'admin_id' => 'Admin ID',
    'user_id' => 'User ID', 
    'user_type' => 'User Type',
    'admin_name' => 'Admin Name',
    'admin_email' => 'Admin Email',
    'login_time' => 'Login Time'
];

foreach ($sessionVars as $key => $description) {
    $value = $_SESSION[$key] ?? 'Not Set';
    $status = isset($_SESSION[$key]) ? '<span style="color: #10b981;">✓ Set</span>' : '<span style="color: #ef4444;">✗ Missing</span>';
    
    echo "<tr>";
    echo "<td style='padding: 0.5rem;'><strong>$key</strong></td>";
    echo "<td style='padding: 0.5rem;'>" . (is_array($value) ? print_r($value, true) : htmlspecialchars($value)) . "</td>";
    echo "<td style='padding: 0.5rem; text-align: center;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Admin Access Check:</h3>";
$isAdminLoggedIn = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
$userType = $_SESSION['user_type'] ?? '';
$isAdmin = $userType === 'admin';

echo "<p><strong>Admin Logged In:</strong> " . ($isAdminLoggedIn ? '<span style="color: #10b981;">✓ Yes</span>' : '<span style="color: #ef4444;">✗ No</span>') . "</p>";
echo "<p><strong>User Type:</strong> " . htmlspecialchars($userType) . "</p>";
echo "<p><strong>Is Admin:</strong> " . ($isAdmin ? '<span style="color: #10b981;">✓ Yes</span>' : '<span style="color: #ef4444;">✗ No</span>') . "</p>";

echo "<h3>Test Create Election Access:</h3>";
if ($isAdminLoggedIn && $isAdmin) {
    echo "<p style='color: #10b981;'>✅ Should have access to create election</p>";
    echo "<button onclick='testCreateElection()' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Test Create Election API</button>";
} else {
    echo "<p style='color: #ef4444;'>❌ Will get 'Admin access required' error</p>";
    echo "<p><strong>Solution:</strong> Make sure you are logged in as admin with proper session variables</p>";
}

echo "<h3>Quick Actions:</h3>";
echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap;'>";
if (!$isAdminLoggedIn) {
    echo "<a href='index.php?page=admin_login' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Admin Login</a>";
}
echo "<a href='index.php?page=elections' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Elections Page</a>";
echo "<a href='index.php?page=logout' style='background: #ef4444; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Logout</a>";
echo "</div>";
?>

<script>
function testCreateElection() {
    const testData = {
        title: 'Test Election',
        description: 'Test election for debugging',
        start_date: '2024-01-01',
        end_date: '2024-12-31',
        status: 'draft',
        candidates: [
            {
                name: 'Test Candidate',
                position: 'Test Position',
                description: 'Test candidate description'
            }
        ]
    };
    
    fetch('create_election.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(result => {
        alert('Result: ' + JSON.stringify(result, null, 2));
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
