<?php
/**
 * IUC Voting System - Elections Page
 * View all elections with real-time vote counts and turnout percentages
 */

// Load elections from database
require_once 'includes/election.php';
$electionManager = new Election();
$allElections = $electionManager->getAllElections();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(135deg, #0B3C5D, #3282B8);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-item {
            display: block;
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .sidebar-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #BBE1FA;
        }
        
        .sidebar-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #BBE1FA;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .admin-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0B3C5D;
        }
        
        .elections-container {
            display: grid;
            gap: 2rem;
        }
        
        .election-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .election-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .election-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .election-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .election-subtitle {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .election-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-closed {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .election-content {
            padding: 1.5rem;
        }
        
        .vote-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .vote-stat {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .vote-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.5rem;
        }
        
        .vote-label {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .progress-container {
            margin-bottom: 1.5rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #374151;
        }
        
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3282B8, #0B3C5D);
            border-radius: 6px;
            transition: width 0.3s ease;
        }
        
        .election-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-item i {
            color: #3282B8;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(50, 130, 184, 0.3);
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .filter-tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: #64748b;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .filter-tab:hover {
            color: #3282B8;
        }
        
        .filter-tab.active {
            color: #3282B8;
        }
        
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3282B8;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .vote-stats {
                grid-template-columns: 1fr;
            }
            
            .election-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="sidebar-title">Admin Panel</div>
                <div class="sidebar-subtitle">IUC Voting System</div>
            </div>
            
            <nav class="sidebar-menu">
                <!-- Dashboard -->
                <a href="index.php?page=admin" class="sidebar-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                
                <!-- Election Management -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        ELECTION MANAGEMENT
                    </div>
                </div>
                <a href="#" class="sidebar-item active">
                    <i class="fas fa-vote-yea"></i>
                    Elections
                </a>
                <a href="index.php?page=results" class="sidebar-item">
                    <i class="fas fa-chart-bar"></i>
                    Results
                </a>
                
                <!-- Voter Management -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        VOTER MANAGEMENT
                    </div>
                </div>
                <a href="index.php?page=voter_registration" class="sidebar-item">
                    <i class="fas fa-user-plus"></i>
                    Register Voters
                </a>
                <a href="index.php?page=voter_list" class="sidebar-item">
                    <i class="fas fa-users-cog"></i>
                    Voter List
                </a>
                
                <!-- Blockchain -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        BLOCKCHAIN
                    </div>
                </div>
                <a href="index.php?page=blockchain_explorer" class="sidebar-item">
                    <i class="fas fa-link"></i>
                    Blockchain Explorer
                </a>
                <a href="index.php?page=transaction_monitor" class="sidebar-item">
                    <i class="fas fa-exchange-alt"></i>
                    Transaction Monitor
                </a>
                <a href="index.php?page=node_status" class="sidebar-item">
                    <i class="fas fa-server"></i>
                    Node Status
                </a>
                
                <!-- Security -->
                <div style="padding: 0.5rem 1.5rem; margin: 1rem 0;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                        SECURITY
                    </div>
                </div>
                <a href="index.php?page=security_center" class="sidebar-item">
                    <i class="fas fa-shield-alt"></i>
                    Security Center
                </a>
                <a href="index.php?page=authentication_logs" class="sidebar-item">
                    <i class="fas fa-fingerprint"></i>
                    Authentication Logs
                </a>
                <a href="index.php?page=access_control" class="sidebar-item">
                    <i class="fas fa-lock"></i>
                    Access Control
                </a>
                
                <div style="margin-top: 2rem; padding: 0 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                    <a href="index.php?page=admin_login" class="sidebar-item" style="color: #fbbf24;">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <div>
                    <h1 class="admin-title">Elections</h1>
                    <p style="color: #64748b; margin: 0;">Manage and monitor all voting elections</p>
                </div>
                <a href="#" class="btn-primary" onclick="openCreateElectionModal()">
                    <i class="fas fa-plus"></i>
                    Create Election
                </a>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterElections('all')">All Elections</button>
                <button class="filter-tab" onclick="filterElections('active')">Active</button>
                <button class="filter-tab" onclick="filterElections('upcoming')">Upcoming</button>
                <button class="filter-tab" onclick="filterElections('closed')">Closed</button>
            </div>
            
            <!-- Elections Container -->
            <div class="elections-container" id="electionsContainer">
                <?php if (empty($allElections)): ?>
                    <div style="text-align:center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-vote-yea" style="font-size:3rem; margin-bottom:1rem; display:block; opacity:0.3;"></i>
                        <p>No elections found. Create your first election using the button above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allElections as $elec): ?>
                        <?php
                            $status = $elec['status'];
                            $now = date('Y-m-d');
                            // Normalise display status based on dates too
                            if ($status === 'active' && $elec['end_date'] < $now) {
                                $displayStatus = 'closed';
                            } elseif ($status === 'active' && $elec['start_date'] > $now) {
                                $displayStatus = 'upcoming';
                            } else {
                                $displayStatus = $status; // active | upcoming | draft | closed
                            }
                            $statusClass = match($displayStatus) {
                                'active'   => 'status-active',
                                'upcoming', 'draft' => 'status-upcoming',
                                default    => 'status-closed',
                            };
                            $totalVotes = (int)$elec['total_votes'];
                            $totalCandidates = (int)$elec['total_candidates'];
                        ?>
                        <div class="election-card" data-status="<?php echo htmlspecialchars($displayStatus); ?>">
                            <div class="election-header">
                                <div>
                                    <h3 class="election-title"><?php echo htmlspecialchars($elec['title']); ?></h3>
                                    <p class="election-subtitle"><?php echo htmlspecialchars($elec['description'] ?? ''); ?></p>
                                </div>
                                <span class="election-status <?php echo $statusClass; ?>"><?php echo ucfirst($displayStatus); ?></span>
                            </div>

                            <div class="election-content">
                                <div class="vote-stats">
                                    <div class="vote-stat">
                                        <div class="vote-number"><?php echo $totalVotes; ?></div>
                                        <div class="vote-label">Total Votes</div>
                                    </div>
                                    <div class="vote-stat">
                                        <div class="vote-number"><?php echo $totalCandidates; ?></div>
                                        <div class="vote-label">Candidates</div>
                                    </div>
                                    <div class="vote-stat">
                                        <div class="vote-number"><?php echo ucfirst($displayStatus); ?></div>
                                        <div class="vote-label">Status</div>
                                    </div>
                                </div>

                                <div class="election-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Start: <?php echo date('M d, Y', strtotime($elec['start_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span>End: <?php echo date('M d, Y', strtotime($elec['end_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $totalCandidates; ?> Candidate<?php echo $totalCandidates !== 1 ? 's' : ''; ?></span>
                                    </div>
                                    <?php if (!empty($elec['created_by_name'])): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-user-shield"></i>
                                        <span>By: <?php echo htmlspecialchars($elec['created_by_name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                                    <a href="index.php?page=results&election_id=<?php echo $elec['id']; ?>" class="btn-primary" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                                        <i class="fas fa-chart-bar"></i> Results
                                    </a>
                                    <?php if ($displayStatus !== 'closed'): ?>
                                    <button onclick="updateStatus(<?php echo $elec['id']; ?>, 'closed')" class="btn-primary" style="font-size:0.85rem; padding:0.4rem 0.9rem; background:linear-gradient(135deg,#ef4444,#b91c1c);">
                                        <i class="fas fa-stop-circle"></i> Close
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($displayStatus === 'draft' || $displayStatus === 'upcoming'): ?>
                                    <button onclick="updateStatus(<?php echo $elec['id']; ?>, 'active')" class="btn-primary" style="font-size:0.85rem; padding:0.4rem 0.9rem; background:linear-gradient(135deg,#10b981,#065f46);">
                                        <i class="fas fa-play-circle"></i> Activate
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Election Modal -->
    <div id="createElectionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Election</h2>
                <span class="close" onclick="closeCreateElectionModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createElectionForm">
                    <div class="form-group">
                        <label for="electionTitle">Election Title</label>
                        <input type="text" id="electionTitle" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="electionDescription">Description</label>
                        <textarea id="electionDescription" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="upcoming">Upcoming</option>
                        </select>
                    </div>
                    
                    <h3>Candidates</h3>
                    <div id="candidatesContainer">
                        <div class="candidate-form">
                            <div class="form-group">
                                <label>Candidate Name</label>
                                <input type="text" name="candidate_name[]" required>
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="candidate_position[]" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="candidate_description[]" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-secondary" onclick="addCandidateForm()">
                        <i class="fas fa-plus"></i> Add Another Candidate
                    </button>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeCreateElectionModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Create Election</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #1e293b;
            background: #fff;
            font-family: inherit;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3282B8;
            box-shadow: 0 0 0 3px rgba(50,130,184,0.1);
        }
        
        .candidate-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            transition: background 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .form-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            text-align: right;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 5% auto;
                width: 95%;
            }
        }
    </style>
    
    <script>
        function filterElections(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter election cards
            document.querySelectorAll('.election-card').forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function openCreateElectionModal() {
            document.getElementById('createElectionModal').style.display = 'block';
        }
        
        function closeCreateElectionModal() {
            document.getElementById('createElectionModal').style.display = 'none';
        }
        
        function addCandidateForm() {
            const container = document.getElementById('candidatesContainer');
            const candidateForm = document.createElement('div');
            candidateForm.className = 'candidate-form';
            candidateForm.innerHTML = `
                <div class="form-group">
                    <label>Candidate Name</label>
                    <input type="text" name="candidate_name[]" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="candidate_position[]" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="candidate_description[]" rows="2" required></textarea>
                </div>
                <button type="button" class="btn-secondary" onclick="removeCandidateForm(this)">
                    <i class="fas fa-trash"></i> Remove Candidate
                </button>
            `;
            container.appendChild(candidateForm);
        }
        
        function removeCandidateForm(button) {
            button.parentElement.remove();
        }
        
        // Handle form submission — guard against double-submit
        let submitting = false;

        document.getElementById('createElectionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (submitting) return; // block duplicate submissions
            submitting = true;

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating…';

            const formData = new FormData(this);
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                status: formData.get('status'),
                candidates: []
            };

            const candidateNames        = formData.getAll('candidate_name[]');
            const candidatePositions    = formData.getAll('candidate_position[]');
            const candidateDescriptions = formData.getAll('candidate_description[]');

            for (let i = 0; i < candidateNames.length; i++) {
                if (candidateNames[i].trim()) {
                    data.candidates.push({
                        name:        candidateNames[i],
                        position:    candidatePositions[i],
                        description: candidateDescriptions[i]
                    });
                }
            }

            fetch('create_election.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Election created successfully!');
                    closeCreateElectionModal();
                    location.reload();
                } else {
                    alert('Error creating election: ' + result.message);
                    // Re-enable on failure so admin can retry
                    submitting = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create Election';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating election. Please try again.');
                submitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Election';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createElectionModal');
            if (event.target == modal) {
                closeCreateElectionModal();
            }
        }
    </script>
</body>
</html>
