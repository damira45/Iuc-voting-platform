<?php
/**
 * IUC Voting System - Election Management
 * Election creation, management, and voting functions
 */

class Election {
    private $pdo;
    private $blockchain;
    
    public function __construct() {
        global $pdo, $blockchain;
        $this->pdo = $pdo;
        $this->blockchain = $blockchain;
    }
    
    /**
     * Create new election
     */
    public function createElection($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert election
            $stmt = $this->pdo->prepare("
                INSERT INTO elections (title, description, start_date, end_date, status, created_by, created_at) 
                VALUES (?, ?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['start_date'],
                $data['end_date'],
                $data['created_by']
            ]);
            
            $electionId = $this->pdo->lastInsertId();
            
            // Insert candidates
            if (isset($data['candidates']) && is_array($data['candidates'])) {
                foreach ($data['candidates'] as $index => $candidate) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO candidates (election_id, name, description, position, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $electionId,
                        $candidate['name'],
                        $candidate['description'] ?? '',
                        $index + 1   // 1-based sequential integer — satisfies unique_candidate_position
                    ]);
                }
            }
            
            // Create election on blockchain
            if (BLOCKCHAIN_ENABLED && $this->blockchain) {
                $candidateNames = array_column($data['candidates'], 'name');
                $blockchainResult = $this->blockchain->createElection(
                    "election_$electionId",
                    $data['title'],
                    $candidateNames
                );
                
                if ($blockchainResult['success']) {
                    // Save blockchain transaction
                    $stmt = $this->pdo->prepare("
                        INSERT INTO blockchain_transactions (election_id, transaction_hash, block_number, type, created_at) 
                        VALUES (?, ?, ?, 'election_created', NOW())
                    ");
                    $stmt->execute([
                        $electionId,
                        $blockchainResult['transactionHash'],
                        $blockchainResult['blockNumber']
                    ]);
                }
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'election_id' => $electionId
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Election creation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get total elections count
     */
    public function getTotalElections() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM elections");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get total votes count
     */
    public function getTotalVotes() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM votes");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Get recent elections
     */
    public function getRecentElections($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes,
                   u.name as created_by_name
            FROM elections e 
            LEFT JOIN users u ON e.created_by = u.id 
            ORDER BY e.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active elections (status = 'active' and within the voting window)
     */
    public function getActiveElections() {
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes
            FROM elections e 
            WHERE e.status = 'active'
              AND e.start_date <= CURDATE()
              AND e.end_date >= CURDATE()
            ORDER BY e.end_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all elections (for admin panel)
     */
    public function getAllElections() {
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes,
                   (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as total_candidates,
                   u.name as created_by_name
            FROM elections e 
            LEFT JOIN users u ON e.created_by = u.id
            ORDER BY e.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get election by ID
     */
    public function getElectionById($electionId) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, u.name as created_by_name 
            FROM elections e 
            LEFT JOIN users u ON e.created_by = u.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$electionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get election candidates
     */
    public function getElectionCandidates($electionId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count
            FROM candidates c 
            WHERE c.election_id = ? 
            ORDER BY c.position ASC
        ");
        $stmt->execute([$electionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cast vote
     */
    public function castVote($electionId, $candidateId, $userId) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if user has already voted
            if ($this->hasUserVoted($userId, $electionId)) {
                return [
                    'success' => false,
                    'message' => 'You have already voted in this election'
                ];
            }
            
            // Check if election is active
            $election = $this->getElectionById($electionId);
            if (!$election || $election['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Election is not active'
                ];
            }
            
            // Check if election period is valid
            $now = date('Y-m-d');
            if ($now < $election['start_date'] || $now > $election['end_date']) {
                return [
                    'success' => false,
                    'message' => 'Voting period is not active'
                ];
            }
            
            // Get user details
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cast vote on blockchain
            $transactionHash = null;
            if (BLOCKCHAIN_ENABLED && $this->blockchain) {
                $blockchainResult = $this->blockchain->castVote(
                    "election_$electionId",
                    $candidateId,
                    $user['email']
                );
                
                if ($blockchainResult['success']) {
                    $transactionHash = $blockchainResult['transactionHash'];
                } else {
                    throw new Exception('Blockchain transaction failed: ' . $blockchainResult['error']);
                }
            } else {
                // Generate mock transaction hash for non-blockchain mode
                $transactionHash = 'MOCK-' . bin2hex(random_bytes(16));
            }
            
            // Record vote in database
            $stmt = $this->pdo->prepare("
                INSERT INTO votes (election_id, candidate_id, user_id, transaction_hash, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$electionId, $candidateId, $userId, $transactionHash]);
            
            // Save blockchain transaction
            if ($transactionHash) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO blockchain_transactions (election_id, transaction_hash, type, created_at) 
                    VALUES (?, ?, 'vote_cast', NOW())
                ");
                $stmt->execute([$electionId, $transactionHash]);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'transaction_hash' => $transactionHash
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Vote casting failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user has voted in election
     */
    public function hasUserVoted($userId, $electionId) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM votes 
            WHERE user_id = ? AND election_id = ?
        ");
        $stmt->execute([$userId, $electionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Check if user can vote
     */
    public function canUserVote($userId) {
        $stmt = $this->pdo->prepare("
            SELECT status FROM users 
            WHERE id = ? AND type = 'student'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['status'] === 'approved';
    }
    
    /**
     * Get user votes
     */
    public function getUserVotes($userId, $limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT v.*, v.created_at as voted_at, e.title as election_title, c.name as candidate_name 
            FROM votes v 
            LEFT JOIN elections e ON v.election_id = e.id 
            LEFT JOIN candidates c ON v.candidate_id = c.id 
            WHERE v.user_id = ? 
            ORDER BY v.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get election results
     */
    public function getElectionResults($electionId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   COALESCE(v.vote_count, 0) as votes,
                   ROUND((COALESCE(v.vote_count, 0) * 100.0 / (SELECT COUNT(*) FROM votes WHERE election_id = ?)), 2) as percentage
            FROM candidates c 
            LEFT JOIN (
                SELECT candidate_id, COUNT(*) as vote_count 
                FROM votes 
                WHERE election_id = ? 
                GROUP BY candidate_id
            ) v ON c.id = v.candidate_id 
            WHERE c.election_id = ? 
            ORDER BY c.position ASC
        ");
        $stmt->execute([$electionId, $electionId, $electionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update election status
     */
    public function updateElectionStatus($electionId, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE elections 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $electionId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Status update failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete election
     */
    public function deleteElection($electionId) {
        try {
            $this->pdo->beginTransaction();
            
            // Delete votes
            $stmt = $this->pdo->prepare("DELETE FROM votes WHERE election_id = ?");
            $stmt->execute([$electionId]);
            
            // Delete candidates
            $stmt = $this->pdo->prepare("DELETE FROM candidates WHERE election_id = ?");
            $stmt->execute([$electionId]);
            
            // Delete blockchain transactions
            $stmt = $this->pdo->prepare("DELETE FROM blockchain_transactions WHERE election_id = ?");
            $stmt->execute([$electionId]);
            
            // Delete election
            $stmt = $this->pdo->prepare("DELETE FROM elections WHERE id = ?");
            $stmt->execute([$electionId]);
            
            $this->pdo->commit();
            
            return ['success' => true];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Election deletion failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get upcoming elections (start date is in the future)
     */
    public function getUpcomingElections() {
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes
            FROM elections e 
            WHERE e.start_date > CURDATE()
            ORDER BY e.start_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get voting statistics
     */
    public function getVotingStatistics($electionId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_votes,
                COUNT(DISTINCT user_id) as unique_voters,
                DATE(created_at) as vote_date,
                COUNT(*) as daily_votes
            FROM votes 
            WHERE election_id = ? 
            GROUP BY DATE(created_at)
            ORDER BY vote_date ASC
        ");
        $stmt->execute([$electionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
