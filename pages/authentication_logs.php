<?php
/**
 * IUC Voting System - Authentication Logs (Dynamic)
 */

$search   = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$perPage  = 25;
$page     = max(1, (int)($_GET['p'] ?? 1));
$offset   = ($page - 1) * $perPage;

// ── Stats ──────────────────────────────────────────────────────────────────
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$approvedUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='approved'")->fetchColumn();
$pendingUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$rejectedUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='rejected'")->fetchColumn();
$totalVotes    = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$todayVotes    = (int)$pdo->query("SELECT COUNT(*) FROM votes WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// ── Activity log entries ───────────────────────────────────────────────────
$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (al.action LIKE ? OR al.details LIKE ? OR u.name LIKE ? OR al.ip_address LIKE ?)";
    $like = "%$search%";
    $params = [$like,$like,$like,$like];
}
if ($typeFilter !== '') {
    $where .= " AND al.action = ?";
    $params[] = $typeFilter;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id $where");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalLogs / $perPage));

$stmt = $pdo->prepare("
    SELECT al.*, u.name AS user_name, u.type AS user_type, u.email AS user_email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $where
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct action types for filter
$actionTypes = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// ── Recent user registrations (acts as auth events) ────────────────────────
$recentUsers = $pdo->query("
    SELECT u.id, u.name, u.email, u.type, u.status, u.created_at,
           s.student_id, s.department
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    ORDER BY u.created_at DESC LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// ── Recent votes (voting auth events) ─────────────────────────────────────
$recentVotes = $pdo->query("
    SELECT v.created_at, v.transaction_hash,
           u.name AS voter, u.email,
           e.title AS election, c.name AS candidate
    FROM votes v
    JOIN users u ON v.user_id=u.id
    JOIN elections e ON v.election_id=e.id
    JOIN candidates c ON v.candidate_id=c.id
    ORDER BY v.created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authentication Logs - IUC Voting System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;color:#1e293b}
.layout{display:flex;min-height:100vh}
.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#1a5276 60%,#3282B8);color:#fff;position:fixed;height:100vh;overflow-y:auto;z-index:100}
.sb-brand{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}
.sb-logo{width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.sb-brand h1{font-size:.95rem;font-weight:700}.sb-brand p{font-size:.72rem;opacity:.65;margin-top:.1rem}
.sb-sec{font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;opacity:.5;padding:.8rem 1.5rem .2rem;font-weight:600}
.sb-a{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.5rem;color:rgba(255,255,255,.75);text-decoration:none;font-size:.85rem;transition:all .2s;border-left:3px solid transparent}
.sb-a:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}
.sb-a.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}
.sb-a i{width:16px;text-align:center}
.main{flex:1;margin-left:260px;padding:2rem}
.page-hdr{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
.page-hdr h1{font-size:1.5rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}
.page-hdr p{color:#64748b;font-size:.875rem;margin-top:.25rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem}
.sc{background:#fff;border-radius:12px;padding:1rem 1.1rem;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:.8rem;border-left:4px solid #3282B8}
.sc.g{border-left-color:#10b981}.sc.o{border-left-color:#f59e0b}.sc.r{border-left-color:#ef4444}.sc.p{border-left-color:#8b5cf6}
.sc-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;flex-shrink:0}
.sc.g .sc-icon{background:linear-gradient(135deg,#10b981,#059669)}
.sc.o .sc-icon{background:linear-gradient(135deg,#f59e0b,#d97706)}
.sc.r .sc-icon{background:linear-gradient(135deg,#ef4444,#dc2626)}
.sc.p .sc-icon{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}
.sc-num{font-size:1.4rem;font-weight:800;color:#1e293b;line-height:1}
.sc-lbl{font-size:.7rem;color:#64748b;margin-top:.1rem}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:space-between;gap:.5rem}
.card-head-l{display:flex;align-items:center;gap:.5rem}
.filter-bar{background:#fff;border-radius:12px;padding:1rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end}
.fg{display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:140px}
.fg label{font-size:.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.fg input,.fg select{padding:.55rem .85rem;border:2px solid #e2e8f0;border-radius:8px;font-size:.83rem;color:#1e293b;background:#fff}
.fg input:focus,.fg select:focus{outline:none;border-color:#3282B8}
.btn{padding:.55rem 1rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;font-size:.82rem;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-p:hover{transform:translateY(-1px)}
.btn-o{background:#fff;border:2px solid #e2e8f0;color:#64748b}
.btn-o:hover{border-color:#3282B8;color:#3282B8}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap}
td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}
tr:hover td{background:#f8fafc}
.badge{padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.b-approved,.b-success{background:#dcfce7;color:#166534}
.b-pending,.b-warning{background:#fef9c3;color:#854d0e}
.b-rejected,.b-error{background:#fee2e2;color:#991b1b}
.b-admin{background:#e0e7ff;color:#3730a3}
.b-student{background:#dbeafe;color:#1e40af}
.empty{text-align:center;padding:2.5rem;color:#94a3b8}
.empty i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
.tbl-foot{padding:.7rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem}
.pagination{display:flex;gap:.4rem}
.pg-btn{padding:.3rem .65rem;border:2px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;font-size:.75rem;font-weight:600;text-decoration:none;transition:all .2s}
.pg-btn:hover{border-color:#3282B8;color:#3282B8}
.pg-btn.active{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;border-color:transparent}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:900px){.grid-2{grid-template-columns:1fr}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-fingerprint"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">Security</div>
    <a href="index.php?page=security_center" class="sb-a"><i class="fas fa-shield-alt"></i> Security Center</a>
    <a href="index.php?page=authentication_logs" class="sb-a active"><i class="fas fa-fingerprint"></i> Auth Logs</a>
    <a href="index.php?page=access_control" class="sb-a"><i class="fas fa-lock"></i> Access Control</a>
    <div class="sb-sec">Voters</div>
    <a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <div class="page-hdr">
        <div><h1><i class="fas fa-fingerprint" style="color:#3282B8"></i> Authentication Logs</h1><p>User registrations, voting events, and system activity</p></div>
    </div>

    <div class="stats-grid">
        <div class="sc"><div class="sc-icon"><i class="fas fa-users"></i></div><div><div class="sc-num"><?= $totalUsers ?></div><div class="sc-lbl">Total Users</div></div></div>
        <div class="sc g"><div class="sc-icon"><i class="fas fa-user-check"></i></div><div><div class="sc-num"><?= $approvedUsers ?></div><div class="sc-lbl">Approved</div></div></div>
        <div class="sc o"><div class="sc-icon"><i class="fas fa-clock"></i></div><div><div class="sc-num"><?= $pendingUsers ?></div><div class="sc-lbl">Pending</div></div></div>
        <div class="sc r"><div class="sc-icon"><i class="fas fa-user-times"></i></div><div><div class="sc-num"><?= $rejectedUsers ?></div><div class="sc-lbl">Rejected</div></div></div>
        <div class="sc p"><div class="sc-icon"><i class="fas fa-vote-yea"></i></div><div><div class="sc-num"><?= $totalVotes ?></div><div class="sc-lbl">Total Votes</div></div></div>
        <div class="sc g"><div class="sc-icon"><i class="fas fa-calendar-day"></i></div><div><div class="sc-num"><?= $todayVotes ?></div><div class="sc-lbl">Votes Today</div></div></div>
    </div>

    <div class="grid-2">
        <!-- Recent registrations -->
        <div class="card">
            <div class="card-head"><div class="card-head-l"><i class="fas fa-user-plus" style="color:#10b981"></i> Recent Registrations</div><span style="font-size:.75rem;color:#94a3b8;">Last 20</span></div>
            <?php if (empty($recentUsers)): ?>
                <div class="empty"><i class="fas fa-users"></i>No users registered yet.</div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Registered</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                    <td style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge b-<?= $u['type'] ?>"><?= ucfirst($u['type']) ?></span></td>
                    <td><span class="badge b-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                    <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y H:i', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent voting events -->
        <div class="card">
            <div class="card-head"><div class="card-head-l"><i class="fas fa-vote-yea" style="color:#3282B8"></i> Recent Voting Events</div><span style="font-size:.75rem;color:#94a3b8;">Last 10</span></div>
            <?php if (empty($recentVotes)): ?>
                <div class="empty"><i class="fas fa-vote-yea"></i>No votes cast yet.</div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Voter</th><th>Election</th><th>Candidate</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($recentVotes as $v): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($v['voter']) ?></td>
                    <td style="color:#3282B8;font-size:.78rem;"><?= htmlspecialchars($v['election']) ?></td>
                    <td style="color:#8b5cf6;"><?= htmlspecialchars($v['candidate']) ?></td>
                    <td style="color:#64748b;white-space:nowrap;"><?= date('M d H:i', strtotime($v['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activity log -->
    <div class="card">
        <div class="card-head"><div class="card-head-l"><i class="fas fa-history" style="color:#3282B8"></i> Activity Log</div><span style="font-size:.75rem;color:#94a3b8;"><?= $totalLogs ?> entries</span></div>
        <?php if (empty($logs)): ?>
            <div class="empty"><i class="fas fa-history"></i>No activity logged yet. Events are recorded as users interact with the system.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $i => $l): ?>
            <tr>
                <td style="color:#94a3b8;"><?= $offset+$i+1 ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($l['user_name'] ?? 'System') ?></td>
                <td><span class="badge b-success"><?= htmlspecialchars($l['action']) ?></span></td>
                <td style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($l['details'] ?? '—') ?></td>
                <td style="font-family:monospace;font-size:.75rem;color:#64748b;"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></td>
                <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="tbl-foot">
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?page=authentication_logs&p=<?= $page-1 ?>" class="pg-btn">&laquo;</a><?php endif; ?>
                <?php for ($pg=max(1,$page-2); $pg<=min($totalPages,$page+2); $pg++): ?>
                <a href="?page=authentication_logs&p=<?= $pg ?>" class="pg-btn <?= $pg===$page?'active':'' ?>"><?= $pg ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="?page=authentication_logs&p=<?= $page+1 ?>" class="pg-btn">&raquo;</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>
</body>
</html>
