<?php
session_start();

echo "<h2>Session Debug</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookie Data:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h3>Test Setting Session:</h3>";
$_SESSION['test'] = 'test_value_' . time();
echo "<p>Set test session variable</p>";

echo "<p><a href='debug_session.php'>Refresh Page</a></p>";
echo "<p><a href='index.php?page=admin_login'>Go to Admin Login</a></p>";
?>
