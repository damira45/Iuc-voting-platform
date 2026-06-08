<?php
/**
 * Create Election Backend
 */
session_start();
header('Content-Type: application/json');

require_once 'config/config.php';

// Auth check
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        exit;
    }

    if (empty($data['title']) || empty($data['description']) || empty($data['candidates'])) {
        echo json_encode(['success' => false, 'message' => 'Title, description and at least one candidate are required']);
        exit;
    }

    // Duplicate guard
    $dup = $pdo->prepare("SELECT id FROM elections WHERE title = ? AND start_date = ? LIMIT 1");
    $dup->execute([$data['title'], $data['start_date'] ?? null]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An election with this title and start date already exists.']);
        exit;
    }

    $pdo->beginTransaction();

    // Insert election
    $stmt = $pdo->prepare("
        INSERT INTO elections (title, description, start_date, end_date, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['start_date'] ?? null,
        $data['end_date'] ?? null,
        $data['status'] ?? 'active',
        $_SESSION['user_id']
    ]);

    $electionId = $pdo->lastInsertId();

    // Insert candidates with auto-assigned position
    $cStmt = $pdo->prepare("
        INSERT INTO candidates (election_id, name, position, description, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $pos = 1;
    foreach ($data['candidates'] as $c) {
        if (empty(trim($c['name'] ?? ''))) continue;
        $cStmt->execute([$electionId, $c['name'], $pos, $c['description'] ?? '']);
        $pos++;
    }

    if ($pos === 1) {
        // No valid candidates were added
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'At least one candidate with a name is required.']);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'message'     => 'Election created successfully',
        'election_id' => $electionId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
