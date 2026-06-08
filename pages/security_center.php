<?php

/**

 * IUC Voting System - Security Center (Dynamic)

 */



// ── Real security data ─────────────────────────────────────────────────────

$totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$totalStudents  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student'")->fetchColumn();

$totalAdmins    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='admin'")->fetchColumn();

$pendingUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

$rejectedUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='rejected'")->fetchColumn();

$totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();

$totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();

$totalTx        = (int)$pdo->query("SELECT COUNT(*) FROM blockchain_transactions")->fetchColumn();



// Duplicate vote attempts (same user, same election — should be 0 due to unique constraint)

$dupAttempts = (int)$pdo->query("

    SELECT COUNT(*) FROM (

        SELECT user_id, election_id, COUNT(*) as cnt

        FROM votes GROUP BY user_id, election_id HAVING cnt > 1

    ) t

")->fetchColumn();



// Users registered per day (last 7 days)

$regTrend = $pdo->query("

    SELECT DATE_FORMAT(created_at,'%b %d') AS day, COUNT(*) AS cnt

    FROM users

    WHERE created_at >= NOW() - INTERVAL 7 DAY

    GROUP BY DATE(created_at)

    ORDER BY DATE(created_at) ASC

")->fetchAll(PDO::FETCH_ASSOC);



// Votes per day (last 7 days)

$voteTrend = $pdo->query("

    SELECT DATE_FORMAT(created_at,'%b %d') AS day, COUNT(*) AS cnt

    FROM votes

    WHERE created_at >= NOW() - INTERVAL 7 DAY

    GROUP BY DATE(created_at)

    ORDER BY DATE(created_at) ASC

")->fetchAll(PDO::FETCH_ASSOC);



// Recent activity log

$recentActivity = $pdo->query("

    SELECT al.action, al.details, al.ip_address, al.created_at, u.name AS user_name

    FROM activity_logs al

    LEFT JOIN users u ON al.user_id = u.id

    ORDER BY al.created_at DESC LIMIT 20

")->fetchAll(PDO::FETCH_ASSOC);



// User status breakdown

$userStatuses = $pdo->query("

    SELECT status, COUNT(*) AS cnt FROM users GROUP BY status

")->fetchAll(PDO::FETCH_ASSOC);



// Elections by status

$elecStatuses = $pdo->query("

    SELECT status, COUNT(*) AS cnt FROM elections GROUP BY status

")->fetchAll(PDO::FETCH_ASSOC);



// PHP & server security checks

$secChecks = [

    'display_errors OFF'    => ini_get('display_errors') == '0',

    'HTTPS available'       => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',

    'Session started'       => session_status() === PHP_SESSION_ACTIVE,

    'PDO extension loaded'  => extension_loaded('pdo_mysql'),

    'OpenSSL loaded'        => extension_loaded('openssl'),

    'DB connection OK'      => $pdo !== null,

];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Security Center - IUC Voting System</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<script src="assets/js/chart.umd.min.js"></script>

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

.secure-badge{display:inline-flex;align-items:center;gap:.4rem;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:600}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}

.sc{background:#fff;border-radius:12px;padding:1.1rem 1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:.9rem;border-left:4px solid #3282B8}

.sc.g{border-left-color:#10b981}.sc.o{border-left-color:#f59e0b}.sc.r{border-left-color:#ef4444}.sc.p{border-left-color:#8b5cf6}

.sc-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;flex-shrink:0}

.sc.g .sc-icon{background:linear-gradient(135deg,#10b981,#059669)}

.sc.o .sc-icon{background:linear-gradient(135deg,#f59e0b,#d97706)}

.sc.r .sc-icon{background:linear-gradient(135deg,#ef4444,#dc2626)}

.sc.p .sc-icon{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}

.sc-num{font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1}

.sc-lbl{font-size:.72rem;color:#64748b;margin-top:.15rem}

.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}

@media(max-width:900px){.grid-2{grid-template-columns:1fr}}

.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}

.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}

.card-body{padding:1.25rem 1.5rem}

.check-item{display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f8fafc;font-size:.85rem}

.check-item:last-child{border-bottom:none}

.badge-ok{background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:8px;font-size:.72rem;font-weight:700}

.badge-warn{background:#fef9c3;color:#854d0e;padding:.2rem .6rem;border-radius:8px;font-size:.72rem;font-weight:700}

.badge-err{background:#fee2e2;color:#991b1b;padding:.2rem .6rem;border-radius:8px;font-size:.72rem;font-weight:700}

.activity-item{display:flex;align-items:flex-start;gap:.75rem;padding:.55rem 0;border-bottom:1px solid #f8fafc;font-size:.82rem}

.activity-item:last-child{border-bottom:none}

.act-dot{width:8px;height:8px;background:#3282B8;border-radius:50%;margin-top:.3rem;flex-shrink:0}

.act-text{flex:1;color:#374151}

.act-time{font-size:.72rem;color:#94a3b8;white-space:nowrap}

.empty-act{text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem}

.empty-act i{font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.3}

@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-shield-alt"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>

    <div class="sb-sec">Main</div>

    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

    <div class="sb-sec">Elections</div>

    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>

    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>

    <div class="sb-sec">Security</div>

    <a href="index.php?page=security_center" class="sb-a active"><i class="fas fa-shield-alt"></i> Security Center</a>

    <a href="index.php?page=authentication_logs" class="sb-a"><i class="fas fa-fingerprint"></i> Auth Logs</a>

    <a href="index.php?page=access_control" class="sb-a"><i class="fas fa-lock"></i> Access Control</a>

    <div class="sb-sec">Voters</div>

    <a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>

    <a href="index.php?page=vote_verification" class="sb-a"><i class="fas fa-check-circle"></i> Vote Verification</a>

    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>

</aside>

<main class="main">

    <div class="page-hdr">

        <div><h1><i class="fas fa-shield-alt" style="color:#3282B8"></i> Security Center</h1><p>System security overview and integrity checks</p></div>

        <div class="secure-badge"><i class="fas fa-check-circle"></i> System Secure</div>

    </div>



    <div class="stats-grid">

        <div class="sc"><div class="sc-icon"><i class="fas fa-users"></i></div><div><div class="sc-num"><?= $totalUsers ?></div><div class="sc-lbl">Total Users</div></div></div>

        <div class="sc g"><div class="sc-icon"><i class="fas fa-user-check"></i></div><div><div class="sc-num"><?= $totalStudents ?></div><div class="sc-lbl">Students</div></div></div>

        <div class="sc p"><div class="sc-icon"><i class="fas fa-user-shield"></i></div><div><div class="sc-num"><?= $totalAdmins ?></div><div class="sc-lbl">Admins</div></div></div>

        <div class="sc o"><div class="sc-icon"><i class="fas fa-clock"></i></div><div><div class="sc-num"><?= $pendingUsers ?></div><div class="sc-lbl">Pending Approval</div></div></div>

        <div class="sc r"><div class="sc-icon"><i class="fas fa-vote-yea"></i></div><div><div class="sc-num"><?= $totalVotes ?></div><div class="sc-lbl">Votes Cast</div></div></div>

        <div class="sc <?= $dupAttempts > 0 ? 'r' : 'g' ?>"><div class="sc-icon"><i class="fas fa-<?= $dupAttempts > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i></div><div><div class="sc-num"><?= $dupAttempts ?></div><div class="sc-lbl">Duplicate Votes</div></div></div>

    </div>



    <div class="grid-2">

        <!-- Security checks -->

        <div class="card">

            <div class="card-head"><i class="fas fa-tasks" style="color:#3282B8"></i> Security Checks</div>

            <div class="card-body">

                <?php foreach ($secChecks as $label => $pass): ?>

                <div class="check-item">

                    <span><?= $label ?></span>

                    <span class="badge-<?= $pass ? 'ok' : 'warn' ?>"><?= $pass ? '✓ PASS' : '⚠ CHECK' ?></span>

                </div>

                <?php endforeach; ?>

                <div class="check-item">

                    <span>Duplicate votes in DB</span>

                    <span class="badge-<?= $dupAttempts === 0 ? 'ok' : 'err' ?>"><?= $dupAttempts === 0 ? '✓ NONE' : "⚠ $dupAttempts FOUND" ?></span>

                </div>

            </div>

        </div>



        <!-- User status breakdown -->

        <div class="card">

            <div class="card-head"><i class="fas fa-chart-pie" style="color:#8b5cf6"></i> User Status Breakdown</div>

            <div class="card-body" style="display:flex;justify-content:center;">

                <canvas id="userPie" style="max-height:200px;max-width:260px;"></canvas>

            </div>

        </div>

    </div>



    <!-- Trends -->

    <div class="grid-2">

        <div class="card">

            <div class="card-head"><i class="fas fa-chart-line" style="color:#10b981"></i> Registrations (Last 7 Days)</div>

            <div class="card-body">

                <?php if (empty($regTrend)): ?>

                    <div class="empty-act"><i class="fas fa-chart-line"></i>No registrations in last 7 days.</div>

                <?php else: ?>

                    <canvas id="regChart" style="max-height:180px;"></canvas>

                <?php endif; ?>

            </div>

        </div>

        <div class="card">

            <div class="card-head"><i class="fas fa-chart-bar" style="color:#3282B8"></i> Votes (Last 7 Days)</div>

            <div class="card-body">

                <?php if (empty($voteTrend)): ?>

                    <div class="empty-act"><i class="fas fa-chart-bar"></i>No votes in last 7 days.</div>

                <?php else: ?>

                    <canvas id="voteChart" style="max-height:180px;"></canvas>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- Recent activity -->

    <div class="card">

        <div class="card-head"><i class="fas fa-history" style="color:#3282B8"></i> Recent Activity Log</div>

        <div class="card-body">

            <?php if (empty($recentActivity)): ?>

                <div class="empty-act"><i class="fas fa-history"></i>No activity logged yet. Activity is recorded as users interact with the system.</div>

            <?php else: ?>

                <?php foreach ($recentActivity as $a): ?>

                <div class="activity-item">

                    <div class="act-dot"></div>

                    <div class="act-text">

                        <strong><?= htmlspecialchars($a['user_name'] ?? 'System') ?></strong>

                        — <?= htmlspecialchars($a['action']) ?>

                        <?php if ($a['details']): ?><span style="color:#64748b;"> · <?= htmlspecialchars($a['details']) ?></span><?php endif; ?>

                        <?php if ($a['ip_address']): ?><span style="color:#94a3b8;font-size:.75rem;"> [<?= htmlspecialchars($a['ip_address']) ?>]</span><?php endif; ?>

                    </div>

                    <div class="act-time"><?= date('M d H:i', strtotime($a['created_at'])) ?></div>

                </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</main>

</div>

<script>

window.addEventListener('load', function() {

    if (typeof Chart === 'undefined') return;

    <?php if (!empty($userStatuses)): ?>

    new Chart(document.getElementById('userPie'), {

        type: 'doughnut',

        data:{

            labels: <?= json_encode(array_column($userStatuses,'status')) ?>,

            datasets:[{data:<?= json_encode(array_map('intval',array_column($userStatuses,'cnt'))) ?>,backgroundColor:['#10b981','#f59e0b','#ef4444','#3282B8'],borderWidth:3,borderColor:'#fff'}]

        },

        options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:10,usePointStyle:true}}},cutout:'55%'}

    });

    <?php endif; ?>

    <?php if (!empty($regTrend)): ?>

    new Chart(document.getElementById('regChart'), {

        type:'bar',

        data:{labels:<?= json_encode(array_column($regTrend,'day')) ?>,datasets:[{label:'Registrations',data:<?= json_encode(array_map('intval',array_column($regTrend,'cnt'))) ?>,backgroundColor:'rgba(139,92,246,.7)',borderColor:'#8b5cf6',borderWidth:2,borderRadius:5}]},

        options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}

    });

    <?php endif; ?>

    <?php if (!empty($voteTrend)): ?>

    new Chart(document.getElementById('voteChart'), {

        type:'bar',

        data:{labels:<?= json_encode(array_column($voteTrend,'day')) ?>,datasets:[{label:'Votes',data:<?= json_encode(array_map('intval',array_column($voteTrend,'cnt'))) ?>,backgroundColor:'rgba(50,130,184,.7)',borderColor:'#3282B8',borderWidth:2,borderRadius:5}]},

        options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}

    });

    <?php endif; ?>

});

</script>

</body>

</html>

