<?php
/**
 * Test Voting Interface
 * Check if the new simple voting interface is working
 */

require_once 'config/config.php';

echo "<h2>Test Voting Interface</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>Current Routing Test:</h3>";

// Test if simple_voting page exists
$simpleVotingPath = "pages/simple_voting.php";
if (file_exists($simpleVotingPath)) {
    echo "<p style='color: green;'>✓ simple_voting.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ simple_voting.php not found</p>";
}

// Test if voting_receipt page exists
$receiptPath = "pages/voting_receipt.php";
if (file_exists($receiptPath)) {
    echo "<p style='color: green;'>✓ voting_receipt.php exists</p>";
} else {
    echo "<p style='color: red;'>✗ voting_receipt.php not found</p>";
}

echo "<h3>Direct Test Links:</h3>";
echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Test the New Interface:</h4>";

echo "<p><a href='index.php?page=simple_voting' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem; display: inline-block;'>Test Simple Voting Interface</a></p>";
echo "<p><a href='index.php?page=voting_receipt' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem; display: inline-block;'>Test Voting Receipt Page</a></p>";
echo "<p><a href='index.php?page=dashboard' style='background: #6b7280; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem; display: inline-block;'>Test Student Dashboard</a></p>";

echo "</div>";

echo "<h3>Check Active Elections:</h3>";
require_once 'includes/election.php';
$election = new Election();
$activeElections = $election->getActiveElections();

echo "<p><strong>Active elections found:</strong> " . count($activeElections) . "</p>";

if (count($activeElections) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>Title</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Start Date</th>";
    echo "<th style='padding: 0.5rem;'>End Date</th>";
    echo "<th style='padding: 0.5rem;'>Test Link</th>";
    echo "</tr>";
    
    foreach ($activeElections as $elec) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['status']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $elec['start_date'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $elec['end_date'] . "</td>";
        echo "<td style='padding: 0.5rem;'>";
        echo "<a href='index.php?page=simple_voting&election_id=" . $elec['id'] . "' style='background: #10b981; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;'>Test Vote</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No active elections found</p>";
    
    // Check all elections regardless of status
    echo "<h4>All Elections in Database:</h4>";
    $stmt = $pdo->query("SELECT id, title, status, start_date, end_date FROM elections ORDER BY created_at DESC");
    $allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allElections) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 0.5rem;'>Title</th>";
        echo "<th style='padding: 0.5rem;'>Status</th>";
        echo "<th style='padding: 0.5rem;'>Start Date</th>";
        echo "<th style='padding: 0.5rem;'>End Date</th>";
        echo "<th style='padding: 0.5rem;'>Test Link</th>";
        echo "</tr>";
        
        foreach ($allElections as $elec) {
            echo "<tr>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['title']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['status']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . $elec['start_date'] . "</td>";
            echo "<td style='padding: 0.5rem;'>" . $elec['end_date'] . "</td>";
            echo "<td style='padding: 0.5rem;'>";
            echo "<a href='index.php?page=simple_voting&election_id=" . $elec['id'] . "' style='background: #3b82f6; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;'>Test Vote</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No elections found in database</p>";
    }
}

echo "<h3>Session Status:</h3>";
echo "<pre>";
if (isset($_SESSION)) {
    echo "Active session found:\n";
    print_r($_SESSION);
} else {
    echo "No active session\n";
}
echo "</pre>";

echo "<h3>Troubleshooting Steps:</h3>";
echo "<div style='background: #fef3c7; border: 1px solid #fbbf24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #92400e; margin: 0 0 1rem 0;'>If changes aren't showing:</h4>";
echo "<ol>";
echo "<li><strong>Clear browser cache</strong> - Press Ctrl+F5 or Ctrl+Shift+R</li>";
echo "<li><strong>Check file permissions</strong> - Ensure files are readable by web server</li>";
echo "<li><strong>Test direct URLs</strong> - Use the test links above</li>";
echo "<li><strong>Check Apache/Nginx restart</strong> - May need to restart web server</li>";
echo "<li><strong>Verify file paths</strong> - Make sure files are in correct directories</li>";
echo "</ol>";
echo "</div>";
?>
