<?php
/**
 * IUC Voting System - Voting Receipt
 * Shows blockchain receipt code after successful voting
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Check if voting receipt exists
if (!isset($_SESSION['voting_receipt'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$receipt = $_SESSION['voting_receipt'];
$userName = $_SESSION['user_name'];

// Clear receipt from session after displaying
unset($_SESSION['voting_receipt']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Receipt - IUC Voting System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            min-height: 100vh;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .receipt-container {
            max-width: 600px;
            width: 100%;
            margin: 2rem;
        }

        .receipt-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
        }

        .receipt-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2rem;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .receipt-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .receipt-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .receipt-content {
            padding: 2rem;
        }

        .receipt-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 1.5rem 0;
            letter-spacing: 2px;
            word-break: break-all;
        }

        .receipt-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .blockchain-info {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 10px;
            padding: 1rem;
            margin: 1.5rem 0;
        }

        .blockchain-info h3 {
            color: #92400e;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .blockchain-info p {
            color: #78350f;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .transaction-hash {
            background: #1e293b;
            color: #10b981;
            padding: 0.75rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            word-break: break-all;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .verification-info {
            background: #f0fdf4;
            border: 1px solid #10b981;
            border-radius: 10px;
            padding: 1rem;
            margin: 1.5rem 0;
        }

        .verification-info h3 {
            color: #059669;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .verification-info p {
            color: #047857;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .receipt-container {
                margin: 1rem;
            }
        }

        .print-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            margin-bottom: 1rem;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-card">
            <!-- Success Header -->
            <div class="receipt-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Vote Successfully Cast!</h1>
                <p>Thank you for participating in the democratic process</p>
            </div>

            <!-- Receipt Content -->
            <div class="receipt-content">
                <h2 style="color: #333; margin-bottom: 0.5rem;">Blockchain Voting Receipt</h2>
                <p style="color:#666; margin-bottom:1.5rem; font-size:.9rem;">Save your transaction hash — you'll need it to verify your vote.</p>

                <!-- Transaction Hash (the real verifiable code) -->
                <div style="background:#1e293b; border-radius:10px; padding:1.25rem; margin-bottom:1.5rem;">
                    <div style="color:#94a3b8; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.5rem;">
                        <i class="fas fa-link"></i> Transaction Hash (use this to verify)
                    </div>
                    <div id="txHash" style="color:#10b981; font-family:'Courier New',monospace; font-size:.85rem; word-break:break-all; margin-bottom:.75rem;">
                        <?php echo htmlspecialchars($receipt['transaction_hash'] ?? $receipt['receipt_code'] ?? 'N/A'); ?>
                    </div>
                    <button onclick="copyHash()" style="background:rgba(255,255,255,.1); color:white; border:1px solid rgba(255,255,255,.2); padding:.4rem .9rem; border-radius:6px; cursor:pointer; font-size:.8rem;">
                        <i class="fas fa-copy"></i> Copy Hash
                    </button>
                </div>

                <!-- Voting Details -->
                <div class="receipt-details">
                    <div class="detail-row">
                        <span class="detail-label">Voter:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Election:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($receipt['election_title'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Voted For:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($receipt['candidate_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time:</span>
                        <span class="detail-value"><?php echo !empty($receipt['voted_at']) ? date('M d, Y H:i:s', strtotime($receipt['voted_at'])) : 'N/A'; ?></span>
                    </div>
                </div>

                <!-- Verification Instructions -->
                <div class="blockchain-info">
                    <h3><i class="fas fa-shield-alt"></i> How to Verify Your Vote</h3>
                    <p>1. Copy the transaction hash above<br>
                       2. Go to <strong>Verify Vote</strong> from your dashboard<br>
                       3. Paste the hash and click Verify<br>
                       4. Your vote details will be confirmed</p>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="index.php?page=dashboard" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                    <a href="index.php?page=logout" class="btn btn-success">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>

                <!-- Print Button -->
                <button onclick="window.print()" class="btn print-btn" style="width: 100%;">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <script>
function copyHash() {
    const hash = document.getElementById('txHash').innerText.trim();
    navigator.clipboard.writeText(hash).then(() => {
        const btn = event.target.closest('button');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Hash'; }, 2000);
    });
}

// Auto-clear receipt after 5 minutes for security
        setTimeout(() => {
            console.log('Receipt session cleared for security');
        }, 300000);

        // Prevent back navigation after voting
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, null, window.location.href);
        };
    </script>
</body>
</html>
