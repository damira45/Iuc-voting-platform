<?php
/**
 * IUC Voting System - Vote Verification (Admin)
 * Fully dynamic — queries real votes from the database
 */

require_once 'config/config.php';

// ── Real stats from DB ──────────────────────────────────────────────────────
$totalVotes = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalVoters = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM votes")->fetchColumn();
$totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
$activeElections = (int)$pdo->query("SELECT COUNT(*) FROM elections WHERE status='active'")->fetchColumn();

// ── Handle transaction hash lookup ─────────────────────────────────────────
$verification_result = null;
$search_hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['transaction_hash'])) {
    $search_hash = trim($_POST['transaction_hash']);

    $stmt = $pdo->prepare("
        SELECT v.id, v.transaction_hash, v.created_at,
               u.name  AS voter_name,  u.email AS voter_email,
               s.student_id,
               e.title AS election_title,
               c.name  AS candidate_name
        FROM votes v
        JOIN users      u ON v.user_id      = u.id
        LEFT JOIN students s ON s.user_id   = u.id
        JOIN elections  e ON v.election_id  = e.id
        JOIN candidates c ON v.candidate_id = c.id
        WHERE v.transaction_hash = ?
        LIMIT 1
    ");
    $stmt->execute([$search_hash]);
    $verification_result = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── Recent votes (last 20) ──────────────────────────────────────────────────
$recentVotes = $pdo->query("
    SELECT v.transaction_hash, v.created_at,
           u.name  AS voter_name,
           e.title AS election_title,
           c.name  AS candidate_name
    FROM votes v
    JOIN users      u ON v.user_id      = u.id
    JOIN elections  e ON v.election_id  = e.id
    JOIN candidates c ON v.candidate_id = c.id
    ORDER BY v.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Verification - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f8fafc; color:#1e293b; }

        .admin-layout { display:flex; min-height:100vh; }

        .sidebar { width:260px; background:linear-gradient(135deg,#0B3C5D,#3282B8); color:white; padding:2rem 0; position:fixed; height:100vh; overflow-y:auto; }
        .sidebar-header { padding:0 1.5rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.1); margin-bottom:1rem; }
        .sidebar-header h1 { font-size:1.2rem; font-weight:700; }
        .sidebar-header p  { font-size:.8rem; opacity:.7; margin-top:.25rem; }
        .sidebar a { display:flex; align-items:center; gap:.75rem; padding:.7rem 1.5rem; color:rgba(255,255,255,.8); text-decoration:none; transition:all .2s; font-size:.9rem; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,.15); color:white; border-left:3px solid #BBE1FA; }
        .sidebar .section-label { font-size:.7rem; text-transform:uppercase; opacity:.6; padding:.75rem 1.5rem .25rem; letter-spacing:.05em; }

        .main { flex:1; margin-left:260px; padding:1.5rem; }

        .page-header { background:white; padding:1.25rem 1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; }
        .page-header h1 { font-size:1.4rem; font-weight:700; color:#0B3C5D; }
        .page-header p  { color:#64748b; font-size:.9rem; margin-top:.2rem; }
        .live-badge { display:inline-flex; align-items:center; gap:.4rem; background:#dcfce7; color:#166534; padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:600; }
        .live-dot { width:8px; height:8px; background:#22c55e; border-radius:50%; animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1rem; margin-bottom:1.5rem; }
        .stat-card { background:white; border-radius:10px; padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,.06); text-align:center; border-top:4px solid #3282B8; }
        .stat-card.green  { border-top-color:#10b981; }
        .stat-card.orange { border-top-color:#f59e0b; }
        .stat-card.purple { border-top-color:#8b5cf6; }
        .stat-number { font-size:1.8rem; font-weight:700; color:#0B3C5D; }
        .stat-card.green  .stat-number { color:#10b981; }
        .stat-card.orange .stat-number { color:#f59e0b; }
        .stat-card.purple .stat-number { color:#8b5cf6; }
        .stat-label { font-size:.8rem; color:#64748b; margin-top:.25rem; }

        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); padding:1.5rem; margin-bottom:1.5rem; }
        .card-title { font-size:1.1rem; font-weight:600; color:#374151; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }

        .search-form { display:flex; gap:.75rem; }
        .search-form input { flex:1; padding:.75rem 1rem; border:2px solid #e2e8f0; border-radius:8px; font-size:.95rem; font-family:'Courier New',monospace; }
        .search-form input:focus { outline:none; border-color:#3282B8; box-shadow:0 0 0 3px rgba(50,130,184,.1); }
        .btn { padding:.75rem 1.5rem; border:none; border-radius:8px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; font-size:.9rem; transition:all .2s; }
        .btn-primary { background:linear-gradient(135deg,#3282B8,#0B3C5D); color:white; }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(50,130,184,.3); }

        /* Result box */
        .result-box { margin-top:1.25rem; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0; }
        .result-box.found   { border-color:#10b981; }
        .result-box.missing { border-color:#ef4444; }

        .result-banner { padding:1rem 1.5rem; display:flex; align-items:center; gap:.75rem; }
        .result-box.found   .result-banner { background:#d1fae5; color:#065f46; }
        .result-box.missing .result-banner { background:#fee2e2; color:#991b1b; }
        .result-banner i { font-size:1.5rem; }
        .result-banner h3 { font-size:1rem; font-weight:700; }
        .result-banner p  { font-size:.85rem; opacity:.8; }

        .result-body { padding:1.25rem 1.5rem; }
        .detail-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.75rem; }
        .detail-item { background:#f8fafc; border-radius:8px; padding:.75rem 1rem; }
        .detail-item .label { font-size:.75rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.25rem; }
        .detail-item .value { font-size:.95rem; color:#1e293b; font-weight:500; word-break:break-all; }
        .hash-box { background:#1e293b; color:#10b981; font-family:'Courier New',monospace; font-size:.8rem; padding:.75rem 1rem; border-radius:8px; word-break:break-all; margin-top:.75rem; }

        /* Recent votes table */
        table { width:100%; border-collapse:collapse; font-size:.85rem; }
        th { background:#f8fafc; padding:.6rem .75rem; text-align:left; font-weight:600; color:#374151; border-bottom:2px solid #e2e8f0; }
        td { padding:.6rem .75rem; border-bottom:1px solid #f1f5f9; color:#374151; }
        tr:hover td { background:#f8fafc; }
        .hash-short { font-family:'Courier New',monospace; color:#3282B8; font-size:.8rem; cursor:pointer; }
        .hash-short:hover { color:#0B3C5D; text-decoration:underline; }

        @media(max-width:768px) { .sidebar{display:none} .main{margin-left:0} .search-form{flex-direction:column} .detail-grid{grid-template-columns:1fr} }
    </style>
</head>
<body>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Panel</h1>
            <p>IUC Voting System</p>
        </div>
        <div class="section-label">Main</div>
        <a href="index.php?page=admin"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <div class="section-label">Elections</div>
        <a href="index.php?page=elections"><i class="fas fa-vote-yea"></i> Elections</a>
        <a href="index.php?page=results"><i class="fas fa-chart-bar"></i> Results</a>
        <div class="section-label">Voters</div>
        <a href="index.php?page=voter_registration"><i class="fas fa-user-plus"></i> Register Voters</a>
        <a href="index.php?page=voter_list"><i class="fas fa-users-cog"></i> Voter List</a>
        <div class="section-label">Audit</div>
        <a href="index.php?page=vote_verification" class="active"><i class="fas fa-check-circle"></i> Vote Verification</a>
        <a href="index.php?page=logout" style="margin-top:2rem; color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main -->
    <main class="main">

        <div class="page-header">
            <div>
                <h1>Vote Verification</h1>
                <p>Look up any vote by its transaction hash</p>
            </div>
            <div class="live-badge"><div class="live-dot"></div> LIVE</div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalVotes ?></div>
                <div class="stat-label">Total Votes Cast</div>
            </div>
            <div class="stat-card green">
                <div class="stat-number"><?= $totalVoters ?></div>
                <div class="stat-label">Unique Voters</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-number"><?= $totalElections ?></div>
                <div class="stat-label">Total Elections</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-number"><?= $activeElections ?></div>
                <div class="stat-label">Active Elections</div>
            </div>
        </div>

        <!-- Search -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-search"></i> Verify by Transaction Hash</h2>
            <form method="POST" class="search-form">
                <input type="text" name="transaction_hash"
                       placeholder="Paste transaction hash e.g. MOCK-abc123... or 0x7f9a..."
                       value="<?= htmlspecialchars($search_hash) ?>" required>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-shield-alt"></i> Verify
                </button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_hash): ?>
                <?php if ($verification_result): ?>
                    <div class="result-box found">
                        <div class="result-banner">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <h3>Vote Verified ✓</h3>
                                <p>This transaction exists in the database and is authentic.</p>
                            </div>
                        </div>
                        <div class="result-body">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="label">Voter Name</div>
                                    <div class="value"><?= htmlspecialchars($verification_result['voter_name']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="label">Student ID</div>
                                    <div class="value"><?= htmlspecialchars($verification_result['student_id'] ?? 'N/A') ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="label">Email</div>
                                    <div class="value"><?= htmlspecialchars($verification_result['voter_email']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="label">Election</div>
                                    <div class="value"><?= htmlspecialchars($verification_result['election_title']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="label">Voted For</div>
                                    <div class="value"><?= htmlspecialchars($verification_result['candidate_name']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="label">Date &amp; Time</div>
                                    <div class="value"><?= date('M d, Y H:i:s', strtotime($verification_result['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="hash-box">
                                <strong style="color:#94a3b8;">Transaction Hash:</strong><br>
                                <?= htmlspecialchars($verification_result['transaction_hash']) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="result-box missing">
                        <div class="result-banner">
                            <i class="fas fa-times-circle"></i>
                            <div>
                                <h3>Not Found</h3>
                                <p>No vote with this transaction hash exists in the system.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Votes -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-history"></i> Recent Votes (Last 20)</h2>
            <?php if (empty($recentVotes)): ?>
                <p style="color:#64748b; text-align:center; padding:2rem;">No votes recorded yet.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Voter</th>
                                <th>Election</th>
                                <th>Candidate</th>
                                <th>Date</th>
                                <th>Transaction Hash</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVotes as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['voter_name']) ?></td>
                                <td><?= htmlspecialchars($v['election_title']) ?></td>
                                <td><?= htmlspecialchars($v['candidate_name']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($v['created_at'])) ?></td>
                                <td>
                                    <span class="hash-short"
                                          title="<?= htmlspecialchars($v['transaction_hash']) ?>"
                                          onclick="fillHash('<?= htmlspecialchars($v['transaction_hash']) ?>')">
                                        <?= htmlspecialchars(substr($v['transaction_hash'], 0, 18)) ?>…
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
function fillHash(hash) {
    document.querySelector('input[name="transaction_hash"]').value = hash;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Auto-fill hash if passed from results page
window.addEventListener('load', () => {
    const stored = sessionStorage.getItem('verifyHash');
    if (stored) {
        const input = document.querySelector('input[name="transaction_hash"]');
        if (input && !input.value) {
            input.value = stored;
        }
        sessionStorage.removeItem('verifyHash');
    }
});
</script>
</body>
</html>
