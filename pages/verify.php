<?php
/**
 * IUC Voting System - Verify Vote (Student)
 * Fully dynamic — looks up real votes by transaction hash
 */

require_once 'config/config.php';

$search_hash = '';
$verification_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['transaction_hash'])) {
    $search_hash = trim($_POST['transaction_hash']);

    $stmt = $pdo->prepare("
        SELECT v.transaction_hash, v.created_at,
               u.name  AS voter_name,
               e.title AS election_title,
               c.name  AS candidate_name
        FROM votes v
        JOIN users      u ON v.user_id      = u.id
        JOIN elections  e ON v.election_id  = e.id
        JOIN candidates c ON v.candidate_id = c.id
        WHERE v.transaction_hash = ?
        LIMIT 1
    ");
    $stmt->execute([$search_hash]);
    $verification_result = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Vote - IUC Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }

        .container { width:100%; max-width:680px; }

        .card { background:rgba(255,255,255,.97); border-radius:20px; padding:2.5rem; box-shadow:0 20px 40px rgba(0,0,0,.15); margin-bottom:1.5rem; }

        .header { text-align:center; margin-bottom:2rem; }
        .header .icon { width:70px; height:70px; background:linear-gradient(135deg,#667eea,#764ba2); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; }
        .header .icon i { font-size:1.8rem; color:white; }
        .header h1 { font-size:1.8rem; font-weight:700; color:#333; margin-bottom:.4rem; }
        .header p  { color:#666; font-size:.95rem; }

        .search-form { display:flex; gap:.75rem; margin-bottom:1.5rem; }
        .search-form input { flex:1; padding:.85rem 1rem; border:2px solid #e5e7eb; border-radius:10px; font-size:.9rem; font-family:'Courier New',monospace; transition:border-color .2s; }
        .search-form input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
        .btn { padding:.85rem 1.5rem; border:none; border-radius:10px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.5rem; font-size:.9rem; transition:all .2s; white-space:nowrap; }
        .btn-primary { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,.4); }

        /* Result */
        .result-found  { border:2px solid #10b981; border-radius:14px; overflow:hidden; }
        .result-missing { border:2px solid #ef4444; border-radius:14px; overflow:hidden; }

        .result-banner { padding:1.25rem 1.5rem; display:flex; align-items:center; gap:1rem; }
        .result-found   .result-banner { background:#d1fae5; color:#065f46; }
        .result-missing .result-banner { background:#fee2e2; color:#991b1b; }
        .result-banner i { font-size:2rem; }
        .result-banner h3 { font-size:1.1rem; font-weight:700; }
        .result-banner p  { font-size:.85rem; opacity:.8; margin-top:.2rem; }

        .result-body { padding:1.5rem; }
        .detail-row { display:flex; justify-content:space-between; align-items:flex-start; padding:.6rem 0; border-bottom:1px solid #f1f5f9; gap:1rem; }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { font-size:.85rem; color:#64748b; font-weight:600; white-space:nowrap; }
        .detail-value { font-size:.9rem; color:#1e293b; font-weight:500; text-align:right; word-break:break-all; }

        .hash-box { background:#1e293b; color:#10b981; font-family:'Courier New',monospace; font-size:.78rem; padding:1rem; border-radius:8px; word-break:break-all; margin-top:1rem; }

        .back-link { text-align:center; margin-top:1rem; }
        .back-link a { color:rgba(255,255,255,.85); text-decoration:none; font-size:.9rem; }
        .back-link a:hover { color:white; }

        @media(max-width:500px) { .search-form{flex-direction:column} .card{padding:1.5rem} }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <div class="header">
            <div class="icon"><i class="fas fa-shield-alt"></i></div>
            <h1>Verify Your Vote</h1>
            <p>Enter the transaction hash from your voting receipt to confirm your vote was recorded.</p>
        </div>

        <form method="POST" class="search-form">
            <input type="text" name="transaction_hash"
                   placeholder="Paste your transaction hash here…"
                   value="<?= htmlspecialchars($search_hash) ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Verify
            </button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $search_hash): ?>
            <?php if ($verification_result): ?>
                <div class="result-found">
                    <div class="result-banner">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3>Vote Confirmed ✓</h3>
                            <p>Your vote is securely recorded in the system.</p>
                        </div>
                    </div>
                    <div class="result-body">
                        <div class="detail-row">
                            <span class="detail-label">Voter</span>
                            <span class="detail-value"><?= htmlspecialchars($verification_result['voter_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Election</span>
                            <span class="detail-value"><?= htmlspecialchars($verification_result['election_title']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Voted For</span>
                            <span class="detail-value"><?= htmlspecialchars($verification_result['candidate_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date &amp; Time</span>
                            <span class="detail-value"><?= date('M d, Y H:i:s', strtotime($verification_result['created_at'])) ?></span>
                        </div>
                        <div class="hash-box">
                            <strong style="color:#94a3b8; font-size:.75rem;">TRANSACTION HASH</strong><br><br>
                            <?= htmlspecialchars($verification_result['transaction_hash']) ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="result-missing">
                    <div class="result-banner">
                        <i class="fas fa-times-circle"></i>
                        <div>
                            <h3>Not Found</h3>
                            <p>No vote with this transaction hash was found. Please check and try again.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="back-link">
        <a href="index.php?page=dashboard"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

<script>
// Auto-fill hash if passed from results page
window.addEventListener('load', () => {
    const stored = sessionStorage.getItem('verifyHash');
    if (stored) {
        const input = document.querySelector('input[name="transaction_hash"]');
        if (input && !input.value) {
            input.value = stored;
        }
        sessionStorage.removeItem('verifyHash');
    }
});
</script>

</div>
</body>
</html>
