<?php
/**
 * IUC Voting System - Report (Dynamic)
 */

// ── Data ───────────────────────────────────────────────────────────────────
$elections = $pdo->query("
    SELECT e.id, e.title, e.status, e.start_date, e.end_date, e.created_at,
           COUNT(DISTINCT v.id)  AS total_votes,
           COUNT(DISTINCT c.id)  AS total_candidates
    FROM elections e
    LEFT JOIN votes      v ON v.election_id = e.id
    LEFT JOIN candidates c ON c.election_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalStudents  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student'")->fetchColumn();
$approvedStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'")->fetchColumn();
$eligibleVoters = $approvedStudents;
$turnout        = $eligibleVoters > 0 ? round($totalVotes / $eligibleVoters * 100, 1) : 0;

// Per-election candidate results
$electionResults = [];
foreach ($elections as $e) {
    $stmt = $pdo->prepare("
        SELECT c.name, COUNT(v.id) AS votes
        FROM candidates c
        LEFT JOIN votes v ON v.candidate_id=c.id AND v.election_id=?
        WHERE c.election_id=?
        GROUP BY c.id ORDER BY votes DESC
    ");
    $stmt->execute([$e['id'], $e['id']]);
    $electionResults[$e['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $elecId = (int)($_GET['election_id'] ?? 0);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="election_report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($elecId > 0 && isset($electionResults[$elecId])) {
        $elec = array_filter($elections, fn($e) => $e['id'] == $elecId);
        $elec = reset($elec);
        fputcsv($out, ['Election Report: ' . $elec['title']]);
        fputcsv($out, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($out, []);
        fputcsv($out, ['Rank','Candidate','Votes','Percentage']);
        $tv = array_sum(array_column($electionResults[$elecId], 'votes'));
        foreach ($electionResults[$elecId] as $i => $c) {
            $pct = $tv > 0 ? round($c['votes']/$tv*100,1) : 0;
            fputcsv($out, [$i+1, $c['name'], $c['votes'], $pct.'%']);
        }
        fputcsv($out, []);
        fputcsv($out, ['Total Votes', $tv]);
    } else {
        fputcsv($out, ['Full System Report']);
        fputcsv($out, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($out, []);
        fputcsv($out, ['Election','Status','Start','End','Total Votes','Candidates']);
        foreach ($elections as $e) {
            fputcsv($out, [$e['title'],$e['status'],$e['start_date'],$e['end_date'],$e['total_votes'],$e['total_candidates']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Summary']);
        fputcsv($out, ['Total Elections', count($elections)]);
        fputcsv($out, ['Total Votes', $totalVotes]);
        fputcsv($out, ['Eligible Voters', $eligibleVoters]);
        fputcsv($out, ['Overall Turnout', $turnout.'%']);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report - IUC Voting System</title>
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
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.sc{background:#fff;border-radius:12px;padding:1.1rem 1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;align-items:center;gap:.9rem;border-left:4px solid #3282B8}
.sc.g{border-left-color:#10b981}.sc.o{border-left-color:#f59e0b}.sc.p{border-left-color:#8b5cf6}
.sc-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff;flex-shrink:0}
.sc.g .sc-icon{background:linear-gradient(135deg,#10b981,#059669)}
.sc.o .sc-icon{background:linear-gradient(135deg,#f59e0b,#d97706)}
.sc.p .sc-icon{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}
.sc-num{font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1}
.sc-lbl{font-size:.72rem;color:#64748b;margin-top:.15rem}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:space-between;gap:.5rem}
.card-head-l{display:flex;align-items:center;gap:.5rem}
.btn{padding:.55rem 1.1rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;font-size:.82rem;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(50,130,184,.3)}
.btn-g{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.btn-g:hover{transform:translateY(-1px)}
table{width:100%;border-collapse:collapse;font-size:.83rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap}
td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151}
tr:hover td{background:#f8fafc}
.badge{padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.b-active{background:#dcfce7;color:#166534}
.b-draft{background:#fef9c3;color:#854d0e}
.b-completed,.b-closed{background:#e0e7ff;color:#3730a3}
.b-upcoming{background:#dbeafe;color:#1e40af}
.bar-track{height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;margin-top:.3rem}
.bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#3282B8,#0B3C5D)}
.bar-fill.gold{background:linear-gradient(90deg,#f59e0b,#d97706)}
.empty{text-align:center;padding:2rem;color:#94a3b8}
.empty i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-file-alt"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">System</div>
    <a href="index.php?page=settings" class="sb-a"><i class="fas fa-cog"></i> Settings</a>
    <a href="index.php?page=report" class="sb-a active"><i class="fas fa-file-alt"></i> Report</a>
    <a href="index.php?page=backup" class="sb-a"><i class="fas fa-database"></i> Backup</a>
    <a href="index.php?page=complain" class="sb-a"><i class="fas fa-comment-alt"></i> Complaints</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <div class="page-hdr">
        <div>
            <h1><i class="fas fa-file-alt" style="color:#3282B8"></i> Reports</h1>
            <p>Full election reports with export to CSV</p>
        </div>
        <a href="index.php?page=report&export=csv" class="btn btn-g"><i class="fas fa-download"></i> Export All (CSV)</a>
    </div>

    <div class="stats-grid">
        <div class="sc"><div class="sc-icon"><i class="fas fa-vote-yea"></i></div><div><div class="sc-num"><?= count($elections) ?></div><div class="sc-lbl">Total Elections</div></div></div>
        <div class="sc g"><div class="sc-icon"><i class="fas fa-ballot-check"></i></div><div><div class="sc-num"><?= number_format($totalVotes) ?></div><div class="sc-lbl">Total Votes</div></div></div>
        <div class="sc o"><div class="sc-icon"><i class="fas fa-users"></i></div><div><div class="sc-num"><?= $eligibleVoters ?></div><div class="sc-lbl">Eligible Voters</div></div></div>
        <div class="sc p"><div class="sc-icon"><i class="fas fa-percentage"></i></div><div><div class="sc-num"><?= $turnout ?>%</div><div class="sc-lbl">Overall Turnout</div></div></div>
    </div>

    <!-- Elections summary table -->
    <div class="card">
        <div class="card-head">
            <div class="card-head-l"><i class="fas fa-table" style="color:#3282B8"></i> All Elections Summary</div>
        </div>
        <?php if (empty($elections)): ?>
            <div class="empty"><i class="fas fa-vote-yea"></i>No elections created yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>Election</th><th>Status</th><th>Start</th><th>End</th><th>Votes</th><th>Candidates</th><th>Turnout</th><th>Export</th></tr></thead>
            <tbody>
            <?php foreach ($elections as $i => $e):
                $et = $eligibleVoters > 0 ? round($e['total_votes']/$eligibleVoters*100,1) : 0;
            ?>
            <tr>
                <td style="color:#94a3b8;"><?= $i+1 ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($e['title']) ?></td>
                <td><span class="badge b-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
                <td style="color:#64748b;"><?= date('M d, Y', strtotime($e['start_date'])) ?></td>
                <td style="color:#64748b;"><?= date('M d, Y', strtotime($e['end_date'])) ?></td>
                <td style="font-weight:700;color:#0B3C5D;"><?= $e['total_votes'] ?></td>
                <td style="text-align:center;"><?= $e['total_candidates'] ?></td>
                <td><?= $et ?>%</td>
                <td><a href="index.php?page=report&export=csv&election_id=<?= $e['id'] ?>" class="btn btn-g" style="font-size:.75rem;padding:.3rem .7rem;"><i class="fas fa-download"></i> CSV</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Per-election detailed results -->
    <?php foreach ($elections as $e):
        $cands = $electionResults[$e['id']];
        $tv = array_sum(array_column($cands, 'votes'));
    ?>
    <div class="card">
        <div class="card-head">
            <div class="card-head-l"><i class="fas fa-chart-bar" style="color:#8b5cf6"></i> <?= htmlspecialchars($e['title']) ?></div>
            <div style="display:flex;align-items:center;gap:.75rem;">
                <span class="badge b-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span>
                <a href="index.php?page=report&export=csv&election_id=<?= $e['id'] ?>" class="btn btn-g" style="font-size:.75rem;padding:.3rem .7rem;"><i class="fas fa-download"></i> CSV</a>
            </div>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            <?php if (empty($cands) || $tv == 0): ?>
                <div class="empty"><i class="fas fa-hourglass-half"></i>No votes cast yet.</div>
            <?php else: ?>
                <?php foreach ($cands as $ci => $c):
                    $pct = $tv > 0 ? round($c['votes']/$tv*100,1) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.9rem;">
                    <div style="width:28px;height:28px;border-radius:50%;background:<?= $ci===0?'linear-gradient(135deg,#f59e0b,#d97706)':'linear-gradient(135deg,#94a3b8,#64748b)' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;"><?= $ci+1 ?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.85rem;font-weight:600;color:#1e293b;margin-bottom:.25rem;"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="bar-track"><div class="bar-fill <?= $ci===0?'gold':'' ?>" style="width:<?= $pct ?>%"></div></div>
                    </div>
                    <div style="font-size:.82rem;color:#64748b;width:80px;text-align:right;"><?= $c['votes'] ?> votes (<?= $pct ?>%)</div>
                </div>
                <?php endforeach; ?>
                <div style="font-size:.8rem;color:#64748b;margin-top:.5rem;">Total: <?= $tv ?> votes</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</main>
</div>
</body>
</html>
