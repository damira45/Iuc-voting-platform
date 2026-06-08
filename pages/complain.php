<?php
/**
 * IUC Voting System - Complaints / Notifications (Dynamic)
 */

$msg = '';

// Handle contact message reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_contact') {
    $msgId    = (int)$_POST['message_id'];
    $replyTxt = trim($_POST['reply_text'] ?? '');
    if ($msgId && $replyTxt !== '') {
        $pdo->prepare("UPDATE contact_messages SET status='replied', reply=?, replied_at=NOW() WHERE id=?")
            ->execute([$replyTxt, $msgId]);
        $logDetails = "Replied to contact message ID $msgId";
        $ipAddr     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $adminId    = $_SESSION['user_id'] ?? 0;
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, 'CONTACT_REPLY', ?, ?, NOW())")
            ->execute([$adminId, $logDetails, $ipAddr]);
    }
    header("Location: index.php?page=complain&msg=replied");
    exit;
}

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $nid = (int)$_POST['notification_id'];
    $pdo->prepare("UPDATE notifications SET status='read' WHERE id=?")->execute([$nid]);
    header("Location: index.php?page=complain&msg=marked_read");
    exit;
}

// Handle mark all read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $pdo->query("UPDATE notifications SET status='read' WHERE status='unread'");
    header("Location: index.php?page=complain&msg=all_read");
    exit;
}

// Handle dismiss
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss'])) {
    $nid = (int)$_POST['notification_id'];
    $pdo->prepare("UPDATE notifications SET status='dismissed' WHERE id=?")->execute([$nid]);
    header("Location: index.php?page=complain&msg=dismissed");
    exit;
}

if (!empty($_GET['msg'])) {
    $msgs = [
        'marked_read' => 'Notification marked as read.',
        'all_read'    => 'All notifications marked as read.',
        'dismissed'   => 'Notification dismissed.',
        'replied'     => 'Reply sent successfully.',
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

// ── Contact messages ────────────────────────────────────────────────────────
$contactMessages  = [];
$unreadContactCnt = 0;
try {
    $contactMessages  = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $unreadContactCnt = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='pending'")->fetchColumn();
} catch (Exception $e) {
    // table may not exist yet — silently skip
}

// ── Notification stats ───────────────────────────────────────────────────────
$totalNotif = (int)$pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$unread     = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE status='unread'")->fetchColumn();
$actionReq  = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE action_required=1 AND status='unread'")->fetchColumn();
$dismissed  = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE status='dismissed'")->fetchColumn();

// ── Filters ──────────────────────────────────────────────────────────────────
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter   = trim($_GET['type']   ?? '');

$where  = 'WHERE 1=1';
$params = [];
if ($statusFilter !== '') { $where .= " AND n.status=?"; $params[] = $statusFilter; }
if ($typeFilter   !== '') { $where .= " AND n.type=?";   $params[] = $typeFilter;   }

// ── Notifications ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT n.*, u.name AS user_name, u.email AS user_email, u.type AS user_type
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    $where
    ORDER BY n.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$types = $pdo->query("SELECT DISTINCT type FROM notifications ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);

// ── Pending students ──────────────────────────────────────────────────────────
$pendingStudents = $pdo->query("
    SELECT u.id, u.name, u.email, u.created_at, s.student_id, s.department, s.level
    FROM users u
    LEFT JOIN students s ON s.user_id=u.id
    WHERE u.type='student' AND u.status='pending'
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complaints & Notifications - IUC Voting System</title>
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
.toast{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:.75rem 1.25rem;border-radius:10px;margin-bottom:1.5rem;font-weight:600;display:flex;align-items:center;gap:.5rem}
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
.fg select{padding:.55rem .85rem;border:2px solid #e2e8f0;border-radius:8px;font-size:.83rem;color:#1e293b;background:#fff}
.fg select:focus{outline:none;border-color:#3282B8}
.btn{padding:.5rem .95rem;border:none;border-radius:8px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-p{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-p:hover{transform:translateY(-1px)}
.btn-o{background:#fff;border:2px solid #e2e8f0;color:#64748b}
.btn-o:hover{border-color:#3282B8;color:#3282B8}
.btn-g{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
.btn-g:hover{background:#166534;color:#fff}
.btn-r{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
.btn-r:hover{background:#dc2626;color:#fff}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;margin-bottom:1.5rem}
.card-head{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;font-size:.95rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:space-between;gap:.5rem}
.card-head-l{display:flex;align-items:center;gap:.5rem}
.notif-item{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;gap:1rem;transition:background .2s}
.notif-item:last-child{border-bottom:none}
.notif-item:hover{background:#f8fafc}
.notif-item.unread{border-left:3px solid #3282B8;background:#f0f7ff}
.notif-item.unread:hover{background:#e8f2ff}
.notif-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:.35rem}
.dot-unread{background:#3282B8}
.dot-read{background:#cbd5e1}
.dot-dismissed{background:#e2e8f0}
.notif-body{flex:1;min-width:0}
.notif-title{font-size:.88rem;font-weight:600;color:#1e293b;margin-bottom:.2rem}
.notif-msg{font-size:.82rem;color:#64748b;margin-bottom:.4rem}
.notif-meta{font-size:.75rem;color:#94a3b8;display:flex;gap:.75rem;flex-wrap:wrap}
.notif-actions{display:flex;gap:.4rem;flex-shrink:0;flex-wrap:wrap}
.badge{padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.b-unread{background:#dbeafe;color:#1e40af}
.b-read{background:#f1f5f9;color:#64748b}
.b-dismissed{background:#f3f4f6;color:#9ca3af}
.b-high{background:#fee2e2;color:#991b1b}
.b-medium{background:#fef9c3;color:#854d0e}
.b-low{background:#f1f5f9;color:#64748b}
/* contact message specific */
.cm-item{padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9}
.cm-item:last-child{border-bottom:none}
.cm-item:hover{background:#f8fafc}
.cm-item.pending{border-left:3px solid #f59e0b;background:#fffbeb}
.cm-item.replied{border-left:3px solid #10b981}
.cm-header{display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;flex-wrap:wrap;margin-bottom:.4rem}
.cm-name{font-weight:700;font-size:.9rem;color:#0B3C5D}
.cm-email{font-size:.78rem;color:#64748b}
.cm-subject{font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.3rem}
.cm-body{font-size:.82rem;color:#475569;margin-bottom:.5rem;line-height:1.5}
.cm-reply{background:#f0fdf4;border-left:3px solid #10b981;padding:.6rem .9rem;border-radius:0 8px 8px 0;font-size:.82rem;color:#166534;margin-top:.5rem}
.cm-reply strong{display:block;font-size:.73rem;margin-bottom:.2rem;color:#059669}
.cm-meta{font-size:.73rem;color:#94a3b8;display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.3rem}
.badge-pending{background:#fef9c3;color:#854d0e;padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700}
.badge-replied{background:#dcfce7;color:#166534;padding:.2rem .55rem;border-radius:8px;font-size:.7rem;font-weight:700}
/* modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:14px;padding:1.75rem;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.modal h3{font-size:1.1rem;font-weight:700;color:#0B3C5D;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.modal textarea{width:100%;border:2px solid #e2e8f0;border-radius:8px;padding:.75rem;font-size:.85rem;resize:vertical;min-height:120px;color:#1e293b}
.modal textarea:focus{outline:none;border-color:#3282B8}
.modal-footer{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem}
.empty{text-align:center;padding:2.5rem;color:#94a3b8}
.empty i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap}
td{padding:.6rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151;vertical-align:middle}
tr:hover td{background:#f8fafc}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:1rem}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="sb-brand"><div class="sb-logo"><i class="fas fa-comment-alt"></i></div><div><h1>IUC Voting</h1><p>Admin Panel</p></div></div>
    <div class="sb-sec">Main</div>
    <a href="index.php?page=admin" class="sb-a"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <div class="sb-sec">Elections</div>
    <a href="index.php?page=elections" class="sb-a"><i class="fas fa-vote-yea"></i> Elections</a>
    <a href="index.php?page=results" class="sb-a"><i class="fas fa-chart-bar"></i> Results</a>
    <div class="sb-sec">System</div>
    <a href="index.php?page=settings" class="sb-a"><i class="fas fa-cog"></i> Settings</a>
    <a href="index.php?page=report" class="sb-a"><i class="fas fa-file-alt"></i> Report</a>
    <a href="index.php?page=backup" class="sb-a"><i class="fas fa-database"></i> Backup</a>
    <a href="index.php?page=complain" class="sb-a active"><i class="fas fa-comment-alt"></i> Complaints</a>
    <a href="index.php?page=logout" class="sb-a" style="margin-top:2rem;color:#fbbf24;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main">
    <div class="page-hdr">
        <div>
            <h1><i class="fas fa-comment-alt" style="color:#3282B8"></i> Complaints &amp; Notifications</h1>
            <p>Contact messages, system notifications, alerts, and pending actions</p>
        </div>
        <?php if ($unread > 0): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="mark_all_read" value="1">
            <button type="submit" class="btn btn-g"><i class="fas fa-check-double"></i> Mark All Read</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($msg): ?>
    <div class="toast"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="sc"><div class="sc-icon"><i class="fas fa-bell"></i></div><div><div class="sc-num"><?= $totalNotif ?></div><div class="sc-lbl">Total Notifs</div></div></div>
        <div class="sc o"><div class="sc-icon"><i class="fas fa-envelope"></i></div><div><div class="sc-num"><?= $unread ?></div><div class="sc-lbl">Unread</div></div></div>
        <div class="sc r"><div class="sc-icon"><i class="fas fa-exclamation-circle"></i></div><div><div class="sc-num"><?= $actionReq ?></div><div class="sc-lbl">Action Required</div></div></div>
        <div class="sc p"><div class="sc-icon"><i class="fas fa-envelope-open-text"></i></div><div><div class="sc-num"><?= $unreadContactCnt ?></div><div class="sc-lbl">Unread Contact</div></div></div>
        <div class="sc g"><div class="sc-icon"><i class="fas fa-user-clock"></i></div><div><div class="sc-num"><?= count($pendingStudents) ?></div><div class="sc-lbl">Pending Students</div></div></div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         CONTACT MESSAGES SECTION (NEW — before notifications)
    ══════════════════════════════════════════════════════════ -->
    <div class="card">
        <div class="card-head">
            <div class="card-head-l">
                <i class="fas fa-envelope-open-text" style="color:#8b5cf6"></i>
                Contact Messages
            </div>
            <?php if ($unreadContactCnt > 0): ?>
            <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .6rem;border-radius:8px;font-size:.75rem;font-weight:700;">
                <?= $unreadContactCnt ?> unread
            </span>
            <?php else: ?>
            <span style="background:#f1f5f9;color:#64748b;padding:.2rem .6rem;border-radius:8px;font-size:.75rem;font-weight:700;">
                <?= count($contactMessages) ?> total
            </span>
            <?php endif; ?>
        </div>

        <?php if (empty($contactMessages)): ?>
            <div class="empty"><i class="fas fa-inbox"></i>No contact messages yet.</div>
        <?php else: ?>
            <?php foreach ($contactMessages as $cm): ?>
            <div class="cm-item <?= htmlspecialchars($cm['status']) ?>">
                <div class="cm-header">
                    <div>
                        <span class="cm-name"><?= htmlspecialchars($cm['name']) ?></span>
                        <span class="cm-email"> &lt;<?= htmlspecialchars($cm['email']) ?>&gt;</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
                        <?php if ($cm['status'] === 'replied'): ?>
                            <span class="badge-replied"><i class="fas fa-check"></i> Replied</span>
                        <?php else: ?>
                            <span class="badge-pending"><i class="fas fa-clock"></i> Pending</span>
                        <?php endif; ?>
                        <?php if ($cm['status'] !== 'replied'): ?>
                        <button class="btn btn-p" style="font-size:.75rem;padding:.3rem .7rem;"
                            onclick="openReplyModal(<?= $cm['id'] ?>, '<?= htmlspecialchars(addslashes($cm['name'])) ?>')">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cm-subject"><i class="fas fa-tag" style="color:#3282B8;margin-right:.3rem;font-size:.75rem;"></i><?= htmlspecialchars($cm['subject'] ?? '(No subject)') ?></div>
                <div class="cm-body"><?= nl2br(htmlspecialchars($cm['message'])) ?></div>
                <?php if ($cm['status'] === 'replied' && !empty($cm['reply'])): ?>
                <div class="cm-reply">
                    <strong><i class="fas fa-reply"></i> Admin Reply</strong>
                    <?= nl2br(htmlspecialchars($cm['reply'])) ?>
                </div>
                <?php endif; ?>
                <div class="cm-meta">
                    <span><i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($cm['created_at'])) ?></span>
                    <?php if (!empty($cm['replied_at']) && $cm['status'] === 'replied'): ?>
                    <span><i class="fas fa-check-double"></i> Replied <?= date('M d, Y H:i', strtotime($cm['replied_at'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- ══ END CONTACT MESSAGES ══════════════════════════════════ -->

    <!-- Pending student approvals -->
    <?php if (!empty($pendingStudents)): ?>
    <div class="card">
        <div class="card-head">
            <div class="card-head-l"><i class="fas fa-user-clock" style="color:#f59e0b"></i> Pending Student Approvals</div>
            <span style="background:#fef9c3;color:#854d0e;padding:.2rem .6rem;border-radius:8px;font-size:.75rem;font-weight:700;"><?= count($pendingStudents) ?> pending</span>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Student ID</th><th>Department</th><th>Level</th><th>Registered</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pendingStudents as $s): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></td>
                <td style="color:#64748b;font-size:.78rem;"><?= htmlspecialchars($s['email']) ?></td>
                <td style="font-family:monospace;color:#3282B8;"><?= htmlspecialchars($s['student_id'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
                <td style="text-align:center;"><?= htmlspecialchars($s['level'] ?? '—') ?></td>
                <td style="color:#64748b;white-space:nowrap;"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:.4rem;">
                        <form method="POST" action="index.php?page=access_control" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="action" value="approved">
                            <button type="submit" class="btn btn-g" style="font-size:.75rem;padding:.3rem .7rem;"><i class="fas fa-check"></i> Approve</button>
                        </form>
                        <form method="POST" action="index.php?page=access_control" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="action" value="rejected">
                            <button type="submit" class="btn btn-r" style="font-size:.75rem;padding:.3rem .7rem;"><i class="fas fa-times"></i> Reject</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" action="index.php" class="filter-bar">
        <input type="hidden" name="page" value="complain">
        <div class="fg">
            <label>Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="unread"    <?= $statusFilter==='unread'?'selected':'' ?>>Unread</option>
                <option value="read"      <?= $statusFilter==='read'?'selected':'' ?>>Read</option>
                <option value="dismissed" <?= $statusFilter==='dismissed'?'selected':'' ?>>Dismissed</option>
            </select>
        </div>
        <div class="fg">
            <label>Type</label>
            <select name="type">
                <option value="">All Types</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $typeFilter===$t?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-p"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($statusFilter || $typeFilter): ?>
        <a href="index.php?page=complain" class="btn btn-o"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <!-- Notifications list -->
    <div class="card">
        <div class="card-head">
            <div class="card-head-l"><i class="fas fa-bell" style="color:#3282B8"></i> Notifications</div>
            <span style="font-size:.78rem;color:#94a3b8;"><?= count($notifications) ?> shown</span>
        </div>
        <?php if (empty($notifications)): ?>
            <div class="empty"><i class="fas fa-bell-slash"></i>No notifications found.</div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
            <div class="notif-item <?= $n['status'] ?>">
                <div class="notif-dot dot-<?= $n['status'] ?>"></div>
                <div class="notif-body">
                    <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-meta">
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($n['user_name'] ?? 'System') ?></span>
                        <span><i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($n['created_at'])) ?></span>
                        <span class="badge b-<?= $n['status'] ?>"><?= ucfirst($n['status']) ?></span>
                        <?php if (isset($n['priority']) && $n['priority'] !== 'low'): ?>
                        <span class="badge b-<?= $n['priority'] ?>"><?= ucfirst($n['priority']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($n['action_required'])): ?>
                        <span style="background:#fee2e2;color:#991b1b;padding:.15rem .5rem;border-radius:6px;font-size:.7rem;font-weight:700;">ACTION REQUIRED</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="notif-actions">
                    <?php if ($n['status'] === 'unread'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                        <button type="submit" name="mark_read" value="1" class="btn btn-g" style="font-size:.72rem;padding:.25rem .6rem;"><i class="fas fa-check"></i> Read</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($n['status'] !== 'dismissed'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                        <button type="submit" name="dismiss" value="1" class="btn btn-r" style="font-size:.72rem;padding:.25rem .6rem;"><i class="fas fa-times"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php if (!empty($n['action_url'])): ?>
                    <a href="<?= htmlspecialchars($n['action_url']) ?>" class="btn btn-p" style="font-size:.72rem;padding:.25rem .6rem;"><?= htmlspecialchars($n['action_text'] ?? 'View') ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</div>

<!-- Reply Modal -->
<div class="modal-overlay" id="replyModal">
    <div class="modal">
        <h3><i class="fas fa-reply" style="color:#3282B8"></i> Reply to Contact Message</h3>
        <p id="replyModalTarget" style="font-size:.82rem;color:#64748b;margin-bottom:.75rem;"></p>
        <form method="POST" action="index.php?page=complain">
            <input type="hidden" name="action" value="reply_contact">
            <input type="hidden" name="message_id" id="replyMessageId" value="">
            <textarea name="reply_text" placeholder="Type your reply here…" required></textarea>
            <div class="modal-footer">
                <button type="button" class="btn btn-o" onclick="closeReplyModal()"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="btn btn-p"><i class="fas fa-paper-plane"></i> Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplyModal(id, name) {
    document.getElementById('replyMessageId').value = id;
    document.getElementById('replyModalTarget').textContent = 'Replying to: ' + name;
    document.getElementById('replyModal').classList.add('open');
}
function closeReplyModal() {
    document.getElementById('replyModal').classList.remove('open');
}
document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) closeReplyModal();
});
</script>
</body>
</html>
