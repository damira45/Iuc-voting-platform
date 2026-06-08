<?php
/**
 * Debug Elections Issue
 * Check why students can't see elections that admin can see
 */

require_once 'config/config.php';

echo "<h2>Debug Elections Issue</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>All Elections in Database:</h3>";
$stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
$allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($allElections) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Title</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Start Date</th>";
    echo "<th style='padding: 0.5rem;'>End Date</th>";
    echo "<th style='padding: 0.5rem;'>Current Date</th>";
    echo "<th style='padding: 0.5rem;'>Would Be Active</th>";
    echo "</tr>";
    
    foreach ($allElections as $elec) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$elec['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['status']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $elec['start_date'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . $elec['end_date'] . "</td>";
        echo "<td style='padding: 0.5rem;'>" . date('Y-m-d') . "</td>";
        
        // Check if it would be active with current logic
        $wouldBeActive = ($elec['status'] === 'active' && 
                         $elec['start_date'] <= date('Y-m-d') && 
                         $elec['end_date'] >= date('Y-m-d'));
        
        echo "<td style='padding: 0.5rem;'>";
        if ($wouldBeActive) {
            echo "<span style='color: green;'>✓ Yes</span>";
        } else {
            echo "<span style='color: red;'>✗ No</span>";
            echo "<br><small>";
            if ($elec['status'] !== 'active') echo "Status: {$elec['status']} ";
            if ($elec['start_date'] > date('Y-m-d')) echo "Start date in future ";
            if ($elec['end_date'] < date('Y-m-d')) echo "End date passed";
            echo "</small>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No elections found in database</p>";
}

echo "<h3>Test Active Elections Query:</h3>";
$stmt = $pdo->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes
    FROM elections e 
    WHERE e.status = 'active' 
    AND e.start_date <= CURDATE() 
    AND e.end_date >= CURDATE()
    ORDER BY e.end_date ASC
");
$activeElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Active elections found:</strong> " . count($activeElections) . "</p>";

if (count($activeElections) > 0) {
    foreach ($activeElections as $elec) {
        echo "<p style='color: green;'>✓ " . htmlspecialchars($elec['title']) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>No active elections found with current query</p>";
}

echo "<h3>Quick Fix Options:</h3>";
echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669; margin: 0 0 1rem 0;'>Solutions:</h4>";
echo "<p><strong>Option 1:</strong> Update election status to 'active' in database</p>";
echo "<p><strong>Option 2:</strong> Fix date logic in getActiveElections()</p>";
echo "<p><strong>Option 3:</strong> Create test elections with proper status and dates</p>";
echo "</div>";

echo "<h3>Actions:</h3>";
echo "<p><a href='index.php?page=dashboard' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Back to Dashboard</a></p>";
echo "<p><a href='index.php?page=admin' style='background: #6b7280; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin: 0.25rem;'>Admin Panel</a></p>";
?>
