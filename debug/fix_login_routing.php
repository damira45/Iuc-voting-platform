<?php
/**
 * Fix Login Routing Issues
 * This script will help identify and fix the multiple login form problems
 */

require_once 'config/config.php';

echo "<h2>Fix Login Routing Issues</h2>";

echo "<h3>Current Login Forms Analysis:</h3>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='padding: 0.5rem;'>File</th>";
echo "<th style='padding: 0.5rem;'>Purpose</th>";
echo "<th style='padding: 0.5rem;'>Current Redirect</th>";
echo "<th style='padding: 0.5rem;'>Issue</th>";
echo "</tr>";

// Check each login file
$loginFiles = [
    'login.php' => 'Old email/password login',
    'student_login.php' => 'New voting code login', 
    'admin_login.php' => 'Admin login'
];

foreach ($loginFiles as $file => $purpose) {
    echo "<tr>";
    echo "<td style='padding: 0.5rem;'><code>$file</code></td>";
    echo "<td style='padding: 0.5rem;'>$purpose</td>";
    
    // Check current redirect in each file
    $filePath = "pages/$file";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, 'Location: index.php?page=dashboard') !== false) {
            echo "<td style='padding: 0.5rem; color: green;'>dashboard</td>";
        } elseif (strpos($content, 'Location: index.php?page=student_dashboard') !== false) {
            echo "<td style='padding: 0.5rem; color: orange;'>student_dashboard</td>";
        } else {
            echo "<td style='padding: 0.5rem;'>Unknown</td>";
        }
    } else {
        echo "<td style='padding: 0.5rem; color: red;'>File not found</td>";
    }
    
    echo "<td style='padding: 0.5rem;'>";
    if ($file === 'login.php') {
        echo "<span style='color: orange;'>⚠ Conflicts with student_login.php</span>";
    } elseif ($file === 'student_login.php') {
        echo "<span style='color: orange;'>⚠ Redirects to student_dashboard (doesn't exist)</span>";
    } else {
        echo "<span style='color: green;'>✓ Should be OK</span>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Recommended Fixes:</h3>";

echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>1. Standardize Login Forms</h4>";
echo "<p><strong>Problem:</strong> Multiple login forms causing confusion</p>";
echo "<p><strong>Solution:</strong> Use only student_login.php for students, login.php for general login, admin_login.php for admin</p>";

echo "<h4 style='color: #059669; margin: 1rem 0;'>2. Fix Redirects</h4>";
echo "<p><strong>Problem:</strong> student_login.php redirects to student_dashboard (doesn't exist)</p>";
echo "<p><strong>Solution:</strong> Change student_login.php redirect to dashboard</p>";

echo "<h4 style='color: #059669; margin: 1rem 0;'>3. Fix Registration</h4>";
echo "<p><strong>Problem:</strong> Registration page redirects to dashboard when logged in</p>";
echo "<p><strong>Solution:</strong> Allow access to registration form even when logged in for testing</p>";

echo "</div>";

echo "<h3>Quick Test Links:</h3>";
echo "<p><a href='index.php?page=login&force=1' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Force Show Login Form (Email/Password)</a></p>";
echo "<p><a href='index.php?page=student_login&force=1' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Force Show Student Login (Voting Code)</a></p>";
echo "<p><a href='index.php?page=admin_login' style='background: #6b7280; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Admin Login</a></p>";
echo "<p><a href='index.php?page=register&force=1' style='background: #8b5cf6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Force Show Registration Form</a></p>";

echo "<hr>";
echo "<p><strong>Current Session:</strong></p>";
echo "<pre>";
if (isset($_SESSION)) {
    print_r($_SESSION);
} else {
    echo "No active session";
}
echo "</pre>";
?>
