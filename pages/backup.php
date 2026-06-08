<?php
/**
 * IUC Voting System - Backup (Dynamic)
 */

$msg = '';
$msgType = 'success';

// Handle JSON backup download
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $backup = [
        'generated_at' => date('Y-m-d H:i:s'),
        'system'       => 'IUC Voting System',
        'elections'    => $pdo->query("SELECT * FROM elections")->fetchAll(PDO::FETCH_ASSOC),
        'candidates'   => $pdo->query("SELECT * FROM candidates")->fetchAll(PDO::FETCH_ASSOC),
        'votes'        => $pdo->query("SELECT * FROM votes")->fetchAll(PDO::FETCH_ASSOC),
        'users'        => $pdo->query("SELECT id,name,email,type,status,created_at FROM users")->fetchAll(PDO::FETCH_ASSOC),
        'students'     => $pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC),
        'settings'     => $pdo->query("SELECT setting_key,setting_value FROM system_settings")->fetchAll(PDO::FETCH_ASSOC),
    ];
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="iuc_backup_' . date('Ymd_His') . '.json"');
    echo json_encode($backup, JSON_PRETTY_PRINT);
    exit;
}

// Handle SQL backup download
if (isset($_GET['export']) && $_GET['export'] === 'sql') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="iuc_backup_' . date('Ymd_His') . '.sql"');

    $tables = ['elections','candidates','votes','users','students','system_settings','activity_logs'];
    echo "-- IUC Voting System Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        try {
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "-- Table: $table (" . count($rows) . " rows)\n";
            if (!empty($rows)) {
                $cols = implode('`, `', array_keys($rows[0]));
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    echo "INSERT INTO `$table` (`$cols`) VALUES (" . implode(', ', $vals) . ");\n";
                }
            }
            echo "\n";
        } catch (Exception $e) {
            echo "-- Error backing up $table: " . $e->getMessage() . "\n\n";
        }
    }
    exit;
}

// Stats
$counts = [];
foreach (['elections','candidates','votes','users','students','activity_logs','notifications'] as $t) {
    try { $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn(); }
    catch (Exception $e) { $counts[$t] = 0; }
}

$dbSize = (float)$pdo->query("
    SELECT ROUND(SUM(data_length+index_length)/1024/1024,2)
    FROM information_schema.tables WHERE table_schema=DATABASE()
")->fetchColumn();

$diskFree  = round(disk_free_space(__DIR__)/1024/1024/1024,2);
$diskTotal = round(disk_total_space(__DIR__)/1024/1024/1024,2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Backup - IUC Voting System</title>
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
.page-hdr{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem}
.page-hdr h1{font-size:1.5rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}
.page-hdr p{color:#64748b;font-size:.875rem;margin-top:.25rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:900px){.grid-2{grid-template-columns:1fr}}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}
.card-body{padding:1.5rem}
.backup-btn{display:flex;align-items:center;gap:1rem;padding:1.25rem;background:#f8fafc;border-radius:10px;border:2px solid #e2e8f0;text-decoration:none;transition:all .2s;margin-bottom:1rem}
.backup-btn:last-child{margin-bottom:0}
.backup-btn:hover{border-color:#3282B8;background:#eff6ff;transform:translateX(4px)}
.backup-icon{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.bi-json{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.bi-sql{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.backup-info h3{font-size:.95rem;font-weight:700;color:#1e293b;margin-bottom:.2rem}
.backup-info p{font-size:.8rem;color:#64748b}
.db-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:.75rem}
.db-box{background:#f8fafc;border-radius:8px;padding:.75rem;text-align:center;border:1px solid #e2e8f0}
.db-num{font-size:1.4rem;font-weight:800;color:#0B3C5D}
.db-lbl{font-size:.7rem;color:#64748b;margin-top:.2rem;text-transform:capitalize}
.info-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f8fafc;font-size:.85rem}
.info-row:last-child{border-bottom:none}
.info-key{color:#64748b}.info-val{font-weight:600}
.warn-box{background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:.75rem;font-size:.85rem;color:#854d0e;margin-bottom:1.5rem}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-database"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">System</div>
    <a href="index.php?page=settings" class="sb-a"><i class="fas fa-cog"></i> Settings</a>
    <a href="index.php?page=report" class="sb-a"><i class="fas fa-file-alt"></i> Report</a>
    <a href="index.php?page=backup" class="sb-a active"><i class="fas fa-database"></i> Backup</a>
    <a href="index.php?page=complain" class="sb-a"><i class="fas fa-comment-alt"></i> Complaints</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <div class="page-hdr">
        <h1><i class="fas fa-database" style="color:#3282B8"></i> Backup</h1>
        <p>Download a full backup of all system data</p>
    </div>

    <div class="warn-box">
        <i class="fas fa-exclamation-triangle" style="font-size:1.2rem;flex-shrink:0;margin-top:.1rem;"></i>
        <div><strong>Important:</strong> Store backups securely. They contain all user data, votes, and election results. Do not share backup files publicly.</div>
    </div>

    <div class="grid-2">
        <!-- Download options -->
        <div class="card">
            <div class="card-head"><i class="fas fa-download" style="color:#3282B8"></i> Download Backup</div>
            <div class="card-body">
                <a href="index.php?page=backup&export=json" class="backup-btn">
                    <div class="backup-icon bi-json"><i class="fas fa-file-code"></i></div>
                    <div class="backup-info">
                        <h3>JSON Backup</h3>
                        <p>All tables exported as structured JSON — easy to read and import</p>
                    </div>
                    <i class="fas fa-download" style="color:#3282B8;margin-left:auto;"></i>
                </a>
                <a href="index.php?page=backup&export=sql" class="backup-btn">
                    <div class="backup-icon bi-sql"><i class="fas fa-database"></i></div>
                    <div class="backup-info">
                        <h3>SQL Backup</h3>
                        <p>INSERT statements for all tables — can be imported directly into MySQL</p>
                    </div>
                    <i class="fas fa-download" style="color:#3282B8;margin-left:auto;"></i>
                </a>
            </div>
        </div>

        <!-- Database info -->
        <div class="card">
            <div class="card-head"><i class="fas fa-info-circle" style="color:#10b981"></i> Database Info</div>
            <div class="card-body">
                <div class="info-row"><span class="info-key">Database Size</span><span class="info-val"><?= $dbSize ?> MB</span></div>
                <div class="info-row"><span class="info-key">Disk Free</span><span class="info-val"><?= $diskFree ?> GB</span></div>
                <div class="info-row"><span class="info-key">Disk Total</span><span class="info-val"><?= $diskTotal ?> GB</span></div>
                <div class="info-row"><span class="info-key">Backup Time</span><span class="info-val"><?= date('M d, Y H:i:s') ?></span></div>
                <div class="info-row"><span class="info-key">MySQL Version</span><span class="info-val"><?= $pdo->query("SELECT VERSION()")->fetchColumn() ?></span></div>
            </div>
        </div>
    </div>

    <!-- Record counts -->
    <div class="card">
        <div class="card-head"><i class="fas fa-table" style="color:#8b5cf6"></i> Records to be Backed Up</div>
        <div class="card-body">
            <div class="db-grid">
                <?php foreach ($counts as $table => $count): ?>
                <div class="db-box">
                    <div class="db-num"><?= number_format($count) ?></div>
                    <div class="db-lbl"><?= str_replace('_',' ',$table) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
</div>
</body>
</html>
