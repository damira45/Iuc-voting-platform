<?php
/**
 * IUC Voting System - Student Voting Panel
 */

require_once 'includes/election.php';
require_once 'includes/student.php';

$election = new Election();
$student  = new Student();

$userId    = $_SESSION['user_id'];
$userName  = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

if (!$student->canStudentVote($userId)) {
    header("Location: index.php?page=dashboard&error=not_approved");
    exit;
}

$activeElections = $election->getActiveElections();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
    $electionId  = $_POST['election_id'];
    $candidateId = $_POST['candidate_id'];
    $result = $election->castVote($electionId, $candidateId, $userId);
    if ($result['success']) {
        $electionData  = $election->getElectionById($electionId);
        $candidateData = $election->getElectionCandidates($electionId);
        $candidateName = '';
        foreach ($candidateData as $c) {
            if ((int)$c['id'] === (int)$candidateId) { $candidateName = $c['name']; break; }
        }
        $_SESSION['voting_receipt'] = [
            'receipt_code'     => $result['transaction_hash'],
            'election_title'   => $electionData['title'] ?? 'Unknown Election',
            'candidate_name'   => $candidateName ?: 'Unknown Candidate',
            'transaction_hash' => $result['transaction_hash'],
            'voted_at'         => date('Y-m-d H:i:s')
        ];
        header("Location: index.php?page=voting_receipt");
        exit;
    } else {
        $error = $result['message'];
    }
}

$selectedElection = null;
$candidates = [];
$hasVoted   = false;

if (isset($_GET['election_id'])) {
    $electionId = $_GET['election_id'];
    $selectedElection = $election->getElectionById($electionId);
    if ($selectedElection) {
        $candidates = $election->getElectionCandidates($electionId);
        $hasVoted   = $election->hasUserVoted($userId, $electionId);
    }
}

// Get student profile
$stmtP = $pdo->prepare("SELECT s.student_id, s.department, s.level FROM students s WHERE s.user_id = ?");
$stmtP->execute([$userId]);
$profile = $stmtP->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vote - IUC Voting System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f0f4f8;color:#1e293b;min-height:100vh}

/* Top nav */
.topnav{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;padding:0 2rem;height:64px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(11,60,93,.3);position:sticky;top:0;z-index:100}
.topnav-brand{display:flex;align-items:center;gap:.75rem;font-size:1.1rem;font-weight:700}
.topnav-brand .logo{width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.topnav-right{display:flex;align-items:center;gap:1rem}
.topnav-user{display:flex;align-items:center;gap:.6rem;font-size:.875rem;opacity:.9}
.topnav-user .avatar{width:34px;height:34px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem}
.nav-link{color:rgba(255,255,255,.85);text-decoration:none;padding:.4rem .9rem;border-radius:7px;font-size:.85rem;font-weight:500;transition:all .2s;display:flex;align-items:center;gap:.4rem}
.nav-link:hover{background:rgba(255,255,255,.15);color:#fff}
.nav-link.danger{background:rgba(239,68,68,.2);color:#fca5a5}
.nav-link.danger:hover{background:rgba(239,68,68,.35);color:#fff}

/* Page wrapper */
.page{max-width:1100px;margin:0 auto;padding:2rem}

/* Breadcrumb */
.breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#64748b;margin-bottom:1.5rem}
.breadcrumb a{color:#3282B8;text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb i{font-size:.65rem;color:#94a3b8}

/* Page header */
.page-header{background:#fff;border-radius:14px;padding:1.5rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;border-left:4px solid #3282B8}
.page-header h1{font-size:1.4rem;font-weight:800;color:#0B3C5D;display:flex;align-items:center;gap:.6rem}
.page-header p{color:#64748b;font-size:.875rem;margin-top:.2rem}
.student-badge{background:#f0f7ff;border:1px solid #bfdbfe;border-radius:10px;padding:.5rem 1rem;font-size:.8rem;color:#1e40af;display:flex;align-items:center;gap:.5rem}

/* Alert */
.alert{padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem;font-weight:500}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}

/* Election list cards */
.elections-grid{display:flex;flex-direction:column;gap:1.25rem}
.election-card{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden;transition:all .2s;border:1px solid #e2e8f0}
.election-card:hover{box-shadow:0 6px 20px rgba(50,130,184,.12);transform:translateY(-2px)}
.election-card-header{padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}
.election-card-header h3{font-size:1.1rem;font-weight:700;color:#0B3C5D;margin-bottom:.3rem}
.election-card-header p{font-size:.85rem;color:#64748b;line-height:1.5}
.election-card-footer{padding:1rem 1.5rem;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem}
.meta-tags{display:flex;gap:.5rem;flex-wrap:wrap}
.meta-tag{background:#e2e8f0;padding:.2rem .7rem;border-radius:20px;font-size:.75rem;color:#475569;display:flex;align-items:center;gap:.3rem}
.status-pill{padding:.3rem .8rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase}
.pill-active{background:#dcfce7;color:#166534}
.pill-voted{background:#dbeafe;color:#1e40af}

/* Buttons */
.btn{padding:.6rem 1.25rem;border:none;border-radius:9px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.45rem;font-size:.875rem;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,#3282B8,#0B3C5D);color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(50,130,184,.35)}
.btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.btn-success:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,.35)}
.btn-outline{background:#fff;border:2px solid #e2e8f0;color:#64748b}
.btn-outline:hover{border-color:#3282B8;color:#3282B8}
.btn-lg{padding:.85rem 2rem;font-size:1rem}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important;box-shadow:none!important}

/* Voting panel */
.voting-panel{background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);overflow:hidden}
.voting-panel-header{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;padding:1.5rem 2rem}
.voting-panel-header h2{font-size:1.2rem;font-weight:700;margin-bottom:.3rem}
.voting-panel-header p{opacity:.8;font-size:.875rem}
.voting-panel-body{padding:2rem}

/* Candidates grid */
.candidates-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;margin-bottom:2rem}
.candidate-card{background:#f8fafc;border-radius:12px;padding:1.5rem;border:2px solid #e2e8f0;cursor:pointer;transition:all .25s;text-align:center;position:relative}
.candidate-card:hover{border-color:#3282B8;background:#f0f7ff;transform:translateY(-3px);box-shadow:0 8px 20px rgba(50,130,184,.15)}
.candidate-card.selected{border-color:#0B3C5D;background:linear-gradient(135deg,rgba(11,60,93,.06),rgba(50,130,184,.08));box-shadow:0 0 0 3px rgba(50,130,184,.2)}
.candidate-card input[type=radio]{position:absolute;opacity:0;pointer-events:none}
.candidate-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#0B3C5D,#3282B8);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;margin:0 auto 1rem}
.candidate-card.selected .candidate-avatar{background:linear-gradient(135deg,#3282B8,#0B3C5D);box-shadow:0 4px 12px rgba(50,130,184,.4)}
.candidate-name{font-size:1rem;font-weight:700;color:#0B3C5D;margin-bottom:.25rem}
.candidate-desc{font-size:.8rem;color:#64748b;line-height:1.5;margin-top:.5rem}
.selected-check{position:absolute;top:.75rem;right:.75rem;width:24px;height:24px;background:#0B3C5D;border-radius:50%;display:none;align-items:center;justify-content:center;color:#fff;font-size:.7rem}
.candidate-card.selected .selected-check{display:flex}

/* Voted state */
.voted-banner{background:linear-gradient(135deg,#dcfce7,#bbf7d0);border:1px solid #86efac;border-radius:12px;padding:2rem;text-align:center;color:#166534}
.voted-banner i{font-size:3rem;margin-bottom:.75rem;display:block}
.voted-banner h3{font-size:1.2rem;font-weight:700;margin-bottom:.5rem}

/* Empty state */
.empty-state{text-align:center;padding:4rem 2rem;color:#94a3b8}
.empty-state i{font-size:3.5rem;display:block;margin-bottom:1rem;opacity:.3}
.empty-state h3{font-size:1.1rem;font-weight:600;color:#64748b;margin-bottom:.5rem}

/* Confirm overlay */
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.confirm-overlay.show{display:flex}
.confirm-box{background:#fff;border-radius:16px;padding:2rem;max-width:420px;width:90%;box-shadow:0 20px 50px rgba(0,0,0,.2);text-align:center}
.confirm-box .icon{font-size:3rem;margin-bottom:.75rem}
.confirm-box h3{font-size:1.1rem;font-weight:700;color:#0B3C5D;margin-bottom:.5rem}
.confirm-box p{color:#64748b;font-size:.9rem;margin-bottom:1.5rem}
.confirm-actions{display:flex;gap:.75rem;justify-content:center}

@media(max-width:600px){.page{padding:1rem}.candidates-grid{grid-template-columns:1fr}.topnav{padding:0 1rem}}
</style>
</head>
<body>

<!-- Top Navigation -->
<nav class="topnav">
    <div class="topnav-brand">
        <div class="logo"><i class="fas fa-shield-alt"></i></div>
        IUC Voting System
    </div>
    <div class="topnav-right">
        <div class="topnav-user">
            <div class="avatar"><?php echo strtoupper(substr($userName,0,2)); ?></div>
            <span><?php echo htmlspecialchars($userName); ?></span>
        </div>
        <a href="index.php?page=dashboard" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="index.php?page=results" class="nav-link"><i class="fas fa-chart-bar"></i> Results</a>
        <a href="index.php?page=logout" class="nav-link danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="page">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a>
        <i class="fas fa-chevron-right"></i>
        <?php if ($selectedElection): ?>
        <a href="index.php?page=simple_voting">Elections</a>
        <i class="fas fa-chevron-right"></i>
        <span><?php echo htmlspecialchars($selectedElection['title']); ?></span>
        <?php else: ?>
        <span>Elections</span>
        <?php endif; ?>
    </div>

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-vote-yea" style="color:#3282B8"></i>
                <?php echo $selectedElection ? 'Cast Your Vote' : 'Available Elections'; ?>
            </h1>
            <p><?php echo $selectedElection ? 'Select a candidate and confirm your vote' : 'Choose an election to participate in'; ?></p>
        </div>
        <?php if ($profile): ?>
        <div class="student-badge">
            <i class="fas fa-id-card"></i>
            <?php echo htmlspecialchars($profile['student_id'] ?? ''); ?>
            &nbsp;&nbsp;
            <?php echo htmlspecialchars($profile['department'] ?? ''); ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($selectedElection): ?>
    <!--  VOTING PANEL  -->
    <div class="voting-panel">
        <div class="voting-panel-header">
            <h2><?php echo htmlspecialchars($selectedElection['title']); ?></h2>
            <p><?php echo htmlspecialchars($selectedElection['description']); ?>
               &nbsp;&nbsp; Ends <?php echo date('M d, Y', strtotime($selectedElection['end_date'])); ?></p>
        </div>
        <div class="voting-panel-body">

            <?php if ($hasVoted): ?>
            <div class="voted-banner">
                <i class="fas fa-check-circle"></i>
                <h3>You have already voted in this election</h3>
                <p style="margin-bottom:1.25rem;">Your vote has been securely recorded on the blockchain.</p>
                <a href="index.php?page=simple_voting" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Elections
                </a>
            </div>

            <?php elseif (empty($candidates)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No candidates yet</h3>
                <p>Candidates have not been added to this election.</p>
            </div>

            <?php else: ?>
            <p style="color:#64748b;margin-bottom:1.5rem;font-size:.9rem;">
                <i class="fas fa-info-circle" style="color:#3282B8"></i>
                Click a candidate card to select, then confirm your vote. Your choice is final.
            </p>

            <form id="voteForm" method="POST">
                <input type="hidden" name="action" value="vote">
                <input type="hidden" name="election_id" value="<?php echo $selectedElection['id']; ?>">
                <input type="hidden" name="candidate_id" id="selectedCandidateId" value="">

                <div class="candidates-grid">
                    <?php foreach ($candidates as $i => $c): ?>
                    <div class="candidate-card" id="card_<?php echo $c['id']; ?>"
                         onclick="selectCandidate(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')">
                        <input type="radio" name="candidate_id" value="<?php echo $c['id']; ?>" id="r_<?php echo $c['id']; ?>">
                        <div class="selected-check"><i class="fas fa-check"></i></div>
                        <div class="candidate-avatar"><?php echo strtoupper(substr($c['name'],0,2)); ?></div>
                        <div class="candidate-name"><?php echo htmlspecialchars($c['name']); ?></div>
                        <?php if (!empty($c['description'])): ?>
                        <div class="candidate-desc"><?php echo htmlspecialchars($c['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <a href="index.php?page=simple_voting" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="button" id="voteBtn" class="btn btn-success btn-lg" disabled onclick="showConfirm()">
                        <i class="fas fa-vote-yea"></i> Cast Vote
                    </button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>

    <?php else: ?>
    <!--  ELECTIONS LIST  -->
    <?php if (empty($activeElections)): ?>
    <div class="empty-state" style="background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);">
        <i class="fas fa-vote-yea"></i>
        <h3>No active elections</h3>
        <p>There are no elections open for voting right now. Check back later.</p>
        <a href="index.php?page=dashboard" class="btn btn-primary" style="margin-top:1rem;">
            <i class="fas fa-home"></i> Back to Dashboard
        </a>
    </div>
    <?php else: ?>
    <div class="elections-grid">
        <?php foreach ($activeElections as $elec):
            $voted = $election->hasUserVoted($userId, $elec['id']);
        ?>
        <div class="election-card">
            <div class="election-card-header">
                <div>
                    <h3><?php echo htmlspecialchars($elec['title']); ?></h3>
                    <p><?php echo htmlspecialchars($elec['description']); ?></p>
                </div>
                <span class="status-pill <?php echo $voted ? 'pill-voted' : 'pill-active'; ?>">
                    <?php echo $voted ? 'Voted' : 'Open'; ?>
                </span>
            </div>
            <div class="election-card-footer">
                <div class="meta-tags">
                    <span class="meta-tag"><i class="fas fa-calendar"></i> Ends <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                    <span class="meta-tag"><i class="fas fa-ballot-check"></i> <?php echo $elec['total_votes'] ?? 0; ?> votes cast</span>
                </div>
                <?php if ($voted): ?>
                <span class="btn btn-outline" style="cursor:default;opacity:.7;">
                    <i class="fas fa-check" style="color:#10b981"></i> Already Voted
                </span>
                <?php else: ?>
                <a href="index.php?page=simple_voting&election_id=<?php echo $elec['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-vote-yea"></i> Vote Now
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<!-- Confirm modal -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="icon"></div>
        <h3>Confirm Your Vote</h3>
        <p id="confirmMsg">Are you sure you want to vote for <strong id="confirmName"></strong>? This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="btn btn-outline" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-success" onclick="submitVote()"><i class="fas fa-check"></i> Yes, Cast Vote</button>
        </div>
    </div>
</div>

<script>
let selectedId = null;

function selectCandidate(id, name) {
    selectedId = id;
    document.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card_' + id).classList.add('selected');
    document.getElementById('r_' + id).checked = true;
    document.getElementById('selectedCandidateId').value = id;
    document.getElementById('voteBtn').disabled = false;
    document.getElementById('confirmName').textContent = name;
}

function showConfirm() {
    if (!selectedId) return;
    document.getElementById('confirmOverlay').classList.add('show');
}

function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('show');
}

function submitVote() {
    document.getElementById('voteForm').submit();
}

document.getElementById('confirmOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});
</script>
</body>
</html>
