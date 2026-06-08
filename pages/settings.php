<?php
/**
 * IUC Voting System - Settings (Dynamic)
 */

$msg = '';
$msgType = 'success';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $fields = ['system_name','blockchain_enabled','max_elections','voting_timeout','email_notifications'];
    foreach ($fields as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            $check = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key=?");
            $check->execute([$key]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE system_settings SET setting_value=?, updated_by=? WHERE setting_key=?")
                    ->execute([$val, $_SESSION['user_id'], $key]);
            } else {
                $pdo->prepare("INSERT INTO system_settings (setting_key,setting_value,updated_by) VALUES (?,?,?)")
                    ->execute([$key, $val, $_SESSION['user_id']]);
            }
        }
    }
    // Log
    $pdo->prepare("INSERT INTO activity_logs (user_id,action,details,ip_address,created_at) VALUES (?,?,?,?,NOW())")
        ->execute([$_SESSION['user_id'],'SETTINGS_UPDATED','System settings updated',$_SERVER['REMOTE_ADDR']??'']);
    $msg = 'Settings saved successfully.';
}

// Load current settings
$rawSettings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$s = array_merge([
    'system_name'          => 'IUC Voting System',
    'blockchain_enabled'   => 'false',
    'max_elections'        => '10',
    'voting_timeout'       => '300',
    'email_notifications'  => 'true',
], $rawSettings);

// System info
$phpVersion  = PHP_VERSION;
$serverSoft  = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$dbVersion   = $pdo->query("SELECT VERSION()")->fetchColumn();
$timezone    = date_default_timezone_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - IUC Voting System</title>
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
.toast{padding:.85rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;font-weight:600;display:flex;align-items:center;gap:.5rem}
.toast.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
@media(max-width:900px){.grid-2{grid-template-columns:1fr}}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}
.card-body{padding:1.5rem}
.form-group{margin-bottom:1.25rem}
.form-group:last-child{margin-bottom:0}
.form-group label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.4rem}
.form-group input,.form-group select{width:100%;padding:.65rem .9rem;border:2px solid #e2e8f0;border-radius:8px;font-size:.9rem;color:#1e293b;background:#fff;transition:border-color .2s}
.form-group input:focus,.form-group select:focus{outline:none;border-color:#3282B8}
.form-group .hint{font-size:.75rem;color:#94a3b8;margin-top:.3rem}
.btn{padding:.65rem 1.5rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;font-size:.9rem;transition:all .2s}
.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(50,130,184,.3)}
.info-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f8fafc;font-size:.85rem}
.info-row:last-child{border-bottom:none}
.info-key{color:#64748b}.info-val{font-weight:600;color:#1e293b}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-cog"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">System</div>
    <a href="index.php?page=settings" class="sb-a active"><i class="fas fa-cog"></i> Settings</a>
    <a href="index.php?page=report" class="sb-a"><i class="fas fa-file-alt"></i> Report</a>
    <a href="index.php?page=backup" class="sb-a"><i class="fas fa-database"></i> Backup</a>
    <a href="index.php?page=complain" class="sb-a"><i class="fas fa-comment-alt"></i> Complaints</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <div class="page-hdr">
        <h1><i class="fas fa-cog" style="color:#3282B8"></i> System Settings</h1>
        <p>Configure system behaviour and preferences</p>
    </div>

    <?php if ($msg): ?>
    <div class="toast success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
    <input type="hidden" name="save_settings" value="1">
    <div class="grid-2">
        <!-- General settings -->
        <div class="card">
            <div class="card-head"><i class="fas fa-sliders-h" style="color:#3282B8"></i> General</div>
            <div class="card-body">
                <div class="form-group">
                    <label>System Name</label>
                    <input type="text" name="system_name" value="<?= htmlspecialchars($s['system_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Max Concurrent Elections</label>
                    <input type="number" name="max_elections" value="<?= htmlspecialchars($s['max_elections']) ?>" min="1" max="50">
                    <div class="hint">Maximum number of elections that can run at the same time</div>
                </div>
                <div class="form-group">
                    <label>Voting Session Timeout (seconds)</label>
                    <input type="number" name="voting_timeout" value="<?= htmlspecialchars($s['voting_timeout']) ?>" min="60">
                    <div class="hint">How long a voting session stays active (default: 300 = 5 minutes)</div>
                </div>
            </div>
        </div>

        <!-- Feature toggles -->
        <div class="card">
            <div class="card-head"><i class="fas fa-toggle-on" style="color:#10b981"></i> Features</div>
            <div class="card-body">
                <div class="form-group">
                    <label>Blockchain Integration</label>
                    <select name="blockchain_enabled">
                        <option value="false" <?= $s['blockchain_enabled']==='false'?'selected':'' ?>>Disabled (Mock mode)</option>
                        <option value="true"  <?= $s['blockchain_enabled']==='true'?'selected':'' ?>>Enabled (Real blockchain)</option>
                    </select>
                    <div class="hint">Currently running in mock mode — votes are recorded in DB only</div>
                </div>
                <div class="form-group">
                    <label>Email Notifications</label>
                    <select name="email_notifications">
                        <option value="true"  <?= $s['email_notifications']==='true'?'selected':'' ?>>Enabled</option>
                        <option value="false" <?= $s['email_notifications']==='false'?'selected':'' ?>>Disabled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-p"><i class="fas fa-save"></i> Save Settings</button>
    </form>

    <!-- System info (read-only) -->
    <div class="card" style="margin-top:1.5rem;">
        <div class="card-head"><i class="fas fa-info-circle" style="color:#64748b"></i> System Information (Read-only)</div>
        <div class="card-body">
            <div class="info-row"><span class="info-key">PHP Version</span><span class="info-val"><?= $phpVersion ?></span></div>
            <div class="info-row"><span class="info-key">MySQL Version</span><span class="info-val"><?= htmlspecialchars($dbVersion) ?></span></div>
            <div class="info-row"><span class="info-key">Web Server</span><span class="info-val"><?= htmlspecialchars($serverSoft) ?></span></div>
            <div class="info-row"><span class="info-key">Timezone</span><span class="info-val"><?= $timezone ?></span></div>
            <div class="info-row"><span class="info-key">Server Time</span><span class="info-val"><?= date('M d, Y H:i:s') ?></span></div>
        </div>
    </div>
</main>
</div>
</body>
</html>
