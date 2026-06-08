<?php

/**

 * IUC Voting System - Transaction Monitor (Dynamic)

 * All data from real database

 */



// ── Filters ────────────────────────────────────────────────────────────────

$search     = trim($_GET['search'] ?? '');

$typeFilter = trim($_GET['type'] ?? '');

$perPage    = 20;

$page       = max(1, (int)($_GET['p'] ?? 1));

$offset     = ($page - 1) * $perPage;



// ── Stats ──────────────────────────────────────────────────────────────────

$totalTx      = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();

$totalVoters  = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM votes")->fetchColumn();

$totalElec    = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();

$todayTx      = (int)$pdo->query("SELECT COUNT(*) FROM votes WHERE DATE(created_at) = CURDATE()")->fetchColumn();



// ── Hourly activity (last 24h) ─────────────────────────────────────────────

$hourly = $pdo->query("

    SELECT DATE_FORMAT(created_at,'%H:00') AS hr, COUNT(*) AS cnt

    FROM votes

    WHERE created_at >= NOW() - INTERVAL 24 HOUR

    GROUP BY DATE_FORMAT(created_at,'%Y-%m-%d %H')

    ORDER BY created_at ASC

")->fetchAll(PDO::FETCH_ASSOC);



if (empty($hourly)) {

    $hourly = $pdo->query("

        SELECT DATE_FORMAT(created_at,'%b %d') AS hr, COUNT(*) AS cnt

        FROM votes GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC

    ")->fetchAll(PDO::FETCH_ASSOC);

}



// ── Build main query ───────────────────────────────────────────────────────

$where  = '';

$params = [];



if ($search !== '') {

    $where .= " AND (v.transaction_hash LIKE ? OR u.name LIKE ? OR e.title LIKE ? OR c.name LIKE ?)";

    $like = "%$search%";

    $params = [$like,$like,$like,$like];

}



$countStmt = $pdo->prepare("

    SELECT COUNT(*) FROM votes v

    JOIN users u ON v.user_id = u.id

    JOIN elections e ON v.election_id = e.id

    JOIN candidates c ON v.candidate_id = c.id

    WHERE 1=1 $where

");

$countStmt->execute($params);

$totalFiltered = (int)$countStmt->fetchColumn();

$totalPages = max(1, ceil($totalFiltered / $perPage));



$stmt = $pdo->prepare("

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

    WHERE 1=1 $where

    ORDER BY v.created_at DESC

    LIMIT $perPage OFFSET $offset

");

$stmt->execute($params);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);



// ── Election breakdown ─────────────────────────────────────────────────────

$elecBreakdown = $pdo->query("

    SELECT e.title, COUNT(v.id) AS tx_count

    FROM elections e

    LEFT JOIN votes v ON v.election_id = e.id

    GROUP BY e.id

    ORDER BY tx_count DESC

")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Transaction Monitor - IUC Voting System</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<script src="assets/js/chart.umd.min.js"></script>

<style>

*{margin:0;padding:0;box-sizing:border-box}

body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;color:#1e293b}

.layout{display:flex;min-height:100vh}

.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#1a5276 60%,#3282B8);color:#fff;position:fixed;height:100vh;overflow-y:auto;z-index:100}

.sb-brand{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}

.sb-logo{width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}

.sb-brand h1{font-size:.95rem;font-weight:700}

.sb-brand p{font-size:.72rem;opacity:.65;margin-top:.1rem}

.sb-sec{font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;opacity:.5;padding:.8rem 1.5rem .2rem;font-weight:600}

.sb-a{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.5rem;color:rgba(255,255,255,.75);text-decoration:none;font-size:.85rem;transition:all .2s;border-left:3px solid transparent}

.sb-a:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}

.sb-a.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}

.sb-a i{width:16px;text-align:center}

.main{flex:1;margin-left:260px;padding:2rem}

.page-hdr{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}

.page-hdr h1{font-size:1.5rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}

.page-hdr p{color:#64748b;font-size:.875rem;margin-top:.25rem}

.live-badge{display:inline-flex;align-items:center;gap:.4rem;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:600}

.live-dot{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 1.5s infinite}

@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}

.sc{background:#fff;border-radius:12px;padding:1.1rem 1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:.9rem;border-left:4px solid #3282B8}

.sc.g{border-left-color:#10b981}.sc.o{border-left-color:#f59e0b}.sc.p{border-left-color:#8b5cf6}

.sc-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;flex-shrink:0}

.sc.g .sc-icon{background:linear-gradient(135deg,#10b981,#059669)}

.sc.o .sc-icon{background:linear-gradient(135deg,#f59e0b,#d97706)}

.sc.p .sc-icon{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}

.sc-num{font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1}

.sc-lbl{font-size:.72rem;color:#64748b;margin-top:.15rem}

.charts-row{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem}

@media(max-width:900px){.charts-row{grid-template-columns:1fr}}

.cc{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}

.cc-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}

.cc-body{padding:1.25rem}

.filter-bar{background:#fff;border-radius:12px;padding:1.1rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end}

.fg{display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:150px}

.fg label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}

.fg input,.fg select{padding:.6rem .9rem;border:2px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#1e293b;background:#fff}

.fg input:focus,.fg select:focus{outline:none;border-color:#3282B8}

.btn{padding:.6rem 1.1rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;font-size:.83rem;text-decoration:none;transition:all .2s;white-space:nowrap}

.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}

.btn-p:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(50,130,184,.3)}

.btn-o{background:#fff;border:2px solid #e2e8f0;color:#64748b}

.btn-o:hover{border-color:#3282B8;color:#3282B8}

.tbl-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}

.tbl-hdr{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem}

.tbl-hdr h3{font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}

table{width:100%;border-collapse:collapse;font-size:.82rem}

th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap}

td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}

tr:hover td{background:#f8fafc}

.hash-cell{font-family:'Courier New',monospace;color:#3282B8;font-size:.75rem;word-break:break-all}

.confirmed{background:#dcfce7;color:#166534;padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700}

.btn-verify-sm{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:.25rem .6rem;border-radius:6px;font-size:.72rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;transition:all .2s}

.btn-verify-sm:hover{background:#166534;color:#fff}

.empty{text-align:center;padding:3rem;color:#94a3b8}

.empty i{font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3}

.tbl-foot{padding:.7rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem}

.pagination{display:flex;gap:.4rem;align-items:center}

.pg-btn{padding:.3rem .7rem;border:2px solid #e2e8f0;border-radius:6px;background:#fff;color:#64748b;font-size:.78rem;font-weight:600;text-decoration:none;transition:all .2s}

.pg-btn:hover{border-color:#3282B8;color:#3282B8}

.pg-btn.active{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;border-color:transparent}

@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

    <div class="sb-brand">

        <div class="sb-logo"><i class="fas fa-exchange-alt"></i></div>

        <div><h1>IUC Voting</h1><p>Admin Panel</p></div>

    </div>

    <div class="sb-sec">Main</div>

    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

    <div class="sb-sec">Elections</div>

    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>

    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>

    <div class="sb-sec">Analytics</div>

    <a href="index.php?page=realtime_voting" class="sb-a"><i class="fas fa-chart-line"></i> Real-time Voting</a>

    <a href="index.php?page=participants" class="sb-a"><i class="fas fa-users"></i> Participants</a>

    <div class="sb-sec">Blockchain</div>

    <a href="index.php?page=blockchain_explorer" class="sb-a"><i class="fas fa-link"></i> Blockchain Explorer</a>

    <a href="index.php?page=transaction_monitor" class="sb-a active"><i class="fas fa-exchange-alt"></i> Transaction Monitor</a>

    <a href="index.php?page=vote_verification" class="sb-a"><i class="fas fa-check-circle"></i> Vote Verification</a>

    <div class="sb-sec">Voters</div>

    <a href="index.php?page=voter_registration" class="sb-a"><i class="fas fa-user-plus"></i> Register Voters</a>

    <a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>

    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>

</aside>



<main class="main">



    <div class="page-hdr">

        <div>

            <h1><i class="fas fa-exchange-alt" style="color:#3282B8"></i> Transaction Monitor</h1>

            <p>Live monitoring of all voting transactions — auto-refreshes every 30s</p>

        </div>

        <div class="live-badge"><div class="live-dot"></div> LIVE</div>

    </div>



    <!-- Stats -->

    <div class="stats-grid">

        <div class="sc">

            <div class="sc-icon"><i class="fas fa-exchange-alt"></i></div>

            <div><div class="sc-num"><?= number_format($totalTx) ?></div><div class="sc-lbl">Total Transactions</div></div>

        </div>

        <div class="sc g">

            <div class="sc-icon"><i class="fas fa-calendar-day"></i></div>

            <div><div class="sc-num"><?= $todayTx ?></div><div class="sc-lbl">Today</div></div>

        </div>

        <div class="sc o">

            <div class="sc-icon"><i class="fas fa-users"></i></div>

            <div><div class="sc-num"><?= $totalVoters ?></div><div class="sc-lbl">Unique Voters</div></div>

        </div>

        <div class="sc p">

            <div class="sc-icon"><i class="fas fa-vote-yea"></i></div>

            <div><div class="sc-num"><?= $totalElec ?></div><div class="sc-lbl">Elections</div></div>

        </div>

    </div>



    <!-- Charts -->

    <div class="charts-row">

        <div class="cc">

            <div class="cc-head"><i class="fas fa-chart-line" style="color:#3282B8"></i>

                <?= empty($hourly) || count($hourly) <= 1 ? 'Votes Per Day (All Time)' : 'Votes Per Hour (Last 24h)' ?>

            </div>

            <div class="cc-body">

                <?php if (empty($hourly)): ?>

                    <div class="empty"><i class="fas fa-chart-line"></i>No transactions yet.</div>

                <?php else: ?>

                    <canvas id="lineChart" style="max-height:200px;"></canvas>

                <?php endif; ?>

            </div>

        </div>

        <div class="cc">

            <div class="cc-head"><i class="fas fa-chart-pie" style="color:#8b5cf6"></i> By Election</div>

            <div class="cc-body" style="display:flex;justify-content:center;">

                <?php if (empty($elecBreakdown) || array_sum(array_column($elecBreakdown,'tx_count')) == 0): ?>

                    <div class="empty"><i class="fas fa-chart-pie"></i>No data.</div>

                <?php else: ?>

                    <canvas id="pieChart" style="max-height:200px;max-width:260px;"></canvas>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- Filters -->

    <form method="GET" action="index.php" class="filter-bar">

        <input type="hidden" name="page" value="transaction_monitor">

        <div class="fg">

            <label>Search</label>

            <input type="text" name="search" placeholder="Hash, voter, election or candidate…" value="<?= htmlspecialchars($search) ?>">

        </div>

        <button type="submit" class="btn btn-p"><i class="fas fa-search"></i> Search</button>

        <?php if ($search): ?>

        <a href="index.php?page=transaction_monitor" class="btn btn-o"><i class="fas fa-times"></i> Clear</a>

        <?php endif; ?>

    </form>



    <!-- Transactions table -->

    <div class="tbl-card">

        <div class="tbl-hdr">

            <h3><i class="fas fa-list" style="color:#3282B8"></i> Transactions</h3>

            <span style="font-size:.78rem;color:#64748b;"><?= number_format($totalFiltered) ?> total<?= $search ? ' matching' : '' ?></span>

        </div>

        <?php if (empty($transactions)): ?>

            <div class="empty"><i class="fas fa-exchange-alt"></i><?= $search ? 'No transactions match your search.' : 'No transactions recorded yet.' ?></div>

        <?php else: ?>

        <div style="overflow-x:auto;">

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

                <?php foreach ($transactions as $i => $tx): ?>

                <tr>

                    <td style="color:#94a3b8;"><?= $offset + $i + 1 ?></td>

                    <td class="hash-cell"><?= htmlspecialchars($tx['transaction_hash']) ?></td>

                    <td>

                        <div style="font-weight:600;"><?= htmlspecialchars($tx['voter_name']) ?></div>

                        <?php if ($tx['student_id']): ?>

                        <div style="font-size:.72rem;color:#94a3b8;"><?= htmlspecialchars($tx['student_id']) ?></div>

                        <?php endif; ?>

                    </td>

                    <td style="color:#3282B8;"><?= htmlspecialchars($tx['election_title']) ?></td>

                    <td style="color:#8b5cf6;"><?= htmlspecialchars($tx['candidate_name']) ?></td>

                    <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y H:i:s', strtotime($tx['created_at'])) ?></td>

                    <td><span class="confirmed">CONFIRMED</span></td>

                    <td>

                        <a href="index.php?page=vote_verification"

                           onclick="sessionStorage.setItem('verifyHash','<?= htmlspecialchars($tx['transaction_hash']) ?>')"

                           class="btn-verify-sm">

                            <i class="fas fa-shield-alt"></i> Verify

                        </a>

                    </td>

                </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="tbl-foot">

            <span>Page <?= $page ?> of <?= $totalPages ?> &nbsp;|&nbsp; <?= number_format($totalFiltered) ?> transactions</span>

            <div class="pagination">

                <?php if ($page > 1): ?>

                <a href="index.php?page=transaction_monitor&search=<?= urlencode($search) ?>&p=<?= $page-1 ?>" class="pg-btn">&laquo; Prev</a>

                <?php endif; ?>

                <?php

                $start = max(1, $page - 2);

                $end   = min($totalPages, $page + 2);

                for ($pg = $start; $pg <= $end; $pg++):

                ?>

                <a href="index.php?page=transaction_monitor&search=<?= urlencode($search) ?>&p=<?= $pg ?>"

                   class="pg-btn <?= $pg === $page ? 'active' : '' ?>"><?= $pg ?></a>

                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>

                <a href="index.php?page=transaction_monitor&search=<?= urlencode($search) ?>&p=<?= $page+1 ?>" class="pg-btn">Next &raquo;</a>

                <?php endif; ?>

            </div>

        </div>

        <?php endif; ?>

    </div>



</main>

</div>



<script>

window.addEventListener('load', function() {

    if (typeof Chart === 'undefined') return;



    <?php if (!empty($hourly)): ?>

    new Chart(document.getElementById('lineChart'), {

        type: 'line',

        data:{

            labels: <?= json_encode(array_column($hourly,'hr')) ?>,

            datasets:[{

                label:'Transactions',

                data: <?= json_encode(array_map('intval',array_column($hourly,'cnt'))) ?>,

                borderColor:'#3282B8',

                backgroundColor:'rgba(50,130,184,.1)',

                borderWidth:2.5,

                pointBackgroundColor:'#3282B8',

                pointRadius:4,

                fill:true,

                tension:0.4

            }]

        },

        options:{

            responsive:true,maintainAspectRatio:true,

            plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false}},

            scales:{

                y:{beginAtZero:true,ticks:{precision:0},grid:{color:'rgba(0,0,0,.05)'}},

                x:{grid:{display:false},ticks:{maxRotation:45,font:{size:10}}}

            }

        }

    });

    <?php endif; ?>



    <?php

    $pieData = array_filter($elecBreakdown, fn($e) => $e['tx_count'] > 0);

    if (!empty($pieData)):

    ?>

    new Chart(document.getElementById('pieChart'), {

        type: 'doughnut',

        data:{

            labels: <?= json_encode(array_column(array_values($pieData),'title')) ?>,

            datasets:[{

                data: <?= json_encode(array_map('intval',array_column(array_values($pieData),'tx_count'))) ?>,

                backgroundColor:['#3282B8','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4'],

                borderWidth:3,borderColor:'#fff',hoverOffset:8

            }]

        },

        options:{

            responsive:true,maintainAspectRatio:true,

            plugins:{

                legend:{position:'bottom',labels:{font:{size:10},padding:8,usePointStyle:true}},

                tooltip:{callbacks:{label:c=>' '+c.label+': '+c.parsed+' votes'}}

            },

            cutout:'55%'

        }

    });

    <?php endif; ?>

});



// Auto-refresh every 30 seconds

setTimeout(() => location.reload(), 30000);

</script>

</body>

</html>

