<?php
/**
 * IUC Voting System - Real-time Voting Dashboard (Dynamic)
 */
require_once 'config/config.php';

// ── Overall stats ──────────────────────────────────────────────────────────
$totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalVoters    = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM votes")->fetchColumn();
$activeElections= (int)$pdo->query("SELECT COUNT(*) FROM elections WHERE status='active'")->fetchColumn();
$eligibleVoters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'")->fetchColumn();

// ── All elections with vote counts ────────────────────────────────────────
$elections = $pdo->query("
    SELECT e.id, e.title, e.status, e.start_date, e.end_date,
           COUNT(v.id) AS total_votes
    FROM elections e
    LEFT JOIN votes v ON v.election_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Per-election candidate data for charts ────────────────────────────────
$electionCharts = [];
foreach ($elections as $elec) {
    $stmt = $pdo->prepare("
        SELECT c.name, COUNT(v.id) AS votes
        FROM candidates c
        LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = ?
        WHERE c.election_id = ?
        GROUP BY c.id
        ORDER BY votes DESC
    ");
    $stmt->execute([$elec['id'], $elec['id']]);
    $electionCharts[$elec['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Votes per hour (last 24 hours, fallback to all-time by day) ──────────
$hourlyData = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%H:00') AS hour_label,
           COUNT(*) AS vote_count
    FROM votes
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H')
    ORDER BY created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

// If no votes in last 24h, show all votes grouped by date instead
$chartLabel = 'Votes Per Hour (Last 24h)';
if (empty($hourlyData)) {
    $hourlyData = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %d') AS hour_label,
               COUNT(*) AS vote_count
        FROM votes
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $chartLabel = 'Votes Per Day (All Time)';
}

// ── Recent votes (last 15) ────────────────────────────────────────────────
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
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

// ── Votes per election (for doughnut) ────────────────────────────────────
$votesByElection = array_filter($elections, fn($e) => $e['total_votes'] > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Real-time Voting - IUC Voting System</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="assets/js/chart.umd.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;color:#1e293b}
.layout{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#1a5276 60%,#3282B8);color:#fff;padding:0;position:fixed;height:100vh;overflow-y:auto;z-index:100;box-shadow:4px 0 15px rgba(0,0,0,.15)}
.sb-brand{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}
.sb-brand .logo{width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.sb-brand h1{font-size:.95rem;font-weight:700}
.sb-brand p{font-size:.72rem;opacity:.65;margin-top:.1rem}
.sb-sec{font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;opacity:.5;padding:.8rem 1.5rem .2rem;font-weight:600}
.sb-a{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.5rem;color:rgba(255,255,255,.75);text-decoration:none;font-size:.85rem;transition:all .2s;border-left:3px solid transparent}
.sb-a:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}
.sb-a.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}
.sb-a i{width:16px;text-align:center;font-size:.85rem}

/* Main */
.main{flex:1;margin-left:260px;padding:2rem}

/* Top bar */
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.topbar h1{font-size:1.6rem;font-weight:800;color:#0B3C5D}
.topbar p{color:#64748b;font-size:.875rem;margin-top:.2rem}
.live-badge{display:inline-flex;align-items:center;gap:.4rem;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:600}
.live-dot{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}

/* Stat cards */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{background:#fff;border-radius:14px;padding:1.25rem 1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:1rem;transition:transform .2s}
.stat-card:hover{transform:translateY(-3px)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.ic-blue{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.ic-green{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.ic-orange{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.ic-purple{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff}
.stat-num{font-size:1.7rem;font-weight:800;color:#1e293b;line-height:1}
.stat-lbl{font-size:.75rem;color:#64748b;margin-top:.2rem}

/* Charts grid */
.charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:900px){.charts-grid{grid-template-columns:1fr}}
.chart-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
.chart-head{padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.chart-head h3{font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}
.chart-body{padding:1.25rem;position:relative}

/* Election tabs */
.election-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.etab{padding:.4rem .9rem;border:2px solid #e2e8f0;border-radius:20px;background:#fff;color:#64748b;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s}
.etab:hover{border-color:#3282B8;color:#3282B8}
.etab.active{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;border-color:transparent}

/* Per-election chart section */
.election-chart-section{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;overflow:hidden}
.election-chart-inner{display:none;padding:1.5rem}
.election-chart-inner.show{display:block}
.election-chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
@media(max-width:700px){.election-chart-grid{grid-template-columns:1fr}}

/* Candidate bar rows */
.cand-bar-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.9rem}
.cand-bar-row:last-child{margin-bottom:0}
.cand-rank{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.cr1{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.cr2{background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff}
.cr3{background:linear-gradient(135deg,#cd7c2f,#a16207);color:#fff}
.crn{background:#e2e8f0;color:#64748b}
.cand-info-wrap{flex:1;min-width:0}
.cand-name-lbl{font-size:.82rem;font-weight:600;color:#1e293b;margin-bottom:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{height:9px;background:#f1f5f9;border-radius:5px;overflow:hidden}
.bar-fill{height:100%;border-radius:5px;transition:width 1s ease}
.bf-gold{background:linear-gradient(90deg,#f59e0b,#d97706)}
.bf-blue{background:linear-gradient(90deg,#3282B8,#0B3C5D)}
.cand-pct{font-size:.78rem;color:#64748b;width:36px;text-align:right;flex-shrink:0}

/* Recent votes */
.recent-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0}
td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}
tr:hover td{background:#f8fafc}
.hash-short{font-family:'Courier New',monospace;color:#3282B8;font-size:.75rem}
.confirmed-badge{background:#dcfce7;color:#166534;padding:.15rem .5rem;border-radius:8px;font-size:.7rem;font-weight:700}
.no-data{text-align:center;padding:3rem;color:#94a3b8}
.no-data i{font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3}

/* Refresh note */
.refresh-note{text-align:center;color:#94a3b8;font-size:.78rem;margin-top:1rem}
.refresh-note i{color:#10b981}

@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sb-brand">
        <div class="logo"><i class="fas fa-chart-line"></i></div>
        <div><h1>IUC Voting</h1><p>Admin Panel</p></div>
    </div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">Analytics</div>
    <a href="index.php?page=realtime_voting" class="sb-a active"><i class="fas fa-chart-line"></i> Real-time Voting</a>
    <a href="index.php?page=participants" class="sb-a"><i class="fas fa-users"></i> Participants</a>
    <div class="sb-sec">Voters</div>
    <a href="index.php?page=voter_registration" class="sb-a"><i class="fas fa-user-plus"></i> Register Voters</a>
    <a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>
    <div class="sb-sec">Blockchain</div>
    <a href="index.php?page=blockchain_explorer" class="sb-a"><i class="fas fa-link"></i> Blockchain Explorer</a>
    <a href="index.php?page=vote_verification" class="sb-a"><i class="fas fa-check-circle"></i> Vote Verification</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<!-- Main -->
<main class="main">

    <!-- Top bar -->
    <div class="topbar">
        <div>
            <h1><i class="fas fa-chart-line" style="color:#3282B8;margin-right:.4rem"></i>Real-time Voting</h1>
            <p>Live vote counts and analytics — auto-refreshes every 30 seconds</p>
        </div>
        <div class="live-badge"><div class="live-dot"></div> LIVE</div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon ic-blue"><i class="fas fa-ballot-check"></i></div>
            <div><div class="stat-num" id="statTotalVotes"><?= $totalVotes ?></div><div class="stat-lbl">Total Votes Cast</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-green"><i class="fas fa-users"></i></div>
            <div><div class="stat-num"><?= $totalVoters ?></div><div class="stat-lbl">Unique Voters</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-orange"><i class="fas fa-vote-yea"></i></div>
            <div><div class="stat-num"><?= $activeElections ?></div><div class="stat-lbl">Active Elections</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-purple"><i class="fas fa-percentage"></i></div>
            <div>
                <div class="stat-num"><?= $eligibleVoters > 0 ? round($totalVotes / $eligibleVoters * 100, 1) : 0 ?>%</div>
                <div class="stat-lbl">Overall Turnout</div>
            </div>
        </div>
    </div>

    <!-- Top charts row -->
    <div class="charts-grid">
        <!-- Votes per hour (line chart) -->
        <div class="chart-card">
            <div class="chart-head">
                <h3><i class="fas fa-chart-line" style="color:#3282B8"></i> <?= $chartLabel ?></h3>
                <span style="font-size:.78rem;color:#94a3b8;"><?= count($hourlyData) ?> data points</span>
            </div>
            <div class="chart-body">
                <?php if (empty($hourlyData)): ?>
                    <div class="no-data"><i class="fas fa-chart-line"></i>No votes recorded yet. Cast a vote to see the chart.</div>
                <?php else: ?>
                    <canvas id="hourlyChart" style="max-height:220px;"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Votes by election (doughnut) -->
        <div class="chart-card">
            <div class="chart-head">
                <h3><i class="fas fa-chart-pie" style="color:#8b5cf6"></i> Votes by Election</h3>
            </div>
            <div class="chart-body">
                <?php if (empty($votesByElection)): ?>
                    <div class="no-data"><i class="fas fa-chart-pie"></i>No votes yet.</div>
                <?php else: ?>
                    <canvas id="doughnutChart" style="max-height:220px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Per-election breakdown -->
    <?php if (!empty($elections)): ?>
    <div class="election-chart-section">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
            <h3 style="font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem;"><i class="fas fa-chart-bar" style="color:#3282B8"></i> Per-Election Breakdown</h3>
            <div class="election-tabs" id="elecTabs">
                <?php foreach ($elections as $i => $e): ?>
                <button class="etab <?= $i===0?'active':'' ?>"
                        onclick="showElection(<?= $e['id'] ?>, this)">
                    <?= htmlspecialchars($e['title']) ?>
                    <span style="background:rgba(255,255,255,.25);border-radius:8px;padding:.05rem .35rem;font-size:.7rem;margin-left:.3rem;"><?= $e['total_votes'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($elections as $i => $e):
            $cands = $electionCharts[$e['id']];
            $tv = array_sum(array_column($cands, 'votes'));
        ?>
        <div class="election-chart-inner <?= $i===0?'show':'' ?>" id="elec-<?= $e['id'] ?>">
            <?php if (empty($cands) || $tv == 0): ?>
                <div class="no-data"><i class="fas fa-hourglass-half"></i>No votes cast yet for this election.</div>
            <?php else: ?>
            <div class="election-chart-grid">
                <!-- Bar chart -->
                <div>
                    <h4 style="font-size:.85rem;font-weight:600;color:#374151;margin-bottom:1rem;">Vote Distribution</h4>
                    <?php foreach ($cands as $ci => $c):
                        $pct = $tv > 0 ? round($c['votes'] / $tv * 100, 1) : 0;
                        $rc = $ci===0?'cr1':($ci===1?'cr2':($ci===2?'cr3':'crn'));
                        $bf = $ci===0?'bf-gold':'bf-blue';
                    ?>
                    <div class="cand-bar-row">
                        <div class="cand-rank <?= $rc ?>"><?= $ci+1 ?></div>
                        <div class="cand-info-wrap">
                            <div class="cand-name-lbl"><?= htmlspecialchars($c['name']) ?></div>
                            <div class="bar-track">
                                <div class="bar-fill <?= $bf ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <div class="cand-pct"><?= $pct ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Polar area chart -->
                <div>
                    <h4 style="font-size:.85rem;font-weight:600;color:#374151;margin-bottom:1rem;">Visual Breakdown</h4>
                    <canvas id="polarChart-<?= $e['id'] ?>" height="200"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Recent votes -->
    <div class="recent-card">
        <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem;"><i class="fas fa-history" style="color:#3282B8"></i> Recent Votes (Last 15)</h3>
            <span style="font-size:.78rem;color:#94a3b8;">Auto-refreshes every 30s</span>
        </div>
        <?php if (empty($recentVotes)): ?>
            <div class="no-data"><i class="fas fa-vote-yea"></i>No votes recorded yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Voter</th>
                        <th>Election</th>
                        <th>Voted For</th>
                        <th>Date &amp; Time</th>
                        <th>Hash</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVotes as $i => $v): ?>
                    <tr>
                        <td style="color:#94a3b8;"><?= $i+1 ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($v['voter_name']) ?></td>
                        <td style="color:#3282B8;"><?= htmlspecialchars($v['election_title']) ?></td>
                        <td style="color:#8b5cf6;"><?= htmlspecialchars($v['candidate_name']) ?></td>
                        <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y H:i:s', strtotime($v['created_at'])) ?></td>
                        <td class="hash-short"><?= htmlspecialchars(substr($v['transaction_hash'], 0, 20)) ?>…</td>
                        <td><span class="confirmed-badge">CONFIRMED</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="refresh-note"><i class="fas fa-sync-alt"></i> Page auto-refreshes every 30 seconds to show latest votes</div>

</main>
</div>

<script>
// Wait for Chart.js to be available
function initCharts() {
    if (typeof Chart === 'undefined') {
        setTimeout(initCharts, 200);
        return;
    }

    const COLORS = [
        '#3282B8','#10b981','#f59e0b','#8b5cf6','#ef4444',
        '#06b6d4','#84cc16','#f97316','#ec4899','#6366f1'
    ];

    // ── Line chart: votes over time ──────────────────────────────────────
    <?php if (!empty($hourlyData)): ?>
    (function(){
        const ctx = document.getElementById('hourlyChart');
        if (!ctx) return;
        const labels = <?= json_encode(array_column($hourlyData, 'hour_label')) ?>;
        const data   = <?= json_encode(array_map('intval', array_column($hourlyData, 'vote_count'))) ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets:[{
                    label: 'Votes',
                    data,
                    borderColor: '#3282B8',
                    backgroundColor: 'rgba(50,130,184,.12)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#3282B8',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:true,
                plugins:{
                    legend:{display:false},
                    tooltip:{mode:'index',intersect:false}
                },
                scales:{
                    y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,.05)'}},
                    x:{grid:{display:false},ticks:{maxRotation:45}}
                }
            }
        });
    })();
    <?php endif; ?>

    // ── Doughnut: votes by election ──────────────────────────────────────
    <?php if (!empty($votesByElection)): ?>
    (function(){
        const ctx = document.getElementById('doughnutChart');
        if (!ctx) return;
        const labels = <?= json_encode(array_values(array_map(fn($e) => $e['title'], $votesByElection))) ?>;
        const data   = <?= json_encode(array_values(array_map(fn($e) => (int)$e['total_votes'], $votesByElection))) ?>;
        new Chart(ctx, {
            type: 'doughnut',
            data:{
                labels,
                datasets:[{
                    data,
                    backgroundColor: COLORS.slice(0, labels.length),
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:true,
                plugins:{
                    legend:{
                        position:'bottom',
                        labels:{font:{size:11},padding:12,usePointStyle:true}
                    },
                    tooltip:{
                        callbacks:{
                            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' votes'
                        }
                    }
                },
                cutout:'55%'
            }
        });
    })();
    <?php endif; ?>

    // ── Polar area charts per election ───────────────────────────────────
    <?php foreach ($elections as $e):
        $cands = $electionCharts[$e['id']];
        $tv = array_sum(array_column($cands, 'votes'));
        if ($tv == 0) continue;
    ?>
    (function(){
        const ctx = document.getElementById('polarChart-<?= $e['id'] ?>');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'polarArea',
            data:{
                labels: <?= json_encode(array_column($cands, 'name')) ?>,
                datasets:[{
                    data: <?= json_encode(array_map('intval', array_column($cands, 'votes'))) ?>,
                    backgroundColor: COLORS.slice(0, <?= count($cands) ?>).map(c => c + 'bb'),
                    borderColor: COLORS.slice(0, <?= count($cands) ?>),
                    borderWidth: 2
                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:true,
                plugins:{
                    legend:{
                        position:'bottom',
                        labels:{font:{size:10},padding:8,usePointStyle:true}
                    }
                },
                scales:{
                    r:{
                        ticks:{display:false,stepSize:1},
                        grid:{color:'rgba(0,0,0,.07)'}
                    }
                }
            }
        });
    })();
    <?php endforeach; ?>
}

// Start initialization
initCharts();

// ── Election tab switcher ─────────────────────────────────────────────────
function showElection(id, btn) {
    document.querySelectorAll('.election-chart-inner').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('.etab').forEach(b => b.classList.remove('active'));
    const target = document.getElementById('elec-' + id);
    if (target) target.classList.add('show');
    btn.classList.add('active');
}

// ── Auto-refresh every 30 seconds ────────────────────────────────────────
setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>
