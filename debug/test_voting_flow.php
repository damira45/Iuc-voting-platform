<?php
/**
 * Test Complete Voting Flow
 * Verify students can see candidates when clicking on elections
 */

require_once 'config/config.php';

echo "<h2>Test Complete Voting Flow</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

echo "<h3>📋 Current System Status:</h3>";

// Check elections
echo "<h4>🗳️ Available Elections:</h4>";
$stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($elections) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Title</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Candidates</th>";
    echo "<th style='padding: 0.5rem;'>Test Voting</th>";
    echo "</tr>";
    
    foreach ($elections as $election) {
        // Count candidates for this election
        $stmt = $pdo->prepare("SELECT COUNT(*) as candidate_count FROM candidates WHERE election_id = ?");
        $stmt->execute([$election['id']]);
        $candidateCount = $stmt->fetch(PDO::FETCH_ASSOC)['candidate_count'];
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$election['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($election['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($election['status']) . "</td>";
        echo "<td style='padding: 0.5rem; text-align: center;'>$candidateCount</td>";
        echo "<td style='padding: 0.5rem; text-align: center;'>";
        if ($candidateCount > 0) {
            echo "<a href='index.php?page=simple_voting&election_id={$election['id']}' style='background: #10b981; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;'>Vote Now</a>";
        } else {
            echo "<span style='color: #999;'>No candidates</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No elections found</p>";
}

// Check candidates
echo "<h4>👥 All Candidates:</h4>";
$stmt = $pdo->query("
    SELECT c.*, e.title as election_title 
    FROM candidates c 
    LEFT JOIN elections e ON c.election_id = e.id 
    ORDER BY e.title, c.name
");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($candidates) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Election</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Position</th>";
    echo "<th style='padding: 0.5rem;'>Description</th>";
    echo "</tr>";
    
    foreach ($candidates as $candidate) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$candidate['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['election_title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['position']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars(substr($candidate['description'], 0, 80)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No candidates found</p>";
}

echo "<h3>🧪 Test Voting Flow:</h3>";
echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669;'>✅ Complete Voting Flow:</h4>";
echo "<ol>";
echo "<li><strong>Student Login</strong> - Use voting code to login</li>";
echo "<li><strong>View Dashboard</strong> - See active elections</li>";
echo "<li><strong>Click Election</strong> - Select an election to vote</li>";
echo "<li><strong>View Candidates</strong> - See different candidate types</li>";
echo "<li><strong>Select Candidate</strong> - Choose preferred candidate</li>";
echo "<li><strong>Cast Vote</strong> - Submit vote securely</li>";
echo "<li><strong>Get Receipt</strong> - Receive blockchain verification code</li>";
echo "<li><strong>View Results</strong> - Check election outcomes</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🚀 Quick Test Links:</h3>";
echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap;'>";
echo "<a href='index.php?page=dashboard' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Student Dashboard</a>";
echo "<a href='index.php?page=simple_voting' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Simple Voting</a>";
echo "<a href='index.php?page=results' style='background: #6b7280; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>View Results</a>";
echo "<a href='index.php?page=profile' style='background: #8b5cf6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>My Profile</a>";
echo "</div>";

echo "<h3>📝 Admin Election Creation:</h3>";
echo "<div style='background: #fef3c7; border: 1px solid #fbbf24; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #92400e;'>📌 Note:</h4>";
echo "<p>Elections are created by the admin in the election page with a 'Create Election' button at the top right.</p>";
echo "<p>Admin can:</p>";
echo "<ul>";
echo "<li>Create new elections with titles and descriptions</li>";
echo "<li>Set election dates and status</li>";
echo "<li>Add candidates to elections</li>";
echo "<li>Manage voting codes for students</li>";
echo "</ul>";
echo "</div>";

echo "<h3>✅ System Verification:</h3>";
$systemChecks = [
    'Elections Available' => count($elections) > 0,
    'Candidates Added' => count($candidates) > 0,
    'Voting Interface Ready' => file_exists('pages/simple_voting.php'),
    'Results Page Ready' => file_exists('pages/student_results.php'),
    'Profile Page Ready' => file_exists('pages/profile.php'),
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='padding: 0.5rem;'>Component</th>";
echo "<th style='padding: 0.5rem;'>Status</th>";
echo "</tr>";

foreach ($systemChecks as $component => $status) {
    echo "<tr>";
    echo "<td style='padding: 0.5rem;'>$component</td>";
    echo "<td style='padding: 0.5rem; text-align: center;'>";
    if ($status) {
        echo "<span style='color: #10b981;'>✅ Ready</span>";
    } else {
        echo "<span style='color: #ef4444;'>❌ Missing</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🎯 Expected Behavior:</h3>";
echo "<div style='background: #e0e7ff; border: 1px solid #6366f1; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<p><strong>When a student clicks on an election:</strong></p>";
echo "<ol>";
echo "<li>Student sees election title and description</li>";
echo "<li>All candidates for that election are displayed</li>";
echo "<li>Each candidate shows: name, position, description</li>";
echo "<li>Student can click to select a candidate</li>";
echo "<li>Submit button appears after selection</li>";
echo "<li>Vote is cast with blockchain verification</li>";
echo "</ol>";
echo "</div>";
?>
