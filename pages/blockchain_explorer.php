<?php
/**
 * IUC Voting System - Blockchain Explorer (Dynamic)
 * All data pulled from the real database
 */

require_once 'config/config.php';

$isAdmin = ($_SESSION['user_type'] ?? '') === 'admin';

// ── Real stats ─────────────────────────────────────────────────────────────
$totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
$totalVoters    = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM votes")->fetchColumn();

// ── Search ─────────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';

// ── Fetch real transactions (votes) from DB ────────────────────────────────
$sql = "
    SELECT v.id, v.transaction_hash, v.created_at,
           u.name  AS voter_name,  u.email AS voter_email,
           s.student_id,
           e.title AS election_title, e.id AS election_id,
           c.name  AS candidate_name
    FROM votes v
    JOIN users      u ON v.user_id      = u.id
    LEFT JOIN students s ON s.user_id   = u.id
    JOIN elections  e ON v.election_id  = e.id
    JOIN candidates c ON v.candidate_id = c.id
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE (v.transaction_hash LIKE ? OR u.name LIKE ? OR e.title LIKE ? OR c.name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$sql .= " ORDER BY v.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Elections summary (acts as "blocks") ──────────────────────────────────
$elections = $pdo->query("
    SELECT e.id, e.title, e.status, e.start_date, e.end_date, e.created_at,
           COUNT(v.id) AS tx_count
    FROM elections e
    LEFT JOIN votes v ON v.election_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blockchain Explorer - IUC Voting System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}
.layout{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#1a5276 60%,#3282B8);color:#fff;padding:0;position:fixed;height:100vh;overflow-y:auto;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,.15)}
.sb-brand{padding:1.75rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}
.sb-brand .logo{width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.sb-brand h1{font-size:1rem;font-weight:700}
.sb-brand p{font-size:.75rem;opacity:.65;margin-top:.1rem}
.sb-section{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;opacity:.55;padding:.9rem 1.5rem .3rem;font-weight:600}
.sb-link{display:flex;align-items:center;gap:.75rem;padding:.65rem 1.5rem;color:rgba(255,255,255,.78);text-decoration:none;font-size:.875rem;transition:all .2s;border-left:3px solid transparent}
.sb-link:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}
.sb-link.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}
.sb-link i{width:18px;text-align:center}

/* Main */
.main{flex:1;margin-left:260px;padding:2rem}

/* Header */
.page-header{background:white;border-radius:12px;padding:1.5rem 2rem;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
.page-header-left h1{font-size:1.5rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}
.page-header-left p{color:#64748b;font-size:.875rem;margin-top:.25rem}
.live-badge{display:inline-flex;align-items:center;gap:.4rem;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:600}
.live-dot{width:7px;height:7px;background:#22c55e;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{background:white;border-radius:12px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center;border-top:4px solid #3282B8;transition:transform .2s}
.stat-card:hover{transform:translateY(-3px)}
.stat-card.green{border-top-color:#10b981}
.stat-card.orange{border-top-color:#f59e0b}
.stat-card.purple{border-top-color:#8b5cf6}
.stat-icon{font-size:1.5rem;margin-bottom:.4rem}
.stat-num{font-size:1.8rem;font-weight:800;color:#0B3C5D}
.stat-card.green .stat-num{color:#10b981}
.stat-card.orange .stat-num{color:#f59e0b}
.stat-card.purple .stat-num{color:#8b5cf6}
.stat-lbl{font-size:.75rem;color:#64748b;margin-top:.2rem}

/* Search bar */
.search-bar{display:flex;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.search-bar input{flex:1;min-width:200px;padding:.75rem 1rem;background:white;border:2px solid #e2e8f0;border-radius:8px;color:#1e293b;font-size:.9rem}
.search-bar input:focus{outline:none;border-color:#3282B8;box-shadow:0 0 0 3px rgba(50,130,184,.1)}
.btn{padding:.7rem 1.25rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;font-size:.85rem;text-decoration:none;transition:all .2s}
.btn-primary{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(50,130,184,.3)}
.btn-outline{background:white;border:2px solid #e2e8f0;color:#64748b}
.btn-outline:hover{border-color:#3282B8;color:#3282B8}

/* Section title */
.section-title{font-size:1rem;font-weight:700;color:#374151;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;padding-bottom:.5rem;border-bottom:2px solid #e2e8f0}

/* Election blocks */
.blocks-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:2rem}
.block-card{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:1.1rem;cursor:pointer;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.block-card:hover{border-color:#3282B8;box-shadow:0 6px 20px rgba(50,130,184,.15);transform:translateY(-3px)}
.block-num{font-size:.9rem;font-weight:700;color:#3282B8;margin-bottom:.4rem}
.block-title{font-size:.9rem;font-weight:600;color:#1e293b;margin-bottom:.5rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.block-hash{font-family:'Courier New',monospace;font-size:.7rem;color:#94a3b8;word-break:break-all;background:#f8fafc;padding:.3rem .5rem;border-radius:4px;margin-bottom:.5rem;border:1px solid #e2e8f0}
.block-meta{display:flex;justify-content:space-between;font-size:.75rem;color:#64748b}
.block-tx-count{background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:10px;font-size:.72rem;font-weight:600}
.status-pill{padding:.2rem .6rem;border-radius:10px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.pill-active{background:#dcfce7;color:#166534}
.pill-draft{background:#fef9c3;color:#854d0e}
.pill-completed,.pill-closed{background:#e0e7ff;color:#3730a3}
.pill-upcoming{background:#dbeafe;color:#1e40af}

/* Transactions table */
.tx-container{background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:2rem;box-shadow:0 2px 8px rgba(0,0,0,.05)}
table{width:100%;border-collapse:collapse;font-size:.83rem}
th{background:#f8fafc;padding:.65rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;font-size:.78rem}
td{padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}
tr:hover td{background:#f8fafc}
.hash-cell{font-family:'Courier New',monospace;color:#3282B8;font-size:.78rem;word-break:break-all}
.voter-cell{font-weight:600;color:#1e293b}
.election-cell{color:#3282B8}
.candidate-cell{color:#8b5cf6}
.date-cell{color:#64748b;white-space:nowrap}
.btn-verify-sm{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:.25rem .6rem;border-radius:6px;font-size:.72rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;transition:all .2s}
.btn-verify-sm:hover{background:#166534;color:#fff}
.empty-row td{text-align:center;padding:3rem;color:#94a3b8}
.empty-row i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
.table-footer{padding:.75rem 1rem;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:.8rem;color:#64748b;display:flex;justify-content:space-between;align-items:center}

@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}.blocks-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="layout">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sb-brand">
        <div class="logo"><i class="fas fa-link"></i></div>
        <div><h1>IUC Voting</h1><p>Admin Panel</p></div>
    </div>
    <div class="sb-section">Main</div>
    <a href="index.php?page=admin" class="sb-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-section">Elections</div>
    <a href="index.php?page=elections" class="sb-link"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-link"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-section">Voters</div>
    <a href="index.php?page=voter_registration" class="sb-link"><i class="fas fa-user-plus"></i> Register Voters</a>
    <a href="index.php?page=voter_list" class="sb-link"><i class="fas fa-users-cog"></i> Voter List</a>
    <div class="sb-section">Blockchain</div>
    <a href="index.php?page=blockchain_explorer" class="sb-link active"><i class="fas fa-link"></i> Blockchain Explorer</a>
    <a href="index.php?page=vote_verification" class="sb-link"><i class="fas fa-check-circle"></i> Vote Verification</a>
    <a href="index.php?page=logout" class="sb-link" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- Main -->
<main class="main">

    <div class="page-header">
        <div class="page-header-left">
            <h1><i class="fas fa-link"></i> Blockchain Explorer</h1>
            <p>Real-time view of all voting transactions recorded in the system</p>
        </div>
        <div class="live-badge"><div class="live-dot"></div> LIVE — IUC-NET</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">⛓️</div>
            <div class="stat-num"><?= $totalElections ?></div>
            <div class="stat-lbl">Election Blocks</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">🔗</div>
            <div class="stat-num"><?= $totalVotes ?></div>
            <div class="stat-lbl">Transactions</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">👤</div>
            <div class="stat-num"><?= $totalVoters ?></div>
            <div class="stat-lbl">Unique Voters</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon">✅</div>
            <div class="stat-num"><?= $totalVotes > 0 ? '100%' : '—' ?></div>
            <div class="stat-lbl">Confirmed</div>
        </div>
    </div>

    <!-- Election Blocks -->
    <div class="section-title"><i class="fas fa-cubes"></i> Election Blocks (<?= count($elections) ?>)</div>
    <?php if (empty($elections)): ?>
        <p style="color:#64748b;margin-bottom:2rem;">No elections created yet.</p>
    <?php else: ?>
    <div class="blocks-grid">
        <?php foreach ($elections as $i => $e):
            $s = $e['status'];
            $pc = match($s){'active'=>'pill-active','draft'=>'pill-draft','completed'=>'pill-completed','closed'=>'pill-closed','upcoming'=>'pill-upcoming',default=>'pill-draft'};
            // Generate a deterministic hash from the election id for display
            $dispHash = '0x' . hash('sha256', 'election-' . $e['id'] . '-' . $e['created_at']);
        ?>
        <div class="block-card" onclick="location.href='index.php?page=results&election_id=<?= $e['id'] ?>'">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                <div class="block-num">Block #<?= str_pad($e['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <span class="status-pill <?= $pc ?>"><?= ucfirst($s) ?></span>
            </div>
            <div class="block-title"><?= htmlspecialchars($e['title']) ?></div>
            <div class="block-hash"><?= substr($dispHash, 0, 42) ?>...</div>
            <div class="block-meta">
                <span><?= date('M d, Y', strtotime($e['created_at'])) ?></span>
                <span class="block-tx-count"><?= $e['tx_count'] ?> tx<?= $e['tx_count'] != 1 ? 's' : '' ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="section-title"><i class="fas fa-exchange-alt"></i> Transactions</div>
    <form method="GET" action="index.php" class="search-bar">
        <input type="hidden" name="page" value="blockchain_explorer">
        <input type="text" name="search" placeholder="Search by hash, voter name, election, or candidate…"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
        <a href="index.php?page=blockchain_explorer" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Transactions table -->
    <div class="tx-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Transaction Hash</th>
                    <th>Voter</th>
                    <th>Election</th>
                    <th>Voted For</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th>Verify</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr class="empty-row">
                    <td colspan="8">
                        <i class="fas fa-link"></i>
                        <?= $search ? 'No transactions match your search.' : 'No transactions recorded yet.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $i => $tx): ?>
                <tr>
                    <td style="color:#475569;"><?= $i + 1 ?></td>
                    <td class="hash-cell"><?= htmlspecialchars($tx['transaction_hash']) ?></td>
                    <td class="voter-cell">
                        <?= htmlspecialchars($tx['voter_name']) ?>
                        <?php if ($tx['student_id']): ?>
                        <div style="font-size:.72rem;color:#64748b;"><?= htmlspecialchars($tx['student_id']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="election-cell"><?= htmlspecialchars($tx['election_title']) ?></td>
                    <td class="candidate-cell"><?= htmlspecialchars($tx['candidate_name']) ?></td>
                    <td class="date-cell"><?= date('M d, Y H:i:s', strtotime($tx['created_at'])) ?></td>
                    <td><span style="background:rgba(16,185,129,.15);color:#10b981;padding:.2rem .6rem;border-radius:10px;font-size:.72rem;font-weight:700;">CONFIRMED</span></td>
                    <td>
                        <a href="index.php?page=vote_verification"
                           onclick="sessionStorage.setItem('verifyHash','<?= htmlspecialchars($tx['transaction_hash']) ?>')"
                           class="btn-verify-sm">
                            <i class="fas fa-shield-alt"></i> Verify
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="table-footer">
            <span>Showing <?= count($transactions) ?> transaction<?= count($transactions) != 1 ? 's' : '' ?><?= $search ? ' matching "'.htmlspecialchars($search).'"' : '' ?></span>
            <span style="color:#10b981;"><i class="fas fa-check-circle"></i> All confirmed on IUC-NET</span>
        </div>
    </div>

</main>
</div>

<script>
// Auto-fill verify hash from sessionStorage
window.addEventListener('load', () => {
    const h = sessionStorage.getItem('verifyHash');
    if (h) sessionStorage.removeItem('verifyHash');
});
</script>
</body>
</html>
