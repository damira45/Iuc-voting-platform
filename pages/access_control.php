<?php
/**
 * IUC Voting System - Access Control (Dynamic)
 */

// ── Data ───────────────────────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Stats
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='admin'")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student'")->fetchColumn();
$approved      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='approved'")->fetchColumn();
$pending       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$rejected      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='rejected'")->fetchColumn();

// Build user query
$where  = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.student_id LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($roleFilter !== '') {
    $where .= " AND u.type = ?";
    $params[] = $roleFilter;
}
if ($statusFilter !== '') {
    $where .= " AND u.status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.type, u.status, u.created_at,
           s.student_id, s.department, s.level,
           COUNT(DISTINCT v.id) AS votes_cast
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    LEFT JOIN votes    v ON v.user_id = u.id
    $where
    GROUP BY u.id
    ORDER BY u.type ASC, u.status ASC, u.name ASC
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status change (approve/reject)
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($uid > 0 && in_array($action, ['approved','rejected','pending'])) {
        $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$action, $uid]);
        // Log it
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?,?,?,?,NOW())")
            ->execute([$_SESSION['user_id'], 'STATUS_CHANGE', "User ID $uid status changed to $action", $_SERVER['REMOTE_ADDR'] ?? '']);
        $actionMsg = "User status updated to $action.";
        // Refresh
        header("Location: index.php?page=access_control&msg=" . urlencode($actionMsg));
        exit;
    }
}
if (!empty($_GET['msg'])) $actionMsg = htmlspecialchars($_GET['msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Control - IUC Voting System</title>
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
.toast{background:#10b981;color:#fff;padding:.75rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 12px rgba(16,185,129,.3)}
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
.filter-bar{background:#fff;border-radius:12px;padding:1rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end}
.fg{display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:140px}
.fg label{font-size:.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.fg input,.fg select{padding:.55rem .85rem;border:2px solid #e2e8f0;border-radius:8px;font-size:.83rem;color:#1e293b;background:#fff}
.fg input:focus,.fg select:focus{outline:none;border-color:#3282B8}
.btn{padding:.5rem .95rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-p:hover{transform:translateY(-1px)}
.btn-o{background:#fff;border:2px solid #e2e8f0;color:#64748b}
.btn-o:hover{border-color:#3282B8;color:#3282B8}
.btn-approve{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.btn-approve:hover{background:#166534;color:#fff}
.btn-reject{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.btn-reject:hover{background:#dc2626;color:#fff}
.btn-pending{background:#fef9c3;color:#854d0e;border:1px solid #fde68a}
.btn-pending:hover{background:#854d0e;color:#fff}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.9rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:space-between;gap:.5rem}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap}
td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151;vertical-align:middle}
tr:hover td{background:#f8fafc}
.badge{padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.b-approved{background:#dcfce7;color:#166534}
.b-pending{background:#fef9c3;color:#854d0e}
.b-rejected{background:#fee2e2;color:#991b1b}
.b-admin{background:#e0e7ff;color:#3730a3}
.b-student{background:#dbeafe;color:#1e40af}
.empty{text-align:center;padding:2.5rem;color:#94a3b8}
.empty i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
.tbl-foot{padding:.7rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:.78rem;color:#64748b}
/* Confirm modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:2rem;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.2)}
.modal-box h3{font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
.modal-box p{color:#64748b;font-size:.9rem;margin-bottom:1.5rem}
.modal-actions{display:flex;gap:.75rem;justify-content:center}
.btn-cancel{background:#f1f5f9;color:#64748b;padding:.6rem 1.5rem;border:none;border-radius:8px;font-weight:600;cursor:pointer}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-lock"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">Security</div>
    <a href="index.php?page=security_center" class="sb-a"><i class="fas fa-shield-alt"></i> Security Center</a>
    <a href="index.php?page=authentication_logs" class="sb-a"><i class="fas fa-fingerprint"></i> Auth Logs</a>
    <a href="index.php?page=access_control" class="sb-a active"><i class="fas fa-lock"></i> Access Control</a>
    <div class="sb-sec">Voters</div>
    <a href="index.php?page=voter_registration" class="sb-a"><i class="fas fa-user-plus"></i> Register Voters</a>
    <a href="index.php?page=voter_list" class="sb-a"><i class="fas fa-users-cog"></i> Voter List</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<main class="main">
    <div class="page-hdr">
        <div><h1><i class="fas fa-lock" style="color:#3282B8"></i> Access Control</h1><p>Manage user roles, permissions, and account status</p></div>
        <a href="index.php?page=voter_registration" class="btn btn-p"><i class="fas fa-user-plus"></i> Add User</a>
    </div>

    <?php if ($actionMsg): ?>
    <div class="toast"><i class="fas fa-check-circle"></i> <?= $actionMsg ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="sc"><div class="sc-icon"><i class="fas fa-users"></i></div><div><div class="sc-num"><?= $totalUsers ?></div><div class="sc-lbl">Total Users</div></div></div>
        <div class="sc p"><div class="sc-icon"><i class="fas fa-user-shield"></i></div><div><div class="sc-num"><?= $totalAdmins ?></div><div class="sc-lbl">Admins</div></div></div>
        <div class="sc"><div class="sc-icon"><i class="fas fa-user-graduate"></i></div><div><div class="sc-num"><?= $totalStudents ?></div><div class="sc-lbl">Students</div></div></div>
        <div class="sc g"><div class="sc-icon"><i class="fas fa-user-check"></i></div><div><div class="sc-num"><?= $approved ?></div><div class="sc-lbl">Approved</div></div></div>
        <div class="sc o"><div class="sc-icon"><i class="fas fa-clock"></i></div><div><div class="sc-num"><?= $pending ?></div><div class="sc-lbl">Pending</div></div></div>
        <div class="sc r"><div class="sc-icon"><i class="fas fa-user-times"></i></div><div><div class="sc-num"><?= $rejected ?></div><div class="sc-lbl">Rejected</div></div></div>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php" class="filter-bar">
        <input type="hidden" name="page" value="access_control">
        <div class="fg">
            <label>Search</label>
            <input type="text" name="search" placeholder="Name, email or student ID…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="fg">
            <label>Role</label>
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin"   <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
                <option value="student" <?= $roleFilter==='student'?'selected':'' ?>>Student</option>
            </select>
        </div>
        <div class="fg">
            <label>Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Approved</option>
                <option value="pending"  <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
                <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>Rejected</option>
            </select>
        </div>
        <button type="submit" class="btn btn-p"><i class="fas fa-search"></i> Filter</button>
        <?php if ($search || $roleFilter || $statusFilter): ?>
        <a href="index.php?page=access_control" class="btn btn-o"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Users table -->
    <div class="card">
        <div class="card-head">
            <span style="display:flex;align-items:center;gap:.5rem;"><i class="fas fa-users" style="color:#3282B8"></i> Users & Permissions</span>
            <span style="font-size:.78rem;color:#64748b;"><?= count($users) ?> user<?= count($users)!=1?'s':'' ?></span>
        </div>
        <?php if (empty($users)): ?>
            <div class="empty"><i class="fas fa-users"></i>No users match your filters.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Role</th>
                        <th>Student ID</th><th>Department</th><th>Status</th>
                        <th>Votes</th><th>Registered</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td style="color:#94a3b8;"><?= $i+1 ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                    <td style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge b-<?= $u['type'] ?>"><?= ucfirst($u['type']) ?></span></td>
                    <td style="font-family:monospace;color:#3282B8;font-size:.78rem;"><?= htmlspecialchars($u['student_id'] ?? '—') ?></td>
                    <td style="font-size:.78rem;"><?= htmlspecialchars($u['department'] ?? '—') ?></td>
                    <td><span class="badge b-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                    <td style="text-align:center;font-weight:700;color:#0B3C5D;"><?= $u['votes_cast'] ?></td>
                    <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): // Don't allow changing own status ?>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                            <?php if ($u['status'] !== 'approved'): ?>
                            <button onclick="changeStatus(<?= $u['id'] ?>,'approved','<?= htmlspecialchars(addslashes($u['name'])) ?>')" class="btn btn-approve"><i class="fas fa-check"></i> Approve</button>
                            <?php endif; ?>
                            <?php if ($u['status'] !== 'rejected'): ?>
                            <button onclick="changeStatus(<?= $u['id'] ?>,'rejected','<?= htmlspecialchars(addslashes($u['name'])) ?>')" class="btn btn-reject"><i class="fas fa-times"></i> Reject</button>
                            <?php endif; ?>
                            <?php if ($u['status'] !== 'pending'): ?>
                            <button onclick="changeStatus(<?= $u['id'] ?>,'pending','<?= htmlspecialchars(addslashes($u['name'])) ?>')" class="btn btn-pending"><i class="fas fa-clock"></i> Pending</button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.75rem;color:#94a3b8;">Current admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="tbl-foot">Showing <?= count($users) ?> of <?= $totalUsers ?> users &nbsp;|&nbsp; <span style="color:#10b981;"><i class="fas fa-database"></i> Live data</span></div>
        <?php endif; ?>
    </div>
</main>
</div>

<!-- Confirm modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div style="font-size:2.5rem;margin-bottom:.75rem;">⚠️</div>
        <h3 id="modalTitle">Confirm Action</h3>
        <p id="modalMsg">Are you sure?</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <form method="POST" id="confirmForm" style="display:inline;">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="action" id="modalAction">
                <button type="submit" class="btn btn-p" id="modalConfirmBtn">Confirm</button>
            </form>
        </div>
    </div>
</div>

<script>
function changeStatus(id, action, name) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalAction').value = action;
    document.getElementById('modalTitle').textContent = 'Change Status to ' + action.charAt(0).toUpperCase() + action.slice(1);
    document.getElementById('modalMsg').textContent = 'Set "' + name + '" status to ' + action + '?';
    const btn = document.getElementById('modalConfirmBtn');
    btn.className = 'btn ' + (action==='approved'?'btn-approve':action==='rejected'?'btn-reject':'btn-pending');
    document.getElementById('confirmModal').classList.add('show');
}
function closeModal() {
    document.getElementById('confirmModal').classList.remove('show');
}
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
