<?php
/**
 * IUC Voting System - Voting Page
 * Student voting interface
 */

require_once 'includes/election.php';
require_once 'includes/student.php';

$election = new Election();
$student = new Student();

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];

// Check if user can vote
if (!$student->canStudentVote($userId)) {
    header("Location: index.php?page=dashboard&error=not_approved");
    exit;
}

// Get active elections
$activeElections = $election->getActiveElections();

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
    $electionId = $_POST['election_id'];
    $candidateId = $_POST['candidate_id'];
    
    $result = $election->castVote($electionId, $candidateId, $userId);
    
    if ($result['success']) {
        logActivity($userId, 'vote', "Voted in election $electionId for candidate $candidateId");
        header("Location: index.php?page=voting&success=voted&election_id=$electionId");
        exit;
    } else {
        $error = $result['message'];
    }
}

// Get specific election details
$selectedElection = null;
$candidates = [];
$hasVoted = false;

if (isset($_GET['election_id'])) {
    $electionId = $_GET['election_id'];
    $selectedElection = $election->getElectionById($electionId);
    
    if ($selectedElection) {
        $candidates = $election->getElectionCandidates($electionId);
        $hasVoted = $election->hasUserVoted($userId, $electionId);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - IUC Voting System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .voting-container {
            max-width: 1400px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
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
        
        .election-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .election-card:hover {
            transform: translateY(-5px);
        }
        
        .election-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        
        .election-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .election-description {
            opacity: 0.9;
            line-height: 1.6;
            font-size: 1.1rem;
        }

        .election-meta {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .election-meta span {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .election-content {
            padding: 2rem;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .candidate-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .candidate-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .candidate-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 auto 1rem;
        }

        .candidate-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
            color: #333;
        }

        .candidate-position {
            text-align: center;
            color: #666;
            margin-bottom: 1rem;
        }

        .candidate-bio {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .vote-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .vote-btn:disabled {
            background: #6b7280;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .alert-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .voting-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input[type="radio"] {
            display: none;
        }

        .form-group input[type="radio"]:checked + .candidate-card {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .text-muted {
            color: #999;
            text-align: center;
            padding: 2rem;
            font-size: 1.1rem;
        }
        
        .election-meta {
            display: flex;
            gap: var(--spacing-6);
            margin-top: var(--spacing-4);
            font-size: var(--font-size-sm);
            opacity: 0.8;
        }
        
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-4);
            padding: var(--spacing-6);
        }
        
        .candidate-card {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: var(--spacing-6);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .candidate-card:hover {
            border-color: var(--secondary-blue);
            box-shadow: var(--shadow-md);
        }
        
        .candidate-card.selected {
            border-color: var(--primary-blue);
            background: var(--light-blue);
        }
        
        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-4);
            font-size: var(--font-size-2xl);
            font-weight: 700;
        }
        
        .candidate-name {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
        }
        
        .candidate-description {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
            line-height: 1.5;
            margin-bottom: var(--spacing-4);
        }
        
        .vote-button {
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: var(--spacing-3) var(--spacing-6);
            border-radius: var(--radius-md);
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .vote-button:hover {
            background: var(--secondary-blue);
        }
        
        .vote-button:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
        
        .voting-actions {
            padding: var(--spacing-6);
            text-align: center;
            border-top: 1px solid var(--gray-200);
        }
        
        .confirm-vote-btn {
            background: var(--success);
            color: var(--white);
            border: none;
            padding: var(--spacing-4) var(--spacing-8);
            border-radius: var(--radius-md);
            font-size: var(--font-size-lg);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .confirm-vote-btn:hover {
            background: #059669;
        }
        
        .confirm-vote-btn:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
        
        .voted-badge {
            background: var(--success);
            color: var(--white);
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-md);
            font-weight: 500;
            display: inline-block;
        }
        
        .elections-list {
            display: grid;
            gap: var(--spacing-6);
        }
        
        .no-elections {
            text-align: center;
            padding: var(--spacing-8);
            color: var(--gray-500);
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: var(--spacing-6);
        }
        
        .back-button:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1>IUC</h1>
                    <p>Voting System</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php?page=dashboard" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
                <a href="index.php?page=voting" class="nav-item active">
                    <span class="nav-icon">🗳️</span>
                    <span>Vote Now</span>
                </a>
                <a href="index.php?page=results" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Results</span>
                </a>
                <a href="index.php?page=profile" class="nav-item">
                    <span class="nav-icon">👤</span>
                    <span>Profile</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                        <div class="user-type">Student</div>
                    </div>
                </div>
                <a href="index.php?page=logout" class="btn btn-secondary btn-sm">
                    Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <a href="index.php?page=dashboard" class="back-button">
                    ← Back to Dashboard
                </a>
                <h1>Vote Now</h1>
                <p class="breadcrumb">Home / Voting</p>
            </header>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'voted'): ?>
                <div class="alert alert-success">
                    Your vote has been successfully cast! Thank you for participating.
                </div>
            <?php endif; ?>
            
            <div class="voting-container">
                <?php if ($selectedElection): ?>
                    <!-- Single Election View -->
                    <div class="election-card">
                        <div class="election-header">
                            <h2 class="election-title"><?php echo htmlspecialchars($selectedElection['title']); ?></h2>
                            <p class="election-description"><?php echo htmlspecialchars($selectedElection['description']); ?></p>
                            <div class="election-meta">
                                <span>📅 Voting Period: <?php echo date('M d, Y', strtotime($selectedElection['start_date'])); ?> - <?php echo date('M d, Y', strtotime($selectedElection['end_date'])); ?></span>
                                <span>👥 <?php echo $selectedElection['total_votes']; ?> votes cast</span>
                            </div>
                        </div>
                        
                        <?php if ($hasVoted): ?>
                            <div class="candidates-grid">
                                <div class="no-elections">
                                    <div class="voted-badge">✓ You have already voted in this election</div>
                                    <p>Your vote has been securely recorded on the blockchain.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="votingForm">
                                <input type="hidden" name="action" value="vote">
                                <input type="hidden" name="election_id" value="<?php echo $selectedElection['id']; ?>">
                                
                                <div class="candidates-grid">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="candidate-card" onclick="selectCandidate(<?php echo $candidate['id']; ?>)">
                                            <div class="candidate-avatar">
                                                <?php echo strtoupper(substr($candidate['name'], 0, 2)); ?>
                                            </div>
                                            <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></h3>
                                            <p class="candidate-description">
                                                <?php echo htmlspecialchars($candidate['description'] ?: 'Candidate for ' . $selectedElection['title']); ?>
                                            </p>
                                            <button type="button" class="vote-button" onclick="selectCandidate(<?php echo $candidate['id']; ?>)">
                                                Select
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="voting-actions">
                                    <input type="hidden" name="candidate_id" id="selectedCandidate" required>
                                    <button type="submit" class="confirm-vote-btn" id="confirmVote" disabled>
                                        Cast Your Vote
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- All Active Elections -->
                    <div class="elections-list">
                        <?php if (empty($activeElections)): ?>
                            <div class="no-elections">
                                <h3>No Active Elections</h3>
                                <p>There are no active elections at the moment. Please check back later.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeElections as $elec): ?>
                                <div class="election-card">
                                    <div class="election-header">
                                        <h2 class="election-title"><?php echo htmlspecialchars($elec['title']); ?></h2>
                                        <p class="election-description"><?php echo htmlspecialchars($elec['description']); ?></p>
                                        <div class="election-meta">
                                            <span>📅 Voting Period: <?php echo date('M d, Y', strtotime($elec['start_date'])); ?> - <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                                            <span>👥 <?php echo $elec['total_votes']; ?> votes cast</span>
                                        </div>
                                    </div>
                                    
                                    <div class="voting-actions">
                                        <?php if ($election->hasUserVoted($userId, $elec['id'])): ?>
                                            <span class="voted-badge">✓ Voted</span>
                                        <?php else: ?>
                                            <a href="index.php?page=voting&election_id=<?php echo $elec['id']; ?>" class="btn btn-primary">
                                                Vote Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        let selectedCandidateId = null;
        
        function selectCandidate(candidateId) {
            // Remove previous selection
            document.querySelectorAll('.candidate-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked candidate
            const selectedCard = document.querySelector(`.candidate-card:has(button[onclick="selectCandidate(${candidateId})"])`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Update hidden input
            document.getElementById('selectedCandidate').value = candidateId;
            
            // Enable confirm button
            document.getElementById('confirmVote').disabled = false;
            
            selectedCandidateId = candidateId;
        }
        
        // Handle form submission
        document.getElementById('votingForm').addEventListener('submit', function(e) {
            if (!selectedCandidateId) {
                e.preventDefault();
                alert('Please select a candidate before voting.');
                return;
            }
            
            if (!confirm('Are you sure you want to cast your vote? This action cannot be undone.')) {
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
