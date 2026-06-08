<?php
/**
 * IUC Voting System - Student Dashboard
 * Enhanced student voting platform with modern design
 */

require_once 'includes/election.php';
require_once 'includes/student.php';

$election = new Election();
$student = new Student();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Get student data
$activeElections = $election->getActiveElections();
$upcomingElections = $election->getUpcomingElections();
$userVotes = $election->getUserVotes($userId);
$canVote = $election->canUserVote($userId);

// Get student profile
$stmt = $pdo->prepare("SELECT s.student_id, s.department, s.level FROM students s WHERE s.user_id = ?");
$stmt->execute([$userId]);
$studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate voting statistics
$totalVotes = count($userVotes);
$eligibleToVote = 0;
$votedInActive = 0;

foreach ($activeElections as $elec) {
    if ($canVote && !$election->hasUserVoted($userId, $elec['id'])) {
        $eligibleToVote++;
    }
    if ($election->hasUserVoted($userId, $elec['id'])) {
        $votedInActive++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IUC Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #e8eef5;
            min-height: 100vh;
            color: #1e293b;
            display: flex;
        }

        /* ── LEFT SIDEBAR ── */
        .sidebar {
            width: 230px;
            min-height: 100vh;
            background: #1a2332;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-profile {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .profile-avatar {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem; font-weight: 700;
            margin-bottom: .6rem;
        }

        .profile-name {
            font-size: .9rem; font-weight: 700; color: #f1f5f9;
            margin-bottom: .15rem;
        }

        .profile-role {
            font-size: .72rem; color: #94a3b8;
        }

        .profile-stats {
            display: flex; gap: 1rem; margin-top: .75rem;
        }

        .profile-stat {
            font-size: .72rem; color: #64748b;
        }

        .profile-stat span {
            color: #94a3b8; font-weight: 600;
        }

        /* Sidebar nav */
        .sidebar-section-label {
            font-size: .65rem; text-transform: uppercase;
            letter-spacing: .08em; color: #475569;
            padding: 1rem 1.25rem .35rem; font-weight: 600;
        }

        .nav-item {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1.25rem;
            color: #94a3b8; text-decoration: none;
            font-size: .83rem; font-weight: 500;
            transition: all .2s; border-left: 3px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255,255,255,.06); color: #f1f5f9;
        }

        .nav-item.active {
            background: rgba(50,130,184,.15);
            color: #fff; border-left-color: #3282B8; font-weight: 600;
        }

        .nav-item .nav-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; background: rgba(255,255,255,.06);
        }

        .nav-item.active .nav-icon {
            background: linear-gradient(135deg,#3282B8,#0B3C5D);
            color: #fff;
        }

        .nav-item-text { flex: 1; }
        .nav-item-title { font-size: .83rem; }
        .nav-item-sub { font-size: .7rem; color: #64748b; margin-top: .1rem; }
        .nav-item.active .nav-item-sub { color: #93c5fd; }

        /* Sidebar stats (MY REPORTS style) */
        .sidebar-reports {
            padding: .75rem 1.25rem; margin-top: auto;
            border-top: 1px solid rgba(255,255,255,.06);
        }

        .report-stat {
            display: flex; justify-content: space-between;
            align-items: center; padding: .3rem 0;
            font-size: .78rem; color: #64748b;
        }

        .report-stat .dot {
            width: 10px; height: 10px; border-radius: 2px;
            margin-right: .4rem; display: inline-block;
        }

        /* ── MAIN CONTENT ── */
        .dashboard-container {
            margin-left: 230px;
            flex: 1;
            padding: 1.75rem 2rem;
            min-height: 100vh;
            background: #e8eef5;
        }

        /* Top bar inside main */
        .topbar {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 1.5rem;
            flex-wrap: wrap; gap: .75rem;
        }

        .topbar h1 {
            font-size: 1.3rem; font-weight: 800; color: #0B3C5D;
            display: flex; align-items: center; gap: .5rem;
        }

        .topbar p { color: #64748b; font-size: .85rem; margin-top: .15rem; }

        .logout-btn {
            background: #fee2e2; color: #dc2626;
            border: 1px solid #fecaca;
            padding: .45rem .9rem; border-radius: 8px;
            text-decoration: none; font-size: .82rem; font-weight: 600;
            display: flex; align-items: center; gap: .4rem;
            transition: all .2s;
        }

        .logout-btn:hover { background: #dc2626; color: #fff; }

        /* Header section — hidden, replaced by sidebar */
        .header { display: none; }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem; margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #fff; border-radius: 12px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            display: flex; align-items: center; gap: .9rem;
            border-left: 4px solid #3282B8;
        }

        .stat-icon { font-size: 1.6rem; }

        .stat-value {
            font-size: 1.5rem; font-weight: 800; color: #0B3C5D; line-height: 1;
        }

        .stat-label {
            font-size: .7rem; color: #64748b;
            text-transform: uppercase; letter-spacing: .04em;
            margin-top: .15rem;
        }

        /* Quick actions */
        .quick-actions {
            display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }

        .quick-action-btn {
            flex: 1; min-width: 180px;
            background: #fff; border-radius: 12px;
            padding: 1.25rem 1rem; text-align: center;
            text-decoration: none; color: #1e293b;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            transition: all .2s; border: 1px solid #e2e8f0;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(50,130,184,.15);
            border-color: #3282B8;
        }

        .quick-action-btn i {
            font-size: 1.6rem; margin-bottom: .5rem; display: block;
        }

        .quick-action-btn h3 { font-size: .9rem; font-weight: 700; margin-bottom: .2rem; }
        .quick-action-btn p  { font-size: .75rem; color: #64748b; }

        /* Main grid */
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem; margin-bottom: 1.5rem;
        }

        @media (max-width: 1024px) { .main-grid { grid-template-columns: 1fr; } }

        /* Cards */
        .card {
            background: #fff; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05); overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }

        .card-header h2 {
            font-size: .95rem; font-weight: 700; color: #0B3C5D;
            display: flex; align-items: center; gap: .4rem;
        }

        .card-content { padding: 1.25rem; }

        /* Buttons */
        .btn {
            padding: .45rem .95rem; border: none; border-radius: 8px;
            font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: .4rem;
            transition: all .2s; cursor: pointer; font-size: .82rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0B3C5D, #3282B8); color: #fff;
        }

        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(50,130,184,.35); }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669); color: #fff;
        }

        .btn-success:hover { transform: translateY(-1px); }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-sm { padding: .35rem .8rem; font-size: .78rem; }

        /* Elections list */
        .elections-list { display: flex; flex-direction: column; gap: .75rem; }

        .election-item {
            background: #f8fafc; border-radius: 10px;
            padding: 1rem 1.1rem; border-left: 3px solid #3282B8;
            transition: all .2s;
        }

        .election-item:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0,0,0,.07);
        }

        .election-info h3 { font-size: .9rem; margin-bottom: .3rem; color: #0B3C5D; font-weight: 600; }
        .election-info p  { color: #64748b; font-size: .8rem; margin-bottom: .5rem; line-height: 1.4; }

        .election-meta { display: flex; gap: .6rem; flex-wrap: wrap; margin-bottom: .6rem; }

        .election-meta span {
            background: #e2e8f0; padding: .15rem .6rem;
            border-radius: 20px; font-size: .72rem; color: #475569;
        }

        .election-actions { display: flex; gap: .5rem; align-items: center; }

        /* Badge */
        .badge {
            padding: .3rem .7rem; border-radius: 20px;
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-muted   { background: #f1f5f9; color: #64748b; }

        /* Votes list */
        .votes-list { display: flex; flex-direction: column; gap: .75rem; }

        .vote-item {
            background: #f8fafc; border-radius: 10px;
            padding: 1rem; border-left: 3px solid #10b981;
        }

        .vote-info h4 { font-size: .88rem; margin-bottom: .3rem; color: #1e293b; }
        .vote-info p  { color: #64748b; font-size: .8rem; margin-bottom: .3rem; }

        .vote-meta {
            display: flex; gap: .75rem; flex-wrap: wrap;
            font-size: .72rem; color: #94a3b8;
        }

        .text-muted { color: #94a3b8; text-align: center; padding: 1.5rem; font-size: .85rem; }

        /* Upcoming elections card */
        .upcoming-section { margin-top: 1.25rem; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .dashboard-container { margin-left: 0; padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-profile">
            <div class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 2)); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="profile-role">Citizen Panel</div>
            <div class="profile-stats">
                <div class="profile-stat"><span><?php echo $totalVotes; ?></span> votes</div>
                <div class="profile-stat"><span><?php echo $votedInActive; ?></span> resolved</div>
            </div>
        </div>

        <div class="sidebar-section-label">MY TASKS</div>

        <a href="index.php?page=simple_voting" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-vote-yea"></i></div>
            <div class="nav-item-text">
                <div class="nav-item-title">Vote Now</div>
                <div class="nav-item-sub">Cast your vote in active elections</div>
            </div>
        </a>

        <a href="index.php?page=results" class="nav-item">
            <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
            <div class="nav-item-text">
                <div class="nav-item-title">View Results</div>
                <div class="nav-item-sub">See election standings</div>
            </div>
        </a>

        <a href="index.php?page=verify" class="nav-item">
            <div class="nav-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="nav-item-text">
                <div class="nav-item-title">Verify My Vote</div>
                <div class="nav-item-sub">Confirm blockchain record</div>
            </div>
        </a>

        <a href="index.php?page=profile" class="nav-item">
            <div class="nav-icon"><i class="fas fa-user"></i></div>
            <div class="nav-item-text">
                <div class="nav-item-title">My Profile</div>
                <div class="nav-item-sub">View your information</div>
            </div>
        </a>

        <div class="sidebar-section-label">MY VOTES</div>

        <div class="sidebar-reports">
            <div class="report-stat">
                <span><span class="dot" style="background:#f59e0b;"></span>Pending</span>
                <span><?php echo $eligibleToVote; ?></span>
            </div>
            <div class="report-stat">
                <span><span class="dot" style="background:#3282B8;"></span>Voted</span>
                <span><?php echo $votedInActive; ?></span>
            </div>
            <div class="report-stat">
                <span><span class="dot" style="background:#10b981;"></span>Upcoming</span>
                <span><?php echo count($upcomingElections); ?></span>
            </div>
        </div>

        <div style="padding:1rem 1.25rem;margin-top:.5rem;border-top:1px solid rgba(255,255,255,.06);">
            <a href="index.php?page=logout" style="display:flex;align-items:center;gap:.5rem;color:#ef4444;text-decoration:none;font-size:.82rem;font-weight:600;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <div class="dashboard-container">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h1><i class="fas fa-tachometer-alt" style="color:#3282B8"></i> Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($userName); ?>! <?php echo $eligibleToVote > 0 ? $eligibleToVote . ' election(s) waiting for your vote.' : 'You\'re all caught up.'; ?></p>
            </div>
            <a href="index.php?page=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <!-- Header (hidden — replaced by sidebar + topbar) -->
        <div class="header" style="display:none">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p>Participate in active elections and track your voting history</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 2)); ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($userName); ?></h3>
                    <p><?php echo htmlspecialchars($studentProfile['student_id']); ?></p>
                    <p><?php echo htmlspecialchars($studentProfile['department']); ?> • Level <?php echo htmlspecialchars($studentProfile['level']); ?></p>
                    <a href="index.php?page=logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🗳️</div>
                <div class="stat-value"><?php echo count($activeElections); ?></div>
                <div class="stat-label">Active Elections</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $totalVotes; ?></div>
                <div class="stat-label">Votes Cast</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?php echo $eligibleToVote; ?></div>
                <div class="stat-label">Eligible to Vote</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo count($upcomingElections); ?></div>
                <div class="stat-label">Upcoming Elections</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="index.php?page=simple_voting" class="quick-action-btn">
                <i class="fas fa-vote-yea" style="color: #667eea;"></i>
                <h3>Vote Now</h3>
                <p>Cast your vote in active elections</p>
            </a>
            <a href="index.php?page=results" class="quick-action-btn">
                <i class="fas fa-chart-bar" style="color: #10b981;"></i>
                <h3>View Results</h3>
                <p>See election results and statistics</p>
            </a>
            <a href="index.php?page=profile" class="quick-action-btn">
                <i class="fas fa-user" style="color: #764ba2;"></i>
                <h3>My Profile</h3>
                <p>Update your profile information</p>
            </a>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Active Elections -->
            <div class="card">
                <div class="card-header">
                    <h2>Active Elections</h2>
                    <a href="index.php?page=simple_voting" class="btn btn-primary btn-sm">
                        <i class="fas fa-vote-yea"></i> Vote Now
                    </a>
                </div>
                <div class="card-content">
                    <?php if (empty($activeElections)): ?>
                        <p class="text-muted">No active elections at the moment</p>
                    <?php else: ?>
                        <div class="elections-list">
                            <?php foreach ($activeElections as $elec): ?>
                                <div class="election-item">
                                    <div class="election-info">
                                        <h3><?php echo htmlspecialchars($elec['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($elec['description']); ?></p>
                                        <div class="election-meta">
                                            <span><i class="fas fa-calendar"></i> Ends: <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                                            <span><i class="fas fa-users"></i> <?php echo $elec['total_votes']; ?> votes cast</span>
                                        </div>
                                    </div>
                                    <div class="election-actions">
                                        <?php if ($canVote && !$election->hasUserVoted($userId, $elec['id'])): ?>
                                            <a href="index.php?page=simple_voting&election_id=<?php echo $elec['id']; ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-vote-yea"></i> Vote
                                            </a>
                                        <?php elseif ($election->hasUserVoted($userId, $elec['id'])): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Voted
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-muted">
                                                <i class="fas fa-times"></i> Not Eligible
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Voting History -->
            <div class="card">
                <div class="card-header">
                    <h2>Your Voting History</h2>
                    <a href="index.php?page=results" class="btn btn-primary btn-sm">
                        <i class="fas fa-chart-bar"></i> View All
                    </a>
                </div>
                <div class="card-content">
                    <?php if (empty($userVotes)): ?>
                        <p class="text-muted">You haven't voted in any elections yet</p>
                    <?php else: ?>
                        <div class="votes-list">
                            <?php foreach (array_slice($userVotes, 0, 5) as $vote): ?>
                                <div class="vote-item">
                                    <div class="vote-info">
                                        <h4><?php echo htmlspecialchars($vote['election_title']); ?></h4>
                                        <p>Voted for: <?php echo htmlspecialchars($vote['candidate_name']); ?></p>
                                        <div class="vote-meta">
                                            <span><i class="fas fa-calendar"></i> <?php echo !empty($vote['voted_at']) ? date('M d, Y', strtotime($vote['voted_at'])) : 'N/A'; ?></span>
                                            <span><i class="fas fa-hashtag"></i> <?php echo substr($vote['transaction_hash'], 0, 10); ?>...</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Elections -->
        <?php if (!empty($upcomingElections)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Upcoming Elections</h2>
                <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> Get ready to vote!
                </span>
            </div>
            <div class="card-content">
                <div class="elections-list">
                    <?php foreach ($upcomingElections as $elec): ?>
                        <div class="election-item" style="border-left-color: #10b981;">
                            <div class="election-info">
                                <h3><?php echo htmlspecialchars($elec['title']); ?></h3>
                                <p><?php echo htmlspecialchars($elec['description']); ?></p>
                                <div class="election-meta">
                                    <span><i class="fas fa-calendar"></i> Starts: <?php echo date('M d, Y', strtotime($elec['start_date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> Ends: <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                                </div>
                            </div>
                            <div class="election-actions">
                                <span class="badge badge-muted">
                                    <i class="fas fa-clock"></i> Upcoming
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
