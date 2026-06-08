<?php
/**
 * Create Election Backend
 * Handles election and candidate creation
 */

session_start();

header('Content-Type: application/json');

// Debug session state
error_log("Session state in create_election.php: " . print_r($_SESSION, true));

// Check if admin is logged in
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON data
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid data format']);
            exit;
        }
        
        // Validate required fields
        if (empty($data['title']) || empty($data['description']) || empty($data['candidates'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Server-side duplicate guard: reject if same title + start_date already exists
        $dupCheck = $pdo->prepare("SELECT id FROM elections WHERE title = ? AND start_date = ? LIMIT 1");
        $dupCheck->execute([$data['title'], $data['start_date'] ?? null]);
        if ($dupCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An election with this title and start date already exists.']);
            exit;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert election
        $stmt = $pdo->prepare("
            INSERT INTO elections (title, description, start_date, end_date, status, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $createdBy = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;
        $result = $stmt->execute([
            $data['title'],
            $data['description'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'draft',
            $createdBy
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create election');
        }
        
        $electionId = $pdo->lastInsertId();
        
        // Insert candidates — position is auto-assigned as 1, 2, 3… to satisfy the unique int constraint
        $candidateStmt = $pdo->prepare("
            INSERT INTO candidates (election_id, name, position, description, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");

        $positionIndex = 1;
        foreach ($data['candidates'] as $candidate) {
            if (empty($candidate['name'])) {
                continue; // Skip empty candidates
            }

            $result = $candidateStmt->execute([
                $electionId,
                $candidate['name'],
                $positionIndex,
                $candidate['description'] ?? ''
            ]);
            $positionIndex++;
            
            if (!$result) {
                throw new Exception('Failed to add candidate: ' . $candidate['name']);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Election created successfully',
            'election_id' => $electionId
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
