<?php
/**
 * Setup Candidates for Elections
 * Direct database setup for candidates
 */

require_once 'config/config.php';

echo "<h2>Setup Candidates for Elections</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Add candidates to Student Union Election (ID: 1)
echo "<h3>Adding Candidates to Student Union Election...</h3>";

$candidates = [
    ['name' => 'Sarah Johnson', 'position' => 'President', 'description' => 'Experienced leader with vision for student welfare and academic excellence. Proven track record in student government and community service.'],
    ['name' => 'Michael Chen', 'position' => 'President', 'description' => 'Innovation-focused candidate with strong technical background. Passionate about modernizing student services and digital transformation.'],
    ['name' => 'Emily Davis', 'position' => 'President', 'description' => 'Advocate for student rights and campus diversity. Committed to creating inclusive environment for all students.'],
    ['name' => 'James Rodriguez', 'position' => 'President', 'description' => 'Business-minded candidate with entrepreneurial experience. Focus on practical solutions and career development.'],
    ['name' => 'Amanda Patel', 'position' => 'President', 'description' => 'Environmental activist promoting sustainable campus initiatives. Strong advocate for green policies and climate action.']
];

// Clear existing candidates for election ID 1
$stmt = $pdo->prepare("DELETE FROM candidates WHERE election_id = 1");
$stmt->execute();

// Add new candidates
$addedCount = 0;
foreach ($candidates as $candidate) {
    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([1, $candidate['name'], $candidate['position'], $candidate['description']]);
    
    if ($result) {
        $addedCount++;
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($candidate['name']) . "</p>";
    }
}

echo "<h4 style='color: blue;'>Added $addedCount candidates to Student Union Election</h4>";

// Add candidates to Department Representative Election (ID: 2)
echo "<h3>Adding Candidates to Department Representative Election...</h3>";

$deptCandidates = [
    ['name' => 'Alex Thompson', 'position' => 'Computer Science Rep', 'description' => 'Tech enthusiast promoting digital literacy and coding workshops. Experienced in software development.'],
    ['name' => 'Maria Garcia', 'position' => 'Business Rep', 'description' => 'Future entrepreneur with leadership skills. Focus on startup culture and business innovation.'],
    ['name' => 'David Kim', 'position' => 'Engineering Rep', 'description' => 'Innovator with engineering excellence. Passionate about research and development projects.'],
    ['name' => 'Lisa Wang', 'position' => 'Medicine Rep', 'description' => 'Health advocate with medical knowledge. Committed to student wellness and healthcare access.'],
    ['name' => 'Robert Brown', 'position' => 'Law Rep', 'description' => 'Legal mind promoting justice and ethics. Experienced in moot court and legal clinics.']
];

// Clear existing candidates for election ID 2
$stmt = $pdo->prepare("DELETE FROM candidates WHERE election_id = 2");
$stmt->execute();

// Add new candidates
$addedCount2 = 0;
foreach ($deptCandidates as $candidate) {
    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([2, $candidate['name'], $candidate['position'], $candidate['description']]);
    
    if ($result) {
        $addedCount2++;
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($candidate['name']) . " - " . htmlspecialchars($candidate['position']) . "</p>";
    }
}

echo "<h4 style='color: blue;'>Added $addedCount2 candidates to Department Representative Election</h4>";

// Show all candidates
echo "<h3>All Candidates in Database:</h3>";
$stmt = $pdo->query("
    SELECT c.*, e.title as election_title 
    FROM candidates c 
    LEFT JOIN elections e ON c.election_id = e.id 
    ORDER BY e.title, c.name
");
$allCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($allCandidates) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Election</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Position</th>";
    echo "<th style='padding: 0.5rem;'>Description</th>";
    echo "<th style='padding: 0.5rem;'>Test Vote</th>";
    echo "</tr>";
    
    foreach ($allCandidates as $candidate) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$candidate['id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['election_title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($candidate['position']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars(substr($candidate['description'], 0, 50)) . "...</td>";
        echo "<td style='padding: 0.5rem;'>";
        echo "<a href='index.php?page=simple_voting&election_id={$candidate['election_id']}' style='background: #10b981; color: white; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 4px; font-size: 0.8rem;'>Vote</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No candidates found.</p>";
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='index.php?page=simple_voting' style='background: #3b82f6; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Test Simple Voting Interface</a></p>";
echo "<p><a href='index.php?page=dashboard' style='background: #6b7280; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a></p>";

echo "<div style='background: #f0fdf4; border: 1px solid #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
echo "<h4 style='color: #059669;'>✅ Candidates Setup Complete!</h4>";
echo "<p>Students can now vote in elections and see different types of candidates created by admin.</p>";
echo "<ul>";
echo "<li>Student Union Election has 5 presidential candidates</li>";
echo "<li>Department Representative Election has 5 department-specific candidates</li>";
echo "<li>Each candidate has detailed descriptions and positions</li>";
echo "</ul>";
echo "</div>";
?>
