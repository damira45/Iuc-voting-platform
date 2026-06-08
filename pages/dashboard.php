<?php
/**
 * IUC Voting System - Dashboard Page
 * Main dashboard for logged-in users
 */

require_once 'includes/election.php';
require_once 'includes/student.php';

$election = new Election();
$student = new Student();

$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'];

// Get data based on user type
if ($userType === 'admin') {
    $stats = [
        'total_students' => $student->getTotalStudents(),
        'total_elections' => $election->getTotalElections(),
        'total_votes' => $election->getTotalVotes(),
        'pending_registrations' => $student->getPendingRegistrationsCount()
    ];
    
    $recentElections = $election->getRecentElections(5);
    $pendingStudents = $student->getPendingRegistrations(10);
    
} else {
    $activeElections = $election->getActiveElections();
    $userVotes = $election->getUserVotes($userId);
    $canVote = $election->canUserVote($userId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IUC Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                <a href="index.php?page=dashboard" class="nav-item active">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
                
                <?php if ($userType === 'admin'): ?>
                    <a href="index.php?page=elections" class="nav-item">
                        <span class="nav-icon">🗳️</span>
                        <span>Elections</span>
                    </a>
                    <a href="index.php?page=admin" class="nav-item">
                        <span class="nav-icon">⚙️</span>
                        <span>Admin Panel</span>
                    </a>
                    <a href="index.php?page=results" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Results</span>
                    </a>
                <?php else: ?>
                    <a href="index.php?page=voting" class="nav-item">
                        <span class="nav-icon">🗳️</span>
                        <span>Vote Now</span>
                    </a>
                    <a href="index.php?page=results" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Results</span>
                    </a>
                <?php endif; ?>
                
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
                        <div class="user-type"><?php echo ucfirst($userType); ?></div>
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
                <h1>Dashboard</h1>
                <p class="breadcrumb">Home / Dashboard</p>
            </header>
            
            <?php if ($userType === 'admin'): ?>
                <!-- Admin Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🗳️</div>
                        <div class="stat-value"><?php echo $stats['total_elections']; ?></div>
                        <div class="stat-label">Total Elections</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value"><?php echo $stats['total_votes']; ?></div>
                        <div class="stat-label">Total Votes</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-value"><?php echo $stats['pending_registrations']; ?></div>
                        <div class="stat-label">Pending Registrations</div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Recent Elections</h2>
                            <a href="index.php?page=elections" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($recentElections)): ?>
                                <p class="text-muted">No elections found</p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentElections as $elec): ?>
                                            <tr>
                                                <td><?php echo $elec['title']; ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $elec['status']; ?>">
                                                        <?php echo ucfirst($elec['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($elec['end_date'])); ?></td>
                                                <td>
                                                    <a href="index.php?page=elections&action=view&id=<?php echo $elec['id']; ?>" 
                                                       class="btn btn-secondary btn-sm">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Pending Registrations</h2>
                            <a href="index.php?page=admin" class="btn btn-primary btn-sm">Manage</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($pendingStudents)): ?>
                                <p class="text-muted">No pending registrations</p>
                            <?php else: ?>
                                <div class="pending-list">
                                    <?php foreach ($pendingStudents as $student): ?>
                                        <div class="pending-item">
                                            <div class="pending-info">
                                                <div class="pending-name"><?php echo $student['name']; ?></div>
                                                <div class="pending-details">
                                                    <?php echo $student['student_id']; ?> • <?php echo $student['department']; ?>
                                                </div>
                                            </div>
                                            <div class="pending-actions">
                                                <a href="index.php?page=admin&action=approve&id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-success btn-sm">Approve</a>
                                                <a href="index.php?page=admin&action=reject&id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-danger btn-sm">Reject</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Student Dashboard -->
                <div class="welcome-section">
                    <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
                    <p>Participate in active elections and track your voting history.</p>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Active Elections</h2>
                            <a href="index.php?page=voting" class="btn btn-primary btn-sm">Vote Now</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($activeElections)): ?>
                                <p class="text-muted">No active elections at the moment</p>
                            <?php else: ?>
                                <div class="elections-list">
                                    <?php foreach ($activeElections as $elec): ?>
                                        <div class="election-item">
                                            <div class="election-info">
                                                <h3><?php echo $elec['title']; ?></h3>
                                                <p><?php echo $elec['description']; ?></p>
                                                <div class="election-meta">
                                                    <span>Ends: <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                                                    <span><?php echo $elec['total_votes']; ?> votes cast</span>
                                                </div>
                                            </div>
                                            <div class="election-actions">
                                                <?php if ($canVote && !$election->hasUserVoted($userId, $elec['id'])): ?>
                                                    <a href="index.php?page=voting&action=vote&id=<?php echo $elec['id']; ?>" 
                                                       class="btn btn-primary">Vote</a>
                                                <?php elseif ($election->hasUserVoted($userId, $elec['id'])): ?>
                                                    <span class="badge badge-success">Voted</span>
                                                <?php else: ?>
                                                    <span class="badge badge-muted">Not Eligible</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Your Voting History</h2>
                            <a href="index.php?page=results" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($userVotes)): ?>
                                <p class="text-muted">You haven't voted in any elections yet</p>
                            <?php else: ?>
                                <div class="votes-list">
                                    <?php foreach (array_slice($userVotes, 0, 5) as $vote): ?>
                                        <div class="vote-item">
                                            <div class="vote-info">
                                                <h4><?php echo $vote['election_title']; ?></h4>
                                                <p>Voted for: <?php echo $vote['candidate_name']; ?></p>
                                                <div class="vote-meta">
                                                    <span><?php echo date('M d, Y', strtotime($vote['voted_at'])); ?></span>
                                                    <span>Hash: <?php echo substr($vote['transaction_hash'], 0, 10) ?>...</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
