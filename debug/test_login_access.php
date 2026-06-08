<?php
/**
 * Test Login Access - Simple test to verify login forms work
 */

require_once 'config/config.php';

echo "<h2>Test Login Access</h2>";

// Clear session to test fresh
session_destroy();

echo "<h3>Test Different Login Forms:</h3>";

echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Direct Login Form Tests:</h4>";

echo "<p><a href='index.php?page=login' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.5rem; display: inline-block;'>Test Login Form (Email/Password)</a></p>";

echo "<p><a href='index.php?page=student_login' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.5rem; display: inline-block;'>Test Student Login (Voting Code)</a></p>";

echo "<p><a href='index.php?page=admin_login' style='background: #6b7280; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.5rem; display: inline-block;'>Test Admin Login</a></p>";

echo "<p><a href='index.php?page=register' style='background: #8b5cf6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.5rem; display: inline-block;'>Test Registration Form</a></p>";

echo "</div>";

echo "<h3>Current Session Status:</h3>";
echo "<pre>";
if (isset($_SESSION)) {
    echo "Active session found:\n";
    print_r($_SESSION);
} else {
    echo "No active session\n";
}
echo "</pre>";

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Click each test link above to verify the login forms show correctly</li>";
echo "<li>If any form redirects to dashboard instead of showing the form, there's still a routing issue</li>";
echo "<li>The correct behavior should be: Click login → See login form (not dashboard)</li>";
echo "</ol>";
?>
