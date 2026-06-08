<?php
/**
 * IUC Voting System - Voting Interface
 * Clean, intuitive ballot design with 5 sample candidates
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        .voting-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .voting-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .voting-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.5rem;
        }
        
        .voting-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .content-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .content-body {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-control {
            padding: 0.8rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3282B8;
            box-shadow: 0 0 0 3px rgba(50, 130, 184, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.3);
        }
        
        .ballot-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .ballot-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .ballot-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .ballot-info {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .candidates-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .candidate-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .candidate-card:hover {
            border-color: #3282B8;
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.1);
        }
        
        .candidate-card.selected {
            border-color: #3282B8;
            background: linear-gradient(135deg, rgba(50, 130, 184, 0.05), rgba(11, 60, 93, 0.05));
            box-shadow: 0 4px 20px rgba(50, 130, 184, 0.2);
        }
        
        .candidate-radio {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
        }
        
        .radio-input {
            width: 24px;
            height: 24px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            background: white;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .radio-input::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 12px;
            height: 12px;
            background: #3282B8;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }
        
        .candidate-card.selected .radio-input {
            border-color: #3282B8;
        }
        
        .candidate-card.selected .radio-input::after {
            transform: translate(-50%, -50%) scale(1);
        }
        
        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1.5rem;
            flex-shrink: 0;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .candidate-info {
            flex: 1;
        }
        
        .candidate-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .candidate-party {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .candidate-platform {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        
        .candidate-details {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #9ca3af;
        }
        
        .candidate-detail {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .voting-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-vote {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-vote:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(50, 130, 184, 0.3);
        }
        
        .btn-vote:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
            padding: 1rem 2rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
        }
        
        /* Confirmation Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .modal-subtitle {
            color: #64748b;
        }
        
        .confirmation-details {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .confirmation-candidate {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .confirmation-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3282B8, #BBE1FA);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .btn-confirm {
            background: #10b981;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm:hover {
            background: #059669;
        }
        
        .btn-back {
            background: #f3f4f6;
            color: #6b7280;
            padding: 0.8rem 1.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #e5e7eb;
        }
        
        /* Vote Receipt */
        .receipt-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            text-align: center;
            display: none;
        }
        
        .receipt-container.active {
            display: block;
        }
        
        .receipt-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .receipt-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .receipt-details {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: left;
            margin-bottom: 1.5rem;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .receipt-item:last-child {
            border-bottom: none;
        }
        
        .receipt-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .receipt-value {
            color: #374151;
            font-weight: 600;
        }
        
        .receipt-code {
            background: #1e293b;
            color: #10b981;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            word-break: break-all;
        }
        
        .verification-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #10b981;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .voting-container {
                padding: 1rem;
            }
            
            .candidates-grid {
                gap: 1rem;
            }
            
            .candidate-card {
                flex-direction: column;
                text-align: center;
            }
            
            .candidate-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .candidate-radio {
                position: static;
                margin-bottom: 1rem;
                display: flex;
                justify-content: center;
            }
            
            .voting-actions {
                flex-direction: column;
            }
            
            .btn-vote, .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="voting-container">
        <!-- Voting Header -->
        <div class="voting-header">
            <h1 class="voting-title">Student Council President 2024</h1>
            <p class="voting-subtitle">Cast your vote for the next student council president</p>
        </div>
        
        <!-- Voting Code Verification -->
        <div class="content-card" id="codeVerificationSection">
            <div class="content-header">
                <h3 class="content-title">
                    <i class="fas fa-key"></i>
                    Enter Your Voting Code
                </h3>
            </div>
            <div class="content-body">
                <div class="form-group">
                    <label for="votingCodeInput">Voting Code</label>
                    <input type="text" id="votingCodeInput" placeholder="Enter your voting code" class="form-control">
                    <small id="codeHelp">Please enter the voting code sent to your email</small>
                </div>
                <button type="button" class="btn-primary" onclick="verifyVotingCode()">
                    <i class="fas fa-shield-alt"></i>
                    Verify Code
                </button>
            </div>
        </div>
        
        <!-- Voting Instructions -->
        <div class="voting-instructions" id="votingInstructions" style="display: none;">
            <h3><i class="fas fa-info-circle"></i> How to Vote</h3>
            <ol>
                <li>Review all candidates and their platforms</li>
                <li>Select your preferred candidate by clicking on their card</li>
                <li>Click "Submit Vote" to confirm your selection</li>
                <li>Verify your vote in the confirmation modal</li>
                <li>Receive your voting receipt with blockchain verification</li>
            </ol>
        </div>
        
        <!-- Ballot -->
        <div class="ballot-container" id="ballotContainer" style="display: none;">
            <div class="ballot-header">
                <h2 class="ballot-title">Select Your Candidate</h2>
                <p class="ballot-info">Please choose one candidate from the list below</p>
            </div>
            
            <form id="votingForm">
                <div class="candidates-grid">
                    <!-- Candidate 1 -->
                    <div class="candidate-card" onclick="selectCandidate(1)">
                        <div class="candidate-radio">
                            <div class="radio-input"></div>
                        </div>
                        <div class="candidate-avatar">
                            SJ
                        </div>
                        <div class="candidate-info">
                            <h3 class="candidate-name">Sarah Johnson</h3>
                            <span class="candidate-party">Progressive Alliance</span>
                            <p class="candidate-platform">Dedicated to improving student services, expanding mental health resources, and creating more inclusive campus spaces. Focus on sustainability and community engagement.</p>
                            <div class="candidate-details">
                                <div class="candidate-detail">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Computer Science, Year 3</span>
                                </div>
                                <div class="candidate-detail">
                                    <i class="fas fa-trophy"></i>
                                    <span>Debate Club President</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Candidate 2 -->
                    <div class="candidate-card" onclick="selectCandidate(2)">
                        <div class="candidate-radio">
                            <div class="radio-input"></div>
                        </div>
                        <div class="candidate-avatar">
                            MC
                        </div>
                        <div class="candidate-info">
                            <h3 class="candidate-name">Michael Chen</h3>
                            <span class="candidate-party">Innovation Party</span>
                            <p class="candidate-platform">Advocating for digital transformation of campus services, enhanced career development programs, and stronger industry partnerships. Technology-driven solutions for student success.</p>
                            <div class="candidate-details">
                                <div class="candidate-detail">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Business Administration, Year 4</span>
                                </div>
                                <div class="candidate-detail">
                                    <i class="fas fa-trophy"></i>
                                    <span>Student Entrepreneur</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Candidate 3 -->
                    <div class="candidate-card" onclick="selectCandidate(3)">
                        <div class="candidate-radio">
                            <div class="radio-input"></div>
                        </div>
                        <div class="candidate-avatar">
                            ED
                        </div>
                        <div class="candidate-info">
                            <h3 class="candidate-name">Emily Davis</h3>
                            <span class="candidate-party">Unity Coalition</span>
                            <p class="candidate-platform">Committed to fostering diversity, equity, and inclusion. Focus on affordable education, student housing, and mental health support. Building bridges between different student groups.</p>
                            <div class="candidate-details">
                                <div class="candidate-detail">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Social Sciences, Year 3</span>
                                </div>
                                <div class="candidate-detail">
                                    <i class="fas fa-trophy"></i>
                                    <span>Volunteer Coordinator</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Candidate 4 -->
                    <div class="candidate-card" onclick="selectCandidate(4)">
                        <div class="candidate-radio">
                            <div class="radio-input"></div>
                        </div>
                        <div class="candidate-avatar">
                            JR
                        </div>
                        <div class="candidate-info">
                            <h3 class="candidate-name">James Rodriguez</h3>
                            <span class="candidate-party">Student First</span>
                            <p class="candidate-platform">Prioritizing academic excellence, research opportunities, and career placement. Strong advocate for student rights and transparent governance. Experience in student leadership.</p>
                            <div class="candidate-details">
                                <div class="candidate-detail">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Engineering, Year 4</span>
                                </div>
                                <div class="candidate-detail">
                                    <i class="fas fa-trophy"></i>
                                    <span>Current VP Academic</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Candidate 5 -->
                    <div class="candidate-card" onclick="selectCandidate(5)">
                        <div class="candidate-radio">
                            <div class="radio-input"></div>
                        </div>
                        <div class="candidate-avatar">
                            AP
                        </div>
                        <div class="candidate-info">
                            <h3 class="candidate-name">Amanda Patel</h3>
                            <span class="candidate-party">Green Future</span>
                            <p class="candidate-platform">Environmental sustainability champion. Focus on green campus initiatives, renewable energy projects, and climate action. Strong background in environmental activism and policy.</p>
                            <div class="candidate-details">
                                <div class="candidate-detail">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Environmental Science, Year 3</span>
                                </div>
                                <div class="candidate-detail">
                                    <i class="fas fa-trophy"></i>
                                    <span>Environmental Club Leader</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="voting-actions">
                    <button type="button" class="btn-vote" id="submitVote" onclick="showConfirmation()" disabled>
                        <i class="fas fa-vote-yea"></i>
                        Submit Vote
                    </button>
                    <button type="button" class="btn-cancel" onclick="cancelVote()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Confirmation Modal -->
        <div class="modal-overlay" id="confirmationModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Confirm Your Vote</h3>
                    <p class="modal-subtitle">Please review your selection before submitting</p>
                </div>
                
                <div class="confirmation-details">
                    <div class="confirmation-candidate" id="confirmationCandidate">
                        <!-- Candidate details will be inserted here -->
                    </div>
                    <p style="color: #64748b; font-size: 0.9rem; margin-top: 1rem;">
                        <strong>Important:</strong> This action cannot be undone. Your vote will be recorded on the blockchain and cannot be changed.
                    </p>
                </div>
                
                <div class="modal-actions">
                    <button class="btn-confirm" onclick="submitVote()">
                        <i class="fas fa-check"></i>
                        Confirm Vote
                    </button>
                    <button class="btn-back" onclick="hideConfirmation()">
                        <i class="fas fa-arrow-left"></i>
                        Go Back
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Vote Receipt -->
        <div class="receipt-container" id="receiptContainer">
            <div class="receipt-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="receipt-title">Vote Successfully Cast!</h2>
            <p class="receipt-subtitle">Your vote has been securely recorded on the blockchain</p>
            
            <div class="receipt-details">
                <div class="receipt-item">
                    <span class="receipt-label">Election:</span>
                    <span class="receipt-value">Student Council President 2024</span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">Candidate:</span>
                    <span class="receipt-value" id="receiptCandidate">-</span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">Timestamp:</span>
                    <span class="receipt-value" id="receiptTimestamp">-</span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">Voter ID:</span>
                    <span class="receipt-value">STU-<?php echo sprintf('%06d', $_SESSION['user_id'] ?? 123456); ?></span>
                </div>
            </div>
            
            <div class="verification-status">
                <i class="fas fa-check-circle"></i>
                <span>Blockchain Verified</span>
            </div>
            
            <div class="receipt-code" id="receiptCode">
                <!-- Receipt code will be generated here -->
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="btn-vote" onclick="downloadReceipt()">
                    <i class="fas fa-download"></i>
                    Download Receipt
                </button>
                <button class="btn-cancel" onclick="verifyVote()">
                    <i class="fas fa-shield-alt"></i>
                    Verify Vote
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let selectedCandidate = null;
        const candidates = {
            1: { name: 'Sarah Johnson', party: 'Progressive Alliance', initials: 'SJ' },
            2: { name: 'Michael Chen', party: 'Innovation Party', initials: 'MC' },
            3: { name: 'Emily Davis', party: 'Unity Coalition', initials: 'ED' },
            4: { name: 'James Rodriguez', party: 'Student First', initials: 'JR' },
            5: { name: 'Amanda Patel', party: 'Green Future', initials: 'AP' }
        };
        
        function selectCandidate(id) {
            // Remove previous selection
            document.querySelectorAll('.candidate-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked candidate
            event.currentTarget.classList.add('selected');
            selectedCandidate = id;
            
            // Enable submit button
            document.getElementById('submitVote').disabled = false;
        }
        
        function showConfirmation() {
            if (!selectedCandidate) return;
            
            const candidate = candidates[selectedCandidate];
            const confirmationDiv = document.getElementById('confirmationCandidate');
            
            confirmationDiv.innerHTML = `
                <div class="confirmation-avatar">${candidate.initials}</div>
                <div>
                    <h4 style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">${candidate.name}</h4>
                    <span style="color: #64748b; font-size: 0.9rem;">${candidate.party}</span>
                </div>
            `;
            
            document.getElementById('confirmationModal').classList.add('active');
        }
        
        function hideConfirmation() {
            document.getElementById('confirmationModal').classList.remove('active');
        }
        
        function submitVote() {
            if (!selectedCandidate) return;
            
            const candidate = candidates[selectedCandidate];
            
            // Generate blockchain transaction hash
            const transactionHash = generateTransactionHash();
            
            // Generate receipt code
            const receiptCode = generateReceiptCode();
            
            // Update receipt details
            document.getElementById('receiptCandidate').textContent = candidate.name;
            document.getElementById('receiptTimestamp').textContent = new Date().toLocaleString();
            document.getElementById('receiptCode').textContent = receiptCode;
            
            // Hide ballot and confirmation, show receipt
            document.getElementById('ballotContainer').style.display = 'none';
            document.getElementById('confirmationModal').classList.remove('active');
            document.getElementById('receiptContainer').classList.add('active');
            
            // Simulate blockchain verification
            setTimeout(() => {
                console.log('Vote recorded on blockchain:', {
                    candidate: candidate.name,
                    transactionHash: transactionHash,
                    receiptCode: receiptCode,
                    timestamp: new Date().toISOString()
                });
            }, 1000);
        }
        
        function cancelVote() {
            if (confirm('Are you sure you want to cancel? Your selection will be cleared.')) {
                // Clear selection
                document.querySelectorAll('.candidate-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedCandidate = null;
                document.getElementById('submitVote').disabled = true;
            }
        }
        
        function generateTransactionHash() {
            const chars = '0123456789abcdef';
            let hash = '0x';
            for (let i = 0; i < 64; i++) {
                hash += chars[Math.floor(Math.random() * chars.length)];
            }
            return hash;
        }
        
        function generateReceiptCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = 'VRC-';
            for (let i = 0; i < 16; i++) {
                if (i === 4 || i === 8 || i === 12) code += '-';
                code += chars[Math.floor(Math.random() * chars.length)];
            }
            return code;
        }
        
        function downloadReceipt() {
            const receiptData = {
                election: 'Student Council President 2024',
                candidate: candidates[selectedCandidate].name,
                timestamp: new Date().toISOString(),
                voterId: `STU-${<?php echo sprintf('%06d', $_SESSION['user_id'] ?? 123456); ?>}`,
                receiptCode: document.getElementById('receiptCode').textContent,
                transactionHash: generateTransactionHash(),
                blockNumber: Math.floor(Math.random() * 10000) + 8000
            };
            
            const dataStr = JSON.stringify(receiptData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = `vote-receipt-${Date.now()}.json`;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }
        
        function verifyVotingCode() {
            const votingCode = document.getElementById('votingCodeInput').value.trim();
            const codeHelp = document.getElementById('codeHelp');
            
            if (!votingCode) {
                codeHelp.textContent = 'Please enter a voting code';
                codeHelp.style.color = '#ef4444';
                return;
            }
            
            // For demo purposes, we'll accept any non-empty code
            // In production, this would validate against the database
            if (votingCode.length > 0) {
                // Code is valid, show voting interface
                document.getElementById('codeVerificationSection').style.display = 'none';
                document.getElementById('ballotContainer').style.display = 'block';
                document.getElementById('votingInstructions').style.display = 'block';
                
                // Store the verified code for later use
                window.verifiedVotingCode = votingCode;
            } else {
                codeHelp.textContent = 'Invalid voting code. Please try again.';
                codeHelp.style.color = '#ef4444';
            }
        }
        
        function verifyVote() {
            const receiptCode = document.getElementById('receiptCode').textContent;
            // Redirect to verification page
            window.location.href = `index.php?page=verify&code=${receiptCode}`;
        }
        
        // Close modal when clicking outside
        document.getElementById('confirmationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideConfirmation();
            }
        });
    </script>
</body>
</html>
