<?php

/**

 * IUC Voting System - All Election Results (Professional Dashboard)

 */



require_once 'config/config.php';

require_once 'includes/election.php';



$electionManager = new Election();

$isAdmin = ($_SESSION['user_type'] ?? '') === 'admin';



// ── If a specific election is requested, show detail view ──────────────────

$detailId = !empty($_GET['election_id']) && is_numeric($_GET['election_id'])

    ? (int)$_GET['election_id'] : null;



// ── Handle delete (admin only) ─────────────────────────────────────────────

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_election_id'])) {

    $delId = (int)$_POST['delete_election_id'];

    $pdo->prepare("DELETE FROM blockchain_transactions WHERE election_id=?")->execute([$delId]);

    $pdo->prepare("DELETE FROM votes WHERE election_id=?")->execute([$delId]);

    $pdo->prepare("DELETE FROM candidates WHERE election_id=?")->execute([$delId]);

    $pdo->prepare("DELETE FROM elections WHERE id=?")->execute([$delId]);

    header("Location: index.php?page=results&deleted=1");

    exit;

}



// ── Load all elections with aggregated stats ───────────────────────────────

// If detail view requested, handle it first

if ($detailId) {

    $elec = $electionManager->getElectionById($detailId);

    if (!$elec) {

        header("Location: index.php?page=results");

        exit;

    }



    // Candidates with vote counts

    $stmt = $pdo->prepare("

        SELECT c.id, c.name, c.description,

               COUNT(v.id) AS vote_count

        FROM candidates c

        LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = ?

        WHERE c.election_id = ?

        GROUP BY c.id

        ORDER BY vote_count DESC, c.position ASC

    ");

    $stmt->execute([$detailId, $detailId]);

    $detailCands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $detailTotal = array_sum(array_column($detailCands, 'vote_count'));

    foreach ($detailCands as &$dc) {

        $dc['pct'] = $detailTotal > 0 ? round($dc['vote_count'] / $detailTotal * 100, 1) : 0;

    }

    unset($dc);



    $detailWinner = !empty($detailCands) && $detailCands[0]['vote_count'] > 0 ? $detailCands[0] : null;



    $eligibleVoters = (int)$pdo->query(

        "SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'"

    )->fetchColumn();

    $turnout = $eligibleVoters > 0 ? round($detailTotal / $eligibleVoters * 100, 1) : 0;



    // Transaction hashes

    $txStmt = $pdo->prepare("

        SELECT v.transaction_hash, v.created_at,

               u.name  AS voter_name,

               c.name  AS candidate_name

        FROM votes v

        JOIN users      u ON v.user_id      = u.id

        JOIN candidates c ON v.candidate_id = c.id

        WHERE v.election_id = ?

        ORDER BY v.created_at DESC

    ");

    $txStmt->execute([$detailId]);

    $txRows = $txStmt->fetchAll(PDO::FETCH_ASSOC);



    $isAdmin = ($_SESSION['user_type'] ?? '') === 'admin';

    ?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($elec['title']) ?> — Results</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>

*{margin:0;padding:0;box-sizing:border-box}

body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;color:#1e293b}

.layout{display:flex;min-height:100vh}

.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#1a5276 60%,#3282B8);color:#fff;padding:0;position:fixed;height:100vh;overflow-y:auto;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,.2)}

.sb-brand{padding:1.75rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}

.sb-brand .logo{width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}

.sb-brand h1{font-size:1rem;font-weight:700}

.sb-brand p{font-size:.75rem;opacity:.65;margin-top:.1rem}

.sb-section{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;opacity:.55;padding:.9rem 1.5rem .3rem;font-weight:600}

.sb-link{display:flex;align-items:center;gap:.75rem;padding:.65rem 1.5rem;color:rgba(255,255,255,.78);text-decoration:none;font-size:.875rem;transition:all .2s;border-left:3px solid transparent}

.sb-link:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}

.sb-link.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}

.sb-link i{width:18px;text-align:center}

.main{flex:1;margin-left:260px;padding:2rem}

.back-btn{display:inline-flex;align-items:center;gap:.5rem;color:#3282B8;text-decoration:none;font-weight:600;font-size:.9rem;margin-bottom:1.5rem;padding:.5rem 1rem;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:all .2s}

.back-btn:hover{background:#3282B8;color:#fff}

.page-title-bar{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem}

.page-title-bar h1{font-size:1.5rem;font-weight:800;color:#0B3C5D}

.page-title-bar p{color:#64748b;font-size:.85rem;margin-top:.3rem}

.status-pill{padding:.35rem .9rem;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase}

.pill-active{background:#dcfce7;color:#166534}

.pill-draft{background:#fef9c3;color:#854d0e}

.pill-completed,.pill-closed{background:#e0e7ff;color:#3730a3}

.pill-upcoming{background:#dbeafe;color:#1e40af}



/* Winner */

.winner-banner{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;border-radius:14px;padding:2rem;text-align:center;margin-bottom:1.5rem;box-shadow:0 4px 20px rgba(11,60,93,.3)}

.winner-crown{font-size:2.5rem;margin-bottom:.5rem}

.winner-name{font-size:1.8rem;font-weight:800;margin-bottom:.25rem}

.winner-sub{opacity:.8;font-size:.9rem;margin-bottom:1rem}

.winner-stats{display:flex;justify-content:center;gap:2.5rem;flex-wrap:wrap}

.winner-stat{display:flex;align-items:center;gap:.4rem;font-size:.95rem}



/* Grid */

.results-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem}

@media(max-width:900px){.results-grid{grid-template-columns:1fr}}

.card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}

.card-head{padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}

.card-head h3{font-size:1rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}

.card-body{padding:1.5rem}



/* Candidate bars */

.cand-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.1rem}

.cand-row:last-child{margin-bottom:0}

.rank{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}

.r1{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}

.r2{background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff}

.r3{background:linear-gradient(135deg,#cd7c2f,#a16207);color:#fff}

.rn{background:#e2e8f0;color:#64748b}

.cand-info{flex:1;min-width:0}

.cand-name{font-weight:600;color:#1e293b;font-size:.9rem}

.cand-desc{font-size:.75rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.bar-wrap{flex:2}

.bar-lbl{display:flex;justify-content:space-between;font-size:.78rem;color:#64748b;margin-bottom:.3rem}

.bar-track{height:10px;background:#f1f5f9;border-radius:5px;overflow:hidden}

.bar-fill{height:100%;border-radius:5px;background:linear-gradient(90deg,#3282B8,#0B3C5D);transition:width .8s ease}

.bar-fill.gold{background:linear-gradient(90deg,#f59e0b,#d97706)}



/* Stats */

.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}

.stat-box{background:#f8fafc;border-radius:8px;padding:.9rem;text-align:center}

.stat-num{font-size:1.5rem;font-weight:800;color:#0B3C5D}

.stat-lbl{font-size:.72rem;color:#64748b;margin-top:.2rem}



/* TX table */

.tx-card{background:#fff;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}

table{width:100%;border-collapse:collapse;font-size:.83rem}

th{background:#f8fafc;padding:.65rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0}

td{padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}

tr:hover td{background:#f8fafc}

.hash-cell{font-family:'Courier New',monospace;color:#3282B8;font-size:.78rem;word-break:break-all}

.btn-sm{padding:.3rem .7rem;border-radius:6px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;border:none;cursor:pointer;transition:all .2s}

.btn-verify-sm{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

.btn-verify-sm:hover{background:#166534;color:#fff}

.no-tx{text-align:center;padding:2rem;color:#94a3b8}

.no-tx i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}

@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

    <div class="sb-brand">

        <div class="logo"><i class="fas fa-shield-alt"></i></div>

        <div><h1>IUC Voting</h1><p><?= $isAdmin ? 'Admin Panel' : 'Student Portal' ?></p></div>

    </div>

    <?php if ($isAdmin): ?>

    <div class="sb-section">Main</div>

    <a href="index.php?page=admin" class="sb-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

    <div class="sb-section">Elections</div>

    <a href="index.php?page=elections" class="sb-link"><i class="fas fa-vote-yea"></i> Elections</a>

    <a href="index.php?page=results" class="sb-link active"><i class="fas fa-chart-bar"></i> Results</a>

    <div class="sb-section">Audit</div>

    <a href="index.php?page=vote_verification" class="sb-link"><i class="fas fa-check-circle"></i> Vote Verification</a>

    <?php else: ?>

    <div class="sb-section">Menu</div>

    <a href="index.php?page=dashboard" class="sb-link"><i class="fas fa-home"></i> Dashboard</a>

    <a href="index.php?page=simple_voting" class="sb-link"><i class="fas fa-vote-yea"></i> Vote Now</a>

    <a href="index.php?page=results" class="sb-link active"><i class="fas fa-chart-bar"></i> Results</a>

    <a href="index.php?page=verify" class="sb-link"><i class="fas fa-shield-alt"></i> Verify Vote</a>

    <?php endif; ?>

    <a href="index.php?page=logout" class="sb-link" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>

</aside>



<main class="main">

    <a href="index.php?page=results" class="back-btn"><i class="fas fa-arrow-left"></i> All Elections</a>



    <div class="page-title-bar">

        <div>

            <h1><?= htmlspecialchars($elec['title']) ?></h1>

            <p><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($elec['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($elec['end_date'])) ?></p>

        </div>

        <?php

            $s = $elec['status'];

            $pc = match($s){ 'active'=>'pill-active','draft'=>'pill-draft','completed'=>'pill-completed','closed'=>'pill-closed','upcoming'=>'pill-upcoming',default=>'pill-draft'};

        ?>

        <span class="status-pill <?= $pc ?>"><?= ucfirst($s) ?></span>

    </div>



    <!-- Winner -->

    <?php if ($detailWinner): ?>

    <div class="winner-banner">

        <div class="winner-crown">👑</div>

        <div class="winner-name"><?= htmlspecialchars($detailWinner['name']) ?></div>

        <div class="winner-sub">Currently leading</div>

        <div class="winner-stats">

            <div class="winner-stat"><i class="fas fa-vote-yea"></i> <?= $detailWinner['vote_count'] ?> Votes</div>

            <div class="winner-stat"><i class="fas fa-percentage"></i> <?= $detailWinner['pct'] ?>%</div>

            <div class="winner-stat"><i class="fas fa-trophy"></i> Leader</div>

        </div>

    </div>

    <?php else: ?>

    <div style="background:#f1f5f9;border:2px dashed #cbd5e1;border-radius:14px;padding:1.5rem;text-align:center;color:#64748b;margin-bottom:1.5rem;">

        <i class="fas fa-hourglass-half" style="font-size:1.5rem;margin-bottom:.5rem;display:block;"></i>

        No votes cast yet for this election.

    </div>

    <?php endif; ?>



    <div class="results-grid">

        <!-- Vote distribution -->

        <div class="card">

            <div class="card-head">

                <h3><i class="fas fa-chart-bar"></i> Vote Distribution</h3>

                <span style="color:#64748b;font-size:.82rem;">Total: <?= $detailTotal ?> votes</span>

            </div>

            <div class="card-body">

                <?php if (empty($detailCands)): ?>

                    <p style="color:#94a3b8;text-align:center;padding:2rem;">No candidates found.</p>

                <?php else: ?>

                    <?php foreach ($detailCands as $i => $c):

                        $r = $i+1;

                        $rc = $r===1?'r1':($r===2?'r2':($r===3?'r3':'rn'));

                        $bc = $r===1?'bar-fill gold':'bar-fill';

                    ?>

                    <div class="cand-row">

                        <div class="rank <?= $rc ?>"><?= $r ?></div>

                        <div class="cand-info">

                            <div class="cand-name"><?= htmlspecialchars($c['name']) ?></div>

                            <div class="cand-desc"><?= htmlspecialchars($c['description'] ?? '') ?></div>

                        </div>

                        <div class="bar-wrap">

                            <div class="bar-lbl"><span><?= $c['vote_count'] ?> votes</span><span><?= $c['pct'] ?>%</span></div>

                            <div class="bar-track"><div class="<?= $bc ?>" style="width:<?= $c['pct'] ?>%"></div></div>

                        </div>

                    </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>



        <!-- Stats -->

        <div class="card">

            <div class="card-head"><h3><i class="fas fa-chart-pie"></i> Statistics</h3></div>

            <div class="card-body">

                <div class="stats-grid">

                    <div class="stat-box"><div class="stat-num"><?= $detailTotal ?></div><div class="stat-lbl">Total Votes</div></div>

                    <div class="stat-box"><div class="stat-num"><?= $turnout ?>%</div><div class="stat-lbl">Turnout</div></div>

                    <div class="stat-box"><div class="stat-num"><?= $eligibleVoters ?></div><div class="stat-lbl">Eligible Voters</div></div>

                    <div class="stat-box"><div class="stat-num"><?= max(0,$eligibleVoters-$detailTotal) ?></div><div class="stat-lbl">Not Voted</div></div>

                    <div class="stat-box"><div class="stat-num"><?= count($detailCands) ?></div><div class="stat-lbl">Candidates</div></div>

                    <div class="stat-box"><div class="stat-num"><?= ucfirst($elec['status']) ?></div><div class="stat-lbl">Status</div></div>

                </div>

                <div style="margin-top:1rem;background:#1e293b;border-radius:8px;padding:1rem;">

                    <div style="color:#94a3b8;font-size:.72rem;font-weight:600;text-transform:uppercase;margin-bottom:.4rem;">Period</div>

                    <div style="color:#e2e8f0;font-size:.82rem;">

                        <i class="fas fa-calendar" style="color:#3282B8;"></i>

                        <?= date('M d, Y', strtotime($elec['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($elec['end_date'])) ?>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- Transaction Hashes -->

    <div class="tx-card">

        <div class="card-head">

            <h3><i class="fas fa-link"></i> Blockchain Transaction Hashes</h3>

            <span style="color:#64748b;font-size:.82rem;"><?= count($txRows) ?> transaction<?= count($txRows)!==1?'s':'' ?></span>

        </div>

        <?php if (empty($txRows)): ?>

            <div class="no-tx"><i class="fas fa-link"></i>No transactions recorded yet.</div>

        <?php else: ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Voter</th>

                        <th>Voted For</th>

                        <th>Date &amp; Time</th>

                        <th>Transaction Hash</th>

                        <th>Verify</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($txRows as $i => $tx): ?>

                    <tr>

                        <td style="color:#94a3b8;"><?= $i+1 ?></td>

                        <td style="font-weight:600;"><?= htmlspecialchars($tx['voter_name']) ?></td>

                        <td><?= htmlspecialchars($tx['candidate_name']) ?></td>

                        <td style="white-space:nowrap;color:#64748b;"><?= date('M d, Y H:i', strtotime($tx['created_at'])) ?></td>

                        <td class="hash-cell"><?= htmlspecialchars($tx['transaction_hash']) ?></td>

                        <td>

                            <a href="index.php?page=<?= $isAdmin?'vote_verification':'verify' ?>"

                               onclick="sessionStorage.setItem('verifyHash','<?= htmlspecialchars($tx['transaction_hash']) ?>')"

                               class="btn-sm btn-verify-sm">

                                <i class="fas fa-shield-alt"></i> Verify

                            </a>

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

window.addEventListener('load',()=>{

    document.querySelectorAll('.bar-fill').forEach(b=>{

        const w=b.style.width; b.style.width='0%';

        setTimeout(()=>{b.style.width=w;},300);

    });

});

</script>

</body>

</html>

    <?php

    exit; // stop here — don't render the overview

}



// ── Load all elections with aggregated stats ───────────────────────────────

$elections = $pdo->query("

    SELECT e.*,

           (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id)     AS total_votes,

           (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS total_candidates

    FROM elections e

    ORDER BY e.created_at DESC

")->fetchAll(PDO::FETCH_ASSOC);



$eligibleVoters = (int)$pdo->query(

    "SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'"

)->fetchColumn();



// ── Overall stats ──────────────────────────────────────────────────────────

$totalElections  = count($elections);

$activeCount     = count(array_filter($elections, fn($e) => $e['status'] === 'active'));

$totalVotesAll   = array_sum(array_column($elections, 'total_votes'));

$completedCount  = count(array_filter($elections, fn($e) => in_array($e['status'], ['completed','closed'])));



// ── Per-election candidate results (for detail view) ──────────────────────

$electionDetails = [];

foreach ($elections as $elec) {

    $stmt = $pdo->prepare("

        SELECT c.name, c.description,

               COUNT(v.id) AS vote_count

        FROM candidates c

        LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = ?

        WHERE c.election_id = ?

        GROUP BY c.id

        ORDER BY vote_count DESC, c.position ASC

    ");

    $stmt->execute([$elec['id'], $elec['id']]);

    $cands = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tv = array_sum(array_column($cands, 'vote_count'));

    foreach ($cands as &$c) {

        $c['pct'] = $tv > 0 ? round($c['vote_count'] / $tv * 100, 1) : 0;

    }

    unset($c);

    $electionDetails[$elec['id']] = ['candidates' => $cands, 'total' => $tv];

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Election Results - IUC Voting System</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>

*{margin:0;padding:0;box-sizing:border-box}

body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f4f8;color:#1e293b}

.layout{display:flex;min-height:100vh}



/* Sidebar */

.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D 0%,#1a5276 60%,#3282B8 100%);color:#fff;padding:0;position:fixed;height:100vh;overflow-y:auto;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,.2)}

.sb-brand{padding:1.75rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}

.sb-brand .logo{width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}

.sb-brand h1{font-size:1rem;font-weight:700;line-height:1.2}

.sb-brand p{font-size:.75rem;opacity:.65;margin-top:.1rem}

.sb-section{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;opacity:.55;padding:.9rem 1.5rem .3rem;font-weight:600}

.sb-link{display:flex;align-items:center;gap:.75rem;padding:.65rem 1.5rem;color:rgba(255,255,255,.78);text-decoration:none;font-size:.875rem;transition:all .2s;border-left:3px solid transparent;margin:.1rem 0}

.sb-link:hover{background:rgba(255,255,255,.1);color:#fff;border-left-color:rgba(255,255,255,.4)}

.sb-link.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}

.sb-link i{width:18px;text-align:center;font-size:.9rem}



/* Main */

.main{flex:1;margin-left:260px;padding:2rem;min-height:100vh}



/* Top bar */

.topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}

.topbar-left h1{font-size:1.75rem;font-weight:800;color:#0B3C5D;letter-spacing:-.02em}

.topbar-left p{color:#64748b;font-size:.9rem;margin-top:.3rem}

.year-badge{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;padding:.5rem 1.25rem;border-radius:20px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:.5rem}



/* Summary cards */

.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem;margin-bottom:2rem}

.sum-card{background:#fff;border-radius:14px;padding:1.25rem 1.5rem;box-shadow:0 2px 12px rgba(0,0,0,.06);display:flex;align-items:center;gap:1rem;transition:transform .2s}

.sum-card:hover{transform:translateY(-3px)}

.sum-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}

.sum-icon.blue{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}

.sum-icon.green{background:linear-gradient(135deg,#10b981,#059669);color:#fff}

.sum-icon.orange{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}

.sum-icon.purple{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff}

.sum-num{font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1}

.sum-lbl{font-size:.78rem;color:#64748b;margin-top:.2rem}



/* Filter bar */

.filter-bar{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:center}

.filter-btn{padding:.45rem 1rem;border:2px solid #e2e8f0;border-radius:20px;background:#fff;color:#64748b;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}

.filter-btn:hover{border-color:#3282B8;color:#3282B8}

.filter-btn.active{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;border-color:transparent}

.filter-count{background:rgba(255,255,255,.3);border-radius:10px;padding:.1rem .4rem;font-size:.72rem;margin-left:.3rem}



/* Election cards grid */

.elections-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:1.5rem}



/* Election card */

.elec-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden;transition:all .3s;border:1px solid #e8edf2}

.elec-card:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,.12)}



.elec-card-header{padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;border-bottom:1px solid #f1f5f9}

.elec-title{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.3rem;line-height:1.3}

.elec-dates{font-size:.75rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem}

.status-pill{padding:.3rem .8rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;flex-shrink:0}

.pill-active{background:#dcfce7;color:#166534}

.pill-draft{background:#fef9c3;color:#854d0e}

.pill-completed,.pill-closed{background:#e0e7ff;color:#3730a3}

.pill-cancelled{background:#fee2e2;color:#991b1b}

.pill-upcoming{background:#dbeafe;color:#1e40af}



.elec-card-body{padding:1.25rem 1.5rem}



/* Mini stats row */

.mini-stats{display:flex;gap:1.5rem;margin-bottom:1.25rem}

.mini-stat .val{font-size:1.3rem;font-weight:800;color:#0B3C5D}

.mini-stat .lbl{font-size:.72rem;color:#94a3b8;margin-top:.1rem}



/* Candidate mini bars */

.cand-bars{display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.25rem}

.cand-bar-row{display:flex;align-items:center;gap:.6rem}

.cand-bar-name{font-size:.8rem;font-weight:600;color:#374151;width:110px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.cand-bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden}

.cand-bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#3282B8,#0B3C5D);transition:width .8s ease}

.cand-bar-fill.gold{background:linear-gradient(90deg,#f59e0b,#d97706)}

.cand-bar-pct{font-size:.75rem;color:#64748b;width:38px;text-align:right;flex-shrink:0}

.no-votes-msg{text-align:center;padding:1rem;color:#94a3b8;font-size:.85rem}

.no-votes-msg i{display:block;font-size:1.5rem;margin-bottom:.4rem;opacity:.4}



/* Card footer */

.elec-card-footer{padding:.9rem 1.5rem;background:#f8fafc;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap}

.btn{padding:.45rem 1rem;border:none;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;transition:all .2s}

.btn-detail{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}

.btn-detail:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(50,130,184,.35)}

.btn-delete{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}

.btn-delete:hover{background:#dc2626;color:#fff}

.btn-verify{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}

.btn-verify:hover{background:#166534;color:#fff}



/* Empty state */

.empty-state{text-align:center;padding:4rem 2rem;color:#94a3b8}

.empty-state i{font-size:4rem;margin-bottom:1rem;display:block;opacity:.3}

.empty-state h3{font-size:1.2rem;font-weight:600;margin-bottom:.5rem;color:#64748b}



/* Toast */

.toast{position:fixed;top:1.5rem;right:1.5rem;background:#10b981;color:#fff;padding:.75rem 1.5rem;border-radius:10px;font-weight:600;font-size:.9rem;z-index:9999;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 15px rgba(16,185,129,.4);animation:slideIn .3s ease}

@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}



/* Delete modal */

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}

.modal-overlay.show{display:flex}

.modal-box{background:#fff;border-radius:16px;padding:2rem;max-width:420px;width:90%;box-shadow:0 20px 50px rgba(0,0,0,.2);text-align:center}

.modal-box .modal-icon{font-size:3rem;margin-bottom:1rem}

.modal-box h3{font-size:1.2rem;font-weight:700;color:#1e293b;margin-bottom:.5rem}

.modal-box p{color:#64748b;font-size:.9rem;margin-bottom:1.5rem}

.modal-actions{display:flex;gap:.75rem;justify-content:center}

.btn-cancel{background:#f1f5f9;color:#64748b;padding:.6rem 1.5rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:.9rem}

.btn-confirm-delete{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:.6rem 1.5rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:.9rem}



@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}.elections-grid{grid-template-columns:1fr}}

</style>

</head>

<body>

<div class="layout">



<!-- Sidebar -->

<aside class="sidebar">

    <div class="sb-brand">

        <div class="logo"><i class="fas fa-shield-alt"></i></div>

        <div>

            <h1>IUC Voting</h1>

            <p><?= $isAdmin ? 'Admin Panel' : 'Student Portal' ?></p>

        </div>

    </div>

    <?php if ($isAdmin): ?>

    <div class="sb-section">Main</div>

    <a href="index.php?page=admin" class="sb-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

    <div class="sb-section">Elections</div>

    <a href="index.php?page=elections" class="sb-link"><i class="fas fa-vote-yea"></i> Elections</a>

    <a href="index.php?page=results" class="sb-link active"><i class="fas fa-chart-bar"></i> Results</a>

    <div class="sb-section">Voters</div>

    <a href="index.php?page=voter_registration" class="sb-link"><i class="fas fa-user-plus"></i> Register Voters</a>

    <a href="index.php?page=voter_list" class="sb-link"><i class="fas fa-users-cog"></i> Voter List</a>

    <div class="sb-section">Audit</div>

    <a href="index.php?page=vote_verification" class="sb-link"><i class="fas fa-check-circle"></i> Vote Verification</a>

    <?php else: ?>

    <div class="sb-section">Menu</div>

    <a href="index.php?page=dashboard" class="sb-link"><i class="fas fa-home"></i> Dashboard</a>

    <a href="index.php?page=simple_voting" class="sb-link"><i class="fas fa-vote-yea"></i> Vote Now</a>

    <a href="index.php?page=results" class="sb-link active"><i class="fas fa-chart-bar"></i> Results</a>

    <a href="index.php?page=verify" class="sb-link"><i class="fas fa-shield-alt"></i> Verify Vote</a>

    <?php endif; ?>

    <a href="index.php?page=logout" class="sb-link" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>

</aside>



<!-- Main -->

<main class="main">



    <?php if (!empty($_GET['deleted'])): ?>

    <div class="toast" id="toast"><i class="fas fa-check-circle"></i> Election deleted successfully</div>

    <script>setTimeout(()=>document.getElementById('toast').remove(), 3500)</script>

    <?php endif; ?>



    <!-- Top bar -->

    <div class="topbar">

        <div class="topbar-left">

            <h1><i class="fas fa-chart-bar" style="color:#3282B8;margin-right:.4rem"></i>Election Results</h1>

            <p>All elections — real-time vote counts and standings</p>

        </div>

        <div class="year-badge"><i class="fas fa-calendar"></i> <?= date('Y') ?></div>

    </div>



    <!-- Summary cards -->

    <div class="summary-grid">

        <div class="sum-card">

            <div class="sum-icon blue"><i class="fas fa-vote-yea"></i></div>

            <div><div class="sum-num"><?= $totalElections ?></div><div class="sum-lbl">Total Elections</div></div>

        </div>

        <div class="sum-card">

            <div class="sum-icon green"><i class="fas fa-play-circle"></i></div>

            <div><div class="sum-num"><?= $activeCount ?></div><div class="sum-lbl">Active Now</div></div>

        </div>

        <div class="sum-card">

            <div class="sum-icon orange"><i class="fas fa-ballot-check"></i></div>

            <div><div class="sum-num"><?= number_format($totalVotesAll) ?></div><div class="sum-lbl">Total Votes Cast</div></div>

        </div>

        <div class="sum-card">

            <div class="sum-icon purple"><i class="fas fa-users"></i></div>

            <div><div class="sum-num"><?= $eligibleVoters ?></div><div class="sum-lbl">Eligible Voters</div></div>

        </div>

    </div>



    <!-- Filter tabs -->

    <div class="filter-bar">

        <button class="filter-btn active" onclick="filterCards('all',this)">

            All <span class="filter-count"><?= $totalElections ?></span>

        </button>

        <button class="filter-btn" onclick="filterCards('active',this)">

            Active <span class="filter-count"><?= $activeCount ?></span>

        </button>

        <button class="filter-btn" onclick="filterCards('draft',this)">

            Draft <span class="filter-count"><?= count(array_filter($elections,fn($e)=>$e['status']==='draft')) ?></span>

        </button>

        <button class="filter-btn" onclick="filterCards('completed',this)">

            Completed <span class="filter-count"><?= $completedCount ?></span>

        </button>

        <button class="filter-btn" onclick="filterCards('upcoming',this)">

            Upcoming <span class="filter-count"><?= count(array_filter($elections,fn($e)=>$e['status']==='upcoming')) ?></span>

        </button>

    </div>



    <!-- Election cards -->

    <?php if (empty($elections)): ?>

        <div class="empty-state">

            <i class="fas fa-vote-yea"></i>

            <h3>No elections yet</h3>

            <p>Elections created by the admin will appear here.</p>

        </div>

    <?php else: ?>

    <div class="elections-grid" id="electionsGrid">

        <?php foreach ($elections as $elec):

            $detail   = $electionDetails[$elec['id']];

            $tv       = $detail['total'];

            $cands    = $detail['candidates'];

            $winner   = !empty($cands) && $cands[0]['vote_count'] > 0 ? $cands[0] : null;

            $turnout  = $eligibleVoters > 0 ? round($tv / $eligibleVoters * 100, 1) : 0;

            $status   = $elec['status'];

            $pillClass = match($status) {

                'active'    => 'pill-active',

                'draft'     => 'pill-draft',

                'completed' => 'pill-completed',

                'closed'    => 'pill-closed',

                'upcoming'  => 'pill-upcoming',

                default     => 'pill-draft'

            };

        ?>

        <div class="elec-card" data-status="<?= $status ?>">

            <div class="elec-card-header">

                <div>

                    <div class="elec-title"><?= htmlspecialchars($elec['title']) ?></div>

                    <div class="elec-dates">

                        <i class="fas fa-calendar-alt"></i>

                        <?= date('M d', strtotime($elec['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($elec['end_date'])) ?>

                    </div>

                </div>

                <span class="status-pill <?= $pillClass ?>"><?= ucfirst($status) ?></span>

            </div>



            <div class="elec-card-body">

                <!-- Mini stats -->

                <div class="mini-stats">

                    <div class="mini-stat">

                        <div class="val"><?= $tv ?></div>

                        <div class="lbl">Votes</div>

                    </div>

                    <div class="mini-stat">

                        <div class="val"><?= $elec['total_candidates'] ?></div>

                        <div class="lbl">Candidates</div>

                    </div>

                    <div class="mini-stat">

                        <div class="val"><?= $turnout ?>%</div>

                        <div class="lbl">Turnout</div>

                    </div>

                    <?php if ($winner): ?>

                    <div class="mini-stat">

                        <div class="val" style="font-size:.9rem;color:#f59e0b;">👑 <?= htmlspecialchars(explode(' ',$winner['name'])[0]) ?></div>

                        <div class="lbl">Leading</div>

                    </div>

                    <?php endif; ?>

                </div>



                <!-- Candidate bars (top 4) -->

                <?php if (empty($cands) || $tv === 0): ?>

                    <div class="no-votes-msg"><i class="fas fa-hourglass-half"></i>No votes cast yet</div>

                <?php else: ?>

                <div class="cand-bars">

                    <?php foreach (array_slice($cands, 0, 4) as $ci => $c): ?>

                    <div class="cand-bar-row">

                        <div class="cand-bar-name" title="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></div>

                        <div class="cand-bar-track">

                            <div class="cand-bar-fill <?= $ci===0?'gold':'' ?>" style="width:<?= $c['pct'] ?>%"></div>

                        </div>

                        <div class="cand-bar-pct"><?= $c['pct'] ?>%</div>

                    </div>

                    <?php endforeach; ?>

                    <?php if (count($cands) > 4): ?>

                        <div style="font-size:.75rem;color:#94a3b8;text-align:center;margin-top:.25rem;">+<?= count($cands)-4 ?> more candidates</div>

                    <?php endif; ?>

                </div>

                <?php endif; ?>

            </div>



            <div class="elec-card-footer">

                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">

                    <a href="index.php?page=results&election_id=<?= $elec['id'] ?>" class="btn btn-detail">

                        <i class="fas fa-chart-bar"></i> Full Results

                    </a>

                    <a href="index.php?page=<?= $isAdmin?'vote_verification':'verify' ?>" class="btn btn-verify">

                        <i class="fas fa-shield-alt"></i> Verify

                    </a>

                </div>

                <?php if ($isAdmin): ?>

                <button class="btn btn-delete" onclick="confirmDelete(<?= $elec['id'] ?>, '<?= htmlspecialchars(addslashes($elec['title'])) ?>')">

                    <i class="fas fa-trash"></i> Delete

                </button>

                <?php endif; ?>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

    <?php endif; ?>



</main>

</div>



<!-- Delete confirmation modal -->

<div class="modal-overlay" id="deleteModal">

    <div class="modal-box">

        <div class="modal-icon">🗑️</div>

        <h3>Delete Election?</h3>

        <p id="deleteModalMsg">This will permanently delete the election and all its votes. This cannot be undone.</p>

        <div class="modal-actions">

            <button class="btn-cancel" onclick="closeModal()">Cancel</button>

            <form method="POST" style="display:inline;">

                <input type="hidden" name="delete_election_id" id="deleteElectionId">

                <button type="submit" class="btn-confirm-delete"><i class="fas fa-trash"></i> Yes, Delete</button>

            </form>

        </div>

    </div>

</div>



<script>

function filterCards(status, btn) {

    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));

    btn.classList.add('active');

    document.querySelectorAll('.elec-card').forEach(card => {

        card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';

    });

}



function confirmDelete(id, title) {

    document.getElementById('deleteElectionId').value = id;

    document.getElementById('deleteModalMsg').textContent =

        'Delete "' + title + '"? This will permanently remove all votes and candidates. Cannot be undone.';

    document.getElementById('deleteModal').classList.add('show');

}



function closeModal() {

    document.getElementById('deleteModal').classList.remove('show');

}



document.getElementById('deleteModal').addEventListener('click', function(e) {

    if (e.target === this) closeModal();

});



// Animate bars

window.addEventListener('load', () => {

    document.querySelectorAll('.cand-bar-fill').forEach(bar => {

        const w = bar.style.width;

        bar.style.width = '0%';

        setTimeout(() => { bar.style.width = w; }, 400);

    });

});

</script>

</body>

</html>

