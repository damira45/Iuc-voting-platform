<?php
/**
 * Check and fix duplicate candidate positions
 */

require_once 'config/config.php';

echo "<h2>Check Duplicate Candidate Positions</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Check for duplicate positions
echo "<h3>Current Candidates:</h3>";
$stmt = $pdo->query("
    SELECT election_id, position, COUNT(*) as count, GROUP_CONCAT(name) as names
    FROM candidates
    GROUP BY election_id, position
    HAVING COUNT(*) > 1
");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "<p style='color: red;'>Found duplicate positions:</p>";
    echo "<table border='1'><tr><th>Election ID</th><th>Position</th><th>Count</th><th>Candidates</th></tr>";
    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td>{$dup['election_id']}</td>";
        echo "<td>{$dup['position']}</td>";
        echo "<td>{$dup['count']}</td>";
        echo "<td>{$dup['names']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix duplicates by renumbering positions
    echo "<h3>Fixing duplicates...</h3>";
    
    // Get all candidates grouped by election
    $stmt = $pdo->query("SELECT id, election_id, name FROM candidates ORDER BY election_id, id");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $positionMap = [];
    foreach ($candidates as $candidate) {
        $electionId = $candidate['election_id'];
        if (!isset($positionMap[$electionId])) {
            $positionMap[$electionId] = 1;
        }
        
        $newPosition = $positionMap[$electionId]++;
        
        $stmt = $pdo->prepare("UPDATE candidates SET position = ? WHERE id = ?");
        $stmt->execute([$newPosition, $candidate['id']]);
        
        echo "<p>Updated candidate {$candidate['name']} (ID: {$candidate['id']}) in election {$electionId} to position {$newPosition}</p>";
    }
    
    echo "<p style='color: green;'>✓ All positions renumbered successfully</p>";
} else {
    echo "<p style='color: green;'>No duplicate positions found</p>";
}

// Show all candidates after fix
echo "<h3>All Candidates After Fix:</h3>";
$stmt = $pdo->query("SELECT * FROM candidates ORDER BY election_id, position");
$allCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>ID</th><th>Election ID</th><th>Name</th><th>Position</th></tr>";
foreach ($allCandidates as $c) {
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['election_id']}</td>";
    echo "<td>{$c['name']}</td>";
    echo "<td>{$c['position']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='index.php?page=admin'>Admin Dashboard</a></p>";
?>
