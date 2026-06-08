<?php
$dbOk=false;$dbVersion='Unknown';$dbSize=0;$dbTables=0;
try{$dbVersion=$pdo->query("SELECT VERSION()")->fetchColumn();$dbOk=true;$dbSize=(float)$pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn();$dbTables=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn();}catch(Exception $e){}
$counts=[];foreach(['users','students','elections','candidates','votes','blockchain_transactions'] as $t){try{$counts[$t]=(int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();}catch(Exception $e){$counts[$t]=0;}}
$phpVersion=PHP_VERSION;$serverSoft=$_SERVER['SERVER_SOFTWARE']??'Unknown';$serverOs=PHP_OS;$maxMemory=ini_get('memory_limit');$uploadMax=ini_get('upload_max_filesize');$execTime=ini_get('max_execution_time');$memUsage=round(memory_get_usage(true)/1024/1024,2);$memPeak=round(memory_get_peak_usage(true)/1024/1024,2);
$diskFree=round(disk_free_space(__DIR__)/1024/1024/1024,2);$diskTotal=round(disk_total_space(__DIR__)/1024/1024/1024,2);$diskUsed=round($diskTotal-$diskFree,2);$diskPct=$diskTotal>0?round($diskUsed/$diskTotal*100):0;
$extStatus=[];foreach(['pdo','pdo_mysql','json','mbstring','openssl','curl'] as $ext){$extStatus[$ext]=extension_loaded($ext);}
$lastVotes=$pdo->query("SELECT v.created_at,u.name AS voter,e.title AS election FROM votes v JOIN users u ON v.user_id=u.id JOIN elections e ON v.election_id=e.id ORDER BY v.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$secChecks=['display_errors OFF'=>ini_get('display_errors')=='0','Session active'=>session_status()===PHP_SESSION_ACTIVE,'PDO MySQL loaded'=>extension_loaded('pdo_mysql'),'OpenSSL loaded'=>extension_loaded('openssl'),'DB connection OK'=>$pdo!==null];
$now=new DateTime();
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>System Status</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,sans-serif;background:#f0f4f8;color:#1e293b}.layout{display:flex;min-height:100vh}.sidebar{width:260px;background:linear-gradient(160deg,#0B3C5D,#3282B8);color:#fff;position:fixed;height:100vh;overflow-y:auto;z-index:100}.sb-brand{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:.75rem}.sb-logo{width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}.sb-brand h1{font-size:.95rem;font-weight:700}.sb-brand p{font-size:.72rem;opacity:.65}.sb-sec{font-size:.65rem;text-transform:uppercase;opacity:.5;padding:.8rem 1.5rem .2rem;font-weight:600}.sb-a{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.5rem;color:rgba(255,255,255,.75);text-decoration:none;font-size:.85rem;border-left:3px solid transparent;transition:all .2s}.sb-a:hover{background:rgba(255,255,255,.1);color:#fff}.sb-a.active{background:rgba(255,255,255,.15);color:#fff;border-left-color:#BBE1FA;font-weight:600}.sb-a i{width:16px;text-align:center}.main{flex:1;margin-left:260px;padding:2rem}.hdr{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}.hdr h1{font-size:1.5rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}.hdr p{color:#64748b;font-size:.875rem;margin-top:.25rem}.ob{display:inline-flex;align-items:center;gap:.4rem;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:600}.od{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 2s infinite}@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}.sr{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}.sc{background:#fff;border-radius:12px;padding:1.1rem;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center;border-top:4px solid #10b981}.sc.w{border-top-color:#f59e0b}.sc.i{border-top-color:#3282B8}.si{font-size:1.8rem;margin-bottom:.4rem}.sl{font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:.3rem}.sv{font-size:.95rem;font-weight:700;color:#1e293b}.bok{background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:8px;font-size:.72rem;font-weight:700;display:inline-block}.bw{background:#fef9c3;color:#854d0e;padding:.2rem .6rem;border-radius:8px;font-size:.72rem;font-weight:700;display:inline-block}.g2{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}.ch{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem}.cb{padding:1.25rem 1.5rem}.ir{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f8fafc;font-size:.85rem}.ir:last-child{border-bottom:none}.ik{color:#64748b}.iv{font-weight:600;color:#1e293b;text-align:right}.pw{margin-bottom:1rem}.pw:last-child{margin-bottom:0}.pl{display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:.3rem}.pt{height:10px;background:#f1f5f9;border-radius:5px;overflow:hidden}.pf{height:100%;border-radius:5px}.pfg{background:linear-gradient(90deg,#10b981,#059669)}.pfb{background:linear-gradient(90deg,#3282B8,#0B3C5D)}.pfo{background:linear-gradient(90deg,#f59e0b,#d97706)}.pfr{background:linear-gradient(90deg,#ef4444,#dc2626)}.dg{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.75rem}.db{background:#f8fafc;border-radius:8px;padding:.75rem;text-align:center;border:1px solid #e2e8f0}.dn{font-size:1.4rem;font-weight:800;color:#0B3C5D}.dl{font-size:.72rem;color:#64748b;margin-top:.2rem;text-transform:capitalize}.eg{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.5rem}.ei{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;border-radius:8px;font-size:.82rem;font-weight:600}.eok{background:#dcfce7;color:#166534}.em{background:#fee2e2;color:#991b1b}.ci{display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f8fafc;font-size:.85rem}.ci:last-child{border-bottom:none}.ai{display:flex;align-items:flex-start;gap:.75rem;padding:.55rem 0;border-bottom:1px solid #f8fafc;font-size:.82rem}.ai:last-child{border-bottom:none}.ad{width:8px;height:8px;background:#10b981;border-radius:50%;margin-top:.3rem;flex-shrink:0}.at{flex:1;color:#374151}.am{font-size:.72rem;color:#94a3b8;white-space:nowrap}.ea{text-align:center;padding:1.5rem;color:#94a3b8;font-size:.85rem}@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}</style></head>
<body><div class="layout">
<aside class="sidebar"><div class="sb-brand"><div class="sb-logo"><i class="fas fa-server"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
<div class="sb-sec">Main</div><a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<div class="sb-sec">Elections</div><a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a><a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
<div class="sb-sec">Blockchain</div><a href="index.php?page=blockchain_explorer" class="sb-a"><i class="fas fa-link"></i> Blockchain Explorer</a><a href="index.php?page=transaction_monitor" class="sb-a"><i class="fas fa-exchange-alt"></i> Transaction Monitor</a><a href="index.php?page=node_status" class="sb-a active"><i class="fas fa-server"></i> Node Status</a>
<div class="sb-sec">Voters</div><a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>
<a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a></aside>
<main class="main">
<div class="hdr"><div><h1><i class="fas fa-server" style="color:#3282B8"></i> System Status</h1><p>Live server, database and application health  <?= $now->format('M d, Y H:i:s') ?></p></div><div class="ob"><div class="od"></div> OPERATIONAL</div></div>
<div class="sr">
<div class="sc <?= $dbOk?'':'w' ?>"><div class="si"></div><div class="sl">Database</div><div><span class="<?= $dbOk?'bok':'bw' ?>"><?= $dbOk?'ONLINE':'ERROR' ?></span></div></div>
<div class="sc i"><div class="si"></div><div class="sl">PHP Version</div><div class="sv"><?= $phpVersion ?></div></div>
<div class="sc i"><div class="si"></div><div class="sl">Disk Used</div><div class="sv"><?= $diskUsed ?>/<?= $diskTotal ?> GB</div></div>
<div class="sc"><div class="si"></div><div class="sl">Memory</div><div class="sv"><?= $memUsage ?> MB</div></div>
<div class="sc i"><div class="si"></div><div class="sl">Server Time</div><div class="sv"><?= $now->format('H:i:s') ?></div></div>
</div>
<div class="g2">
<div class="card"><div class="ch"><i class="fas fa-database" style="color:#3282B8"></i> Database</div><div class="cb">
<div class="ir"><span class="ik">MySQL Version</span><span class="iv"><?= htmlspecialchars($dbVersion) ?></span></div>
<div class="ir"><span class="ik">Database Name</span><span class="iv">iuc_voting_system</span></div>
<div class="ir"><span class="ik">Database Size</span><span class="iv"><?= $dbSize ?> MB</span></div>
<div class="ir"><span class="ik">Total Tables</span><span class="iv"><?= $dbTables ?></span></div>
<div class="ir"><span class="ik">Host</span><span class="iv">localhost</span></div>
</div></div>
<div class="card"><div class="ch"><i class="fas fa-server" style="color:#8b5cf6"></i> Server</div><div class="cb">
<div class="ir"><span class="ik">Web Server</span><span class="iv" style="font-size:.78rem;"><?= htmlspecialchars($serverSoft) ?></span></div>
<div class="ir"><span class="ik">OS</span><span class="iv"><?= htmlspecialchars($serverOs) ?></span></div>
<div class="ir"><span class="ik">Memory Limit</span><span class="iv"><?= $maxMemory ?></span></div>
<div class="ir"><span class="ik">Memory Used</span><span class="iv"><?= $memUsage ?> MB (peak <?= $memPeak ?> MB)</span></div>
<div class="ir"><span class="ik">Max Upload</span><span class="iv"><?= $uploadMax ?></span></div>
<div class="ir"><span class="ik">Max Exec Time</span><span class="iv"><?= $execTime ?>s</span></div>
</div></div>
</div>
<div class="card"><div class="ch"><i class="fas fa-hdd" style="color:#f59e0b"></i> Disk & Memory</div><div class="cb">
<div class="pw"><div class="pl"><span>Disk Space</span><span><?= $diskUsed ?> GB / <?= $diskTotal ?> GB (<?= $diskPct ?>%)</span></div><div class="pt"><div class="pf <?= $diskPct>80?'pfr':($diskPct>60?'pfo':'pfb') ?>" style="width:<?= $diskPct ?>%"></div></div></div>
<?php $mp=0;preg_match('/(\d+)/',$maxMemory,$m);if(!empty($m[1])&&(int)$m[1]>0){$mp=min(100,round($memUsage/(int)$m[1]*100));} ?>
<div class="pw"><div class="pl"><span>PHP Memory</span><span><?= $memUsage ?> MB (<?= $mp ?>%)</span></div><div class="pt"><div class="pf <?= $mp>80?'pfr':'pfg' ?>" style="width:<?= $mp ?>%"></div></div></div>
</div></div>
<div class="card"><div class="ch"><i class="fas fa-table" style="color:#10b981"></i> Database Records</div><div class="cb">
<div class="dg"><?php foreach($counts as $t=>$c): ?><div class="db"><div class="dn"><?= number_format($c) ?></div><div class="dl"><?= str_replace('_',' ',$t) ?></div></div><?php endforeach; ?></div>
</div></div>
<div class="g2">
<div class="card"><div class="ch"><i class="fas fa-puzzle-piece" style="color:#3282B8"></i> PHP Extensions</div><div class="cb">
<div class="eg"><?php foreach($extStatus as $ext=>$loaded): ?><div class="ei <?= $loaded?'eok':'em' ?>"><i class="fas fa-<?= $loaded?'check':'times' ?>-circle"></i><?= $ext ?></div><?php endforeach; ?></div>
</div></div>
<div class="card"><div class="ch"><i class="fas fa-history" style="color:#10b981"></i> Recent Activity</div><div class="cb">
<?php if(empty($lastVotes)): ?><div class="ea"><i class="fas fa-vote-yea" style="font-size:1.5rem;display:block;margin-bottom:.4rem;opacity:.3;"></i>No votes yet.</div>
<?php else: foreach($lastVotes as $v): ?>
<div class="ai"><div class="ad"></div><div class="at"><strong><?= htmlspecialchars($v['voter']) ?></strong> voted in <span style="color:#3282B8;"><?= htmlspecialchars($v['election']) ?></span></div><div class="am"><?= date('H:i:s',strtotime($v['created_at'])) ?></div></div>
<?php endforeach; endif; ?>
</div></div>
</div>
<div class="card"><div class="ch"><i class="fas fa-tasks" style="color:#3282B8"></i> Security Checks</div><div class="cb">
<?php foreach($secChecks as $label=>$pass): ?><div class="ci"><span><?= $label ?></span><span class="<?= $pass?'bok':'bw' ?>"><?= $pass?'PASS':'CHECK' ?></span></div><?php endforeach; ?>
</div></div>
<div class="card"><div class="ch"><i class="fas fa-info-circle" style="color:#3282B8"></i> Application Info</div><div class="cb">
<div class="ir"><span class="ik">System Name</span><span class="iv">IUC Voting System</span></div>
<div class="ir"><span class="ik">Version</span><span class="iv">1.0.0</span></div>
<div class="ir"><span class="ik">Blockchain Mode</span><span class="iv"><span class="bw">Mock (Disabled)</span></span></div>
<div class="ir"><span class="ik">Timezone</span><span class="iv"><?= date_default_timezone_get() ?></span></div>
<div class="ir"><span class="ik">Current Admin</span><span class="iv"><?= htmlspecialchars($_SESSION['user_name']??'Unknown') ?></span></div>
</div></div>
<p style="text-align:center;color:#94a3b8;font-size:.78rem;margin-top:1rem;"><i class="fas fa-sync-alt"></i> Auto-refreshes every 60 seconds</p>
</main></div>
<script>window.addEventListener('load',()=>{document.querySelectorAll('.pf').forEach(b=>{const w=b.style.width;b.style.width='0%';setTimeout(()=>{b.style.width=w;},300);});});setTimeout(()=>location.reload(),60000);</script>
</body></html>
