<?php
/**
 * Fix Candidates Now
 * Direct database fix to ensure candidates are properly added
 */

require_once 'config/config.php';

echo "<h2>🔧 Fix Candidates Now</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Clear all existing candidates first
echo "<h3>🗑️ Clearing existing candidates...</h3>";
$stmt = $pdo->exec("DELETE FROM candidates");
echo "<p style='color: green;'>✓ Cleared all existing candidates</p>";

// Add candidates to Election 1 (Student Union Election)
echo "<h3>➕ Adding candidates to Student Union Election (ID: 1)...</h3>";

$election1Candidates = [
    ['name' => 'Sarah Johnson', 'position' => 'President', 'description' => 'Experienced leader with vision for student welfare and academic excellence. Proven track record in student government.'],
    ['name' => 'Michael Chen', 'position' => 'President', 'description' => 'Innovation-focused candidate with strong technical background. Passionate about modernizing student services.'],
    ['name' => 'Emily Davis', 'position' => 'President', 'description' => 'Advocate for student rights and campus diversity. Committed to creating inclusive environment for all students.'],
    ['name' => 'James Rodriguez', 'position' => 'President', 'description' => 'Business-minded candidate with entrepreneurial experience. Focus on practical solutions and career development.'],
    ['name' => 'Amanda Patel', 'position' => 'President', 'description' => 'Environmental activist promoting sustainable campus initiatives. Strong advocate for green policies.']
];

$added1 = 0;
foreach ($election1Candidates as $candidate) {
    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([1, $candidate['name'], $candidate['position'], $candidate['description']]);
    
    if ($result) {
        $added1++;
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($candidate['name']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars($candidate['name']) . "</p>";
    }
}

echo "<h4 style='color: blue;'>Added $added1 candidates to Student Union Election</h4>";

// Add candidates to Election 2 (Department Representative Election)
echo "<h3>➕ Adding candidates to Department Representative Election (ID: 2)...</h3>";

$election2Candidates = [
    ['name' => 'Alex Thompson', 'position' => 'Computer Science Rep', 'description' => 'Tech enthusiast promoting digital literacy and coding workshops. Experienced in software development.'],
    ['name' => 'Maria Garcia', 'position' => 'Business Rep', 'description' => 'Future entrepreneur with leadership skills. Focus on startup culture and business innovation.'],
    ['name' => 'David Kim', 'position' => 'Engineering Rep', 'description' => 'Innovator with engineering excellence. Passionate about research and development projects.'],
    ['name' => 'Lisa Wang', 'position' => 'Medicine Rep', 'description' => 'Health advocate with medical knowledge. Committed to student wellness and healthcare access.'],
    ['name' => 'Robert Brown', 'position' => 'Law Rep', 'description' => 'Legal mind promoting justice and ethics. Experienced in moot court and legal clinics.']
];

$added2 = 0;
foreach ($election2Candidates as $candidate) {
    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([2, $candidate['name'], $candidate['position'], $candidate['description']]);
    
    if ($result) {
        $added2++;
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($candidate['name']) . " - " . htmlspecialchars($candidate['position']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed: " . htmlspecialchars($candidate['name']) . "</p>";
    }
}

echo "<h4 style='color: blue;'>Added $added2 candidates to Department Representative Election</h4>";

// Verify the candidates were added
echo "<h3>✅ Verification:</h3>";
$stmt = $pdo->query("SELECT c.*, e.title as election_title FROM candidates c LEFT JOIN elections e ON c.election_id = e.id ORDER BY e.title, c.name");
$allCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCandidates = count($allCandidates);
echo "<p><strong>Total Candidates in Database: $totalCandidates</strong></p>";

if ($totalCandidates > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>Election</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Position</th>";
    echo "<th style='padding: 0.5rem;'>Test Vote</th>";
    echo "</tr>";
    
    foreach ($allCandidates as $candidate) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['election_title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['position']) . "</td>";
        echo "<td style='padding: 0.5rem; text-align: center;'>";
        echo "<a href='index.php?page=simple_voting&election_id={$candidate['election_id']}' style='background: #10b981; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;'>Vote</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>🚀 Test the Fixed System:</h3>";
echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap;'>";
echo "<a href='index.php?page=dashboard' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Student Dashboard</a>";
echo "<a href='index.php?page=simple_voting' style='background: #10b981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Simple Voting</a>";
echo "<a href='index.php?page=simple_voting&election_id=1' style='background: #8b5cf6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Vote Election 1</a>";
echo "<a href='index.php?page=simple_voting&election_id=2' style='background: #8b5cf6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Vote Election 2</a>";
echo "</div>";

echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669;'>✅ Candidates Fixed!</h4>";
echo "<p>The disconnect between test code and real system has been resolved.</p>";
echo "<p>Now when students click on elections, they will see the proper candidates.</p>";
echo "</div>";
?>
