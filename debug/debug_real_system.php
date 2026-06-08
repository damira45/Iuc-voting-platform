<?php
/**
 * Debug Real Voting System vs Test Code
 * Identify disconnect between test results and actual system behavior
 */

require_once 'config/config.php';

echo "<h2>🔍 Debug Real System vs Test Code</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>📊 Database State Check:</h3>";

// Check elections table
echo "<h4>🗳️ Elections in Database:</h4>";
$stmt = $pdo->query("SELECT id, title, status, start_date, end_date FROM elections ORDER BY id");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($elections) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Title</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Start</th>";
    echo "<th style='padding: 0.5rem;'>End</th>";
    echo "<th style='padding: 0.5rem;'>Active?</th>";
    echo "</tr>";
    
    foreach ($elections as $election) {
        // Check if election would be considered active
        $isActive = ($election['status'] === 'active' || $election['status'] === 'draft');
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$election['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($election['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($election['status']) . "</td>";
        echo "<td style='padding: 0.5rem;'>{$election['start_date']}</td>";
        echo "<td style='padding: 0.5rem;'>{$election['end_date']}</td>";
        echo "<td style='padding: 0.5rem; text-align: center;'>";
        echo $isActive ? "<span style='color: #10b981;'>✓</span>" : "<span style='color: #ef4444;'>✗</span>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No elections found in database</p>";
}

// Check candidates table
echo "<h4>👥 Candidates in Database:</h4>";
$stmt = $pdo->query("SELECT c.*, e.title as election_title FROM candidates c LEFT JOIN elections e ON c.election_id = e.id ORDER BY c.election_id, c.name");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($candidates) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Election ID</th>";
    echo "<th style='padding: 0.5rem;'>Election Title</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Position</th>";
    echo "</tr>";
    
    foreach ($candidates as $candidate) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$candidate['id']}</td>";
        echo "<td style='padding: 0.5rem;'>{$candidate['election_id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['election_title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['position']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No candidates found in database</p>";
}

echo "<h3>🧪 Test Real System Functions:</h3>";

// Test getActiveElections function
echo "<h4>Testing getActiveElections():</h4>";
try {
    require_once 'includes/election.php';
    $election = new Election();
    $activeElections = $election->getActiveElections();
    
    echo "<p><strong>Result:</strong> " . count($activeElections) . " active elections found</p>";
    
    if (count($activeElections) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 0.5rem;'>ID</th>";
        echo "<th style='padding: 0.5rem;'>Title</th>";
        echo "<th style='padding: 0.5rem;'>Status</th>";
        echo "<th style='padding: 0.5rem;'>total_votes</th>";
        echo "</tr>";
        
        foreach ($activeElections as $elec) {
            echo "<tr>";
            echo "<td style='padding: 0.5rem;'>{$elec['id']}</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['title']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($elec['status']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . ($elec['total_votes'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test getElectionCandidates function
echo "<h4>Testing getElectionCandidates():</h4>";
if (isset($election) && !empty($elections)) {
    foreach ($elections as $elec) {
        try {
            $candidates = $election->getElectionCandidates($elec['id']);
            echo "<p><strong>Election ID {$elec['id']} ({$elec['title']}):</strong> " . count($candidates) . " candidates</p>";
            
            if (count($candidates) > 0) {
                echo "<ul>";
                foreach ($candidates as $candidate) {
                    echo "<li>" . htmlspecialchars($candidate['name']) . " - " . htmlspecialchars($candidate['position']) . "</li>";
                }
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error for election {$elec['id']}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

echo "<h3>🔍 File System Check:</h3>";
$filesToCheck = [
    'pages/simple_voting.php' => 'Simple Voting Interface',
    'pages/student_dashboard.php' => 'Student Dashboard',
    'pages/student_results.php' => 'Student Results',
    'pages/profile.php' => 'Student Profile',
    'includes/election.php' => 'Election Class',
    'index.php' => 'Main Router'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='padding: 0.5rem;'>File</th>";
echo "<th style='padding: 0.5rem;'>Description</th>";
echo "<th style='padding: 0.5rem;'>Exists</th>";
echo "</tr>";

foreach ($filesToCheck as $file => $description) {
    $exists = file_exists($file);
    echo "<tr>";
    echo "<td style='padding: 0.5rem;'>$file</td>";
    echo "<td style='padding: 0.5rem;'>$description</td>";
    echo "<td style='padding: 0.5rem; text-align: center;'>";
    echo $exists ? "<span style='color: #10b981;'>✓</span>" : "<span style='color: #ef4444;'>✗</span>";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🚨 Potential Issues:</h3>";
echo "<div style='background: #fef3c7; border: 1px solid #fbbf24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";

$issues = [];

// Check for common issues
if (count($elections) == 0) {
    $issues[] = "No elections in database - admin needs to create elections";
}

if (count($candidates) == 0) {
    $issues[] = "No candidates in database - admin needs to add candidates";
}

if (!file_exists('pages/simple_voting.php')) {
    $issues[] = "Simple voting interface missing";
}

if (!isset($election)) {
    $issues[] = "Election class not loading properly";
}

if (empty($issues)) {
    echo "<p style='color: #10b981;'><strong>✅ No obvious issues found</strong></p>";
    echo "<p>The disconnect might be due to:</p>";
    echo "<ul>";
    echo "<li>Session issues - student not properly logged in</li>";
    echo "<li>Routing problems - wrong page being loaded</li>";
    echo "<li>Permission issues - user can't access voting</li>";
    echo "<li>Cache issues - old code being served</li>";
    echo "</ul>";
} else {
    echo "<p style='color: #ef4444;'><strong>❌ Issues Found:</strong></p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}
echo "</div>";

echo "<h3>🔧 Debug Steps:</h3>";
echo "<div style='background: #e0e7ff; border: 1px solid #6366f1; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>To identify the disconnect:</strong></p>";
echo "<ol>";
echo "<li>Clear browser cache (Ctrl+F5)</li>";
echo "<li>Test direct URLs:</li>";
echo "<ul>";
echo "<li><code>index.php?page=simple_voting</code> - Should show elections list</li>";
echo "<li><code>index.php?page=simple_voting&election_id=1</code> - Should show candidates</li>";
echo "</ul>";
echo "<li>Check session variables in real system</li>";
echo "<li>Verify routing is working correctly</li>";
echo "<li>Test with a fresh login</li>";
echo "</ol>";
echo "</div>";

echo "<h3>📝 Quick Test Links:</h3>";
echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap;'>";
echo "<a href='index.php?page=dashboard' style='background: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Dashboard</a>";
echo "<a href='index.php?page=simple_voting' style='background: #10b981; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Simple Voting</a>";
echo "<a href='index.php?page=simple_voting&election_id=1' style='background: #8b5cf6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Vote Election 1</a>";
echo "<a href='index.php?page=simple_voting&election_id=2' style='background: #8b5cf6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;'>Vote Election 2</a>";
echo "</div>";
?>
