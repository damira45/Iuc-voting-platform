<?php
/**
 * Add Sample Candidates to Elections
 * Create different types of candidates for existing elections
 */

require_once 'config/config.php';

echo "<h2>Add Sample Candidates to Elections</h2>";

if (!$pdo) {
    echo "<p style='color: red;'>Database not available</p>";
    exit;
}

// Get existing elections
echo "<h3>Existing Elections:</h3>";
$stmt = $pdo->query("SELECT id, title FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($elections) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Action</th></tr>";
    foreach ($elections as $election) {
        echo "<tr>";
        echo "<td>{$election['id']}</td>";
        echo "<td>" . htmlspecialchars($election['title']) . "</td>";
        echo "<td><a href='?add_candidates={$election['id']}'>Add Candidates</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Handle adding candidates
    if (isset($_GET['add_candidates'])) {
        $electionId = $_GET['add_candidates'];
        
        // Get election details
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$electionId]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($election) {
            echo "<h3>Adding Candidates to: " . htmlspecialchars($election['title']) . "</h3>";
            
            // Sample candidates for different election types
            $candidateSets = [
                1 => [ // Student Union Election
                    ['name' => 'Sarah Johnson', 'position' => 'President', 'description' => 'Experienced leader with vision for student welfare and academic excellence'],
                    ['name' => 'Michael Chen', 'position' => 'President', 'description' => 'Innovation-focused candidate with strong technical background'],
                    ['name' => 'Emily Davis', 'position' => 'President', 'description' => 'Advocate for student rights and campus diversity'],
                    ['name' => 'James Rodriguez', 'position' => 'President', 'description' => 'Business-minded candidate with entrepreneurial experience'],
                    ['name' => 'Amanda Patel', 'position' => 'President', 'description' => 'Environmental activist promoting sustainable campus initiatives']
                ],
                2 => [ // Department Representative Election
                    ['name' => 'Alex Thompson', 'position' => 'Computer Science Rep', 'description' => 'Tech enthusiast promoting digital literacy'],
                    ['name' => 'Maria Garcia', 'position' => 'Business Rep', 'description' => 'Future entrepreneur with leadership skills'],
                    ['name' => 'David Kim', 'position' => 'Engineering Rep', 'description' => 'Innovator with engineering excellence'],
                    ['name' => 'Lisa Wang', 'position' => 'Medicine Rep', 'description' => 'Health advocate with medical knowledge'],
                    ['name' => 'Robert Brown', 'position' => 'Law Rep', 'description' => 'Legal mind promoting justice and ethics']
                ]
            ];
            
            // Use default candidates if election not in predefined sets
            $candidates = $candidateSets[$electionId] ?? [
                ['name' => 'Candidate A', 'position' => 'Position', 'description' => 'Experienced and qualified candidate'],
                ['name' => 'Candidate B', 'position' => 'Position', 'description' => 'Innovative and dedicated candidate'],
                ['name' => 'Candidate C', 'position' => 'Position', 'description' => 'Visionary and committed candidate']
            ];
            
            // Clear existing candidates for this election
            $stmt = $pdo->prepare("DELETE FROM candidates WHERE election_id = ?");
            $stmt->execute([$electionId]);
            
            // Add new candidates
            $addedCount = 0;
            foreach ($candidates as $candidate) {
                $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, position, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$electionId, $candidate['name'], $candidate['position'], $candidate['description']]);
                
                if ($result) {
                    $addedCount++;
                    echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($candidate['name']) . " - " . htmlspecialchars($candidate['position']) . "</p>";
                }
            }
            
            echo "<h4 style='color: blue;'>Added $addedCount candidates to election ID $electionId</h4>";
            echo "<p><a href='index.php?page=simple_voting&election_id=$electionId'>Test Voting</a></p>";
        }
    }
} else {
    echo "<p style='color: orange;'>No elections found. Please create elections first.</p>";
}

// Show current candidates
echo "<h3>Current Candidates:</h3>";
$stmt = $pdo->query("
    SELECT c.*, e.title as election_title 
    FROM candidates c 
    LEFT JOIN elections e ON c.election_id = e.id 
    ORDER BY e.title, c.position
");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($candidates) > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Election</th><th>Name</th><th>Position</th><th>Description</th></tr>";
    foreach ($candidates as $candidate) {
        echo "<tr>";
        echo "<td>{$candidate['id']}</td>";
        echo "<td>" . htmlspecialchars($candidate['election_title']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['name']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['position']) . "</td>";
        echo "<td>" . htmlspecialchars($candidate['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No candidates found.</p>";
}

echo "<h3>Test Voting Interface:</h3>";
echo "<p><a href='index.php?page=simple_voting'>Test Simple Voting</a></p>";
echo "<p><a href='index.php?page=dashboard'>Go to Dashboard</a></p>";
?>
