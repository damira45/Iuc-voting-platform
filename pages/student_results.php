<?php
/**
 * IUC Voting System - Student Results Page
 * Simple results view for students
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'config/config.php';
require_once 'includes/election.php';

$election = new Election();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Get user's voting history
$userVotes = $election->getUserVotes($userId);

// Get all elections with results
$stmt = $pdo->query("SELECT e.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as total_votes
                   FROM elections e 
                   WHERE e.status = 'completed' OR e.status = 'active'
                   ORDER BY e.end_date DESC");
$allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - IUC Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            min-height: 100vh;
            color: #333;
        }

        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #666;
        }

        .back-btn {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.4);
        }

        .logout-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .results-grid {
            display: grid;
            gap: 2rem;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .result-header {
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            color: white;
            padding: 1.5rem;
        }

        .result-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .result-meta {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .result-content {
            padding: 2rem;
        }

        .voting-status {
            background: #f0fdf4;
            border: 1px solid #10b981;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .voting-status.voted {
            background: #f0fdf4;
            border-color: #10b981;
            color: #059669;
        }

        .voting-status.not-voted {
            background: #fef3c7;
            border-color: #fbbf24;
            color: #92400e;
        }

        .candidates-list {
            display: grid;
            gap: 1rem;
        }

        .candidate-result {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .candidate-result:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .candidate-result.winner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 2px solid #10b981;
        }

        .candidate-info {
            flex: 1;
        }

        .candidate-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .candidate-position {
            color: #666;
            font-size: 0.9rem;
        }

        .vote-info {
            text-align: right;
            min-width: 150px;
        }

        .vote-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.25rem;
        }

        .vote-percentage {
            color: #666;
            font-size: 0.9rem;
        }

        .winner-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }

        .your-vote {
            background: linear-gradient(135deg, rgba(11, 60, 93, 0.1) 0%, rgba(50, 130, 184, 0.1) 100%);
            border: 2px solid #0B3C5D;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }

        .your-vote p {
            color: #0B3C5D;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .your-vote strong {
            color: #333;
            font-size: 1.1rem;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #999;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="results-container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>Election Results</h1>
                <p>View election results and your voting history</p>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?></span>
                <a href="index.php?page=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="index.php?page=dashboard" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Results Grid -->
        <div class="results-grid">
            <?php if (empty($allElections)): ?>
                <div class="result-card">
                    <div class="result-content">
                        <div class="no-results">
                            <i class="fas fa-chart-bar"></i>
                            <h3>No Election Results Available</h3>
                            <p>Results will be available once elections are completed.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($allElections as $election): ?>
                    <?php
                    // Get candidates for this election
                    $stmt = $pdo->prepare("SELECT c.*, 
                                       (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count
                                       FROM candidates c 
                                       WHERE c.election_id = ?
                                       ORDER BY vote_count DESC");
                    $stmt->execute([$election['id']]);
                    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Check if user voted in this election
                    $userVoted = false;
                    $userVote = null;
                    foreach ($userVotes as $vote) {
                        if ($vote['election_id'] == $election['id']) {
                            $userVoted = true;
                            $userVote = $vote;
                            break;
                        }
                    }
                    
                    // Calculate total votes
                    $totalVotes = array_sum(array_column($candidates, 'vote_count'));
                    
                    // Find winner
                    $winner = $candidates[0] ?? null;
                    ?>
                    
                    <div class="result-card">
                        <div class="result-header">
                            <h2 class="result-title"><?php echo htmlspecialchars($election['title']); ?></h2>
                            <div class="result-meta">
                                <?php echo date('M d, Y', strtotime($election['end_date'])); ?> • 
                                <?php echo $totalVotes; ?> total votes
                            </div>
                        </div>
                        
                        <div class="result-content">
                            <!-- Voting Status -->
                            <div class="voting-status <?php echo $userVoted ? 'voted' : 'not-voted'; ?>">
                                <?php if ($userVoted): ?>
                                    <i class="fas fa-check-circle"></i>
                                    <strong>You voted in this election</strong>
                                <?php else: ?>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>You did not vote in this election</strong>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Statistics -->
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo count($candidates); ?></div>
                                    <div class="stat-label">Candidates</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $totalVotes; ?></div>
                                    <div class="stat-label">Total Votes</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php 
                                        $maxVotes = max(array_column($candidates, 'vote_count'));
                                        echo $maxVotes;
                                        ?>
                                    </div>
                                    <div class="stat-label">Highest Votes</div>
                                </div>
                            </div>
                            
                            <!-- Candidates Results -->
                            <div class="candidates-list">
                                <?php if (empty($candidates)): ?>
                                    <p style="text-align: center; color: #666; padding: 2rem;">No candidates found for this election</p>
                                <?php else: ?>
                                    <?php foreach ($candidates as $index => $candidate): ?>
                                        <div class="candidate-result <?php echo $winner && $winner['id'] === $candidate['id'] ? 'winner' : ''; ?>">
                                            <div class="candidate-info">
                                                <div class="candidate-name">
                                                    <?php echo htmlspecialchars($candidate['name']); ?>
                                                    <?php if ($winner && $winner['id'] === $candidate['id']): ?>
                                                        <span class="winner-badge">
                                                            <i class="fas fa-trophy"></i> Winner
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                            </div>
                                            <div class="vote-info">
                                                <div class="vote-count"><?php echo $candidate['vote_count']; ?></div>
                                                <div class="vote-percentage">
                                                    <?php echo $totalVotes > 0 ? round(($candidate['vote_count'] / $totalVotes) * 100, 1) : 0; ?>%
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Show user's vote -->
                                        <?php if ($userVote && $userVote['candidate_id'] === $candidate['id']): ?>
                                            <div class="your-vote">
                                                <p><i class="fas fa-vote-yea"></i> Your Vote</p>
                                                <strong><?php echo htmlspecialchars($candidate['name']); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
