<?php
/**
 * IUC Voting System - Compliance Report Page
 * Comprehensive compliance monitoring, regulatory reporting, and audit trail
 */

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=admin_login');
    exit;
}

// Check if user is admin
$userType = $_SESSION['user_type'] ?? '';
if ($userType !== 'admin') {
    header('Location: index.php?page=admin_login');
    exit;
}

require_once 'config/config.php';

// Compliance categories and requirements
$compliance_categories = [
    'DATA_PROTECTION' => [
        'name' => 'Data Protection',
        'requirements' => [
            'GDPR Compliance',
            'Data Encryption',
            'Access Control',
            'Data Retention Policy',
            'Privacy Impact Assessment'
        ],
        'status' => 'COMPLIANT',
        'score' => 95,
        'last_audit' => '2024-04-15',
        'next_audit' => '2024-07-15'
    ],
    'ELECTION_INTEGRITY' => [
        'name' => 'Election Integrity',
        'requirements' => [
            'Vote Anonymity',
            'One Person One Vote',
            'Transparent Counting',
            'Audit Trail',
            'Dispute Resolution'
        ],
        'status' => 'COMPLIANT',
        'score' => 98,
        'last_audit' => '2024-04-10',
        'next_audit' => '2024-07-10'
    ],
    'SECURITY_STANDARDS' => [
        'name' => 'Security Standards',
        'requirements' => [
            'ISO 27001',
            'Penetration Testing',
            'Vulnerability Assessment',
            'Security Training',
            'Incident Response'
        ],
        'status' => 'COMPLIANT',
        'score' => 92,
        'last_audit' => '2024-04-20',
        'next_audit' => '2024-07-20'
    ],
    'BLOCKCHAIN_COMPLIANCE' => [
        'name' => 'Blockchain Compliance',
        'requirements' => [
            'Smart Contract Audit',
            'Consensus Mechanism',
            'Node Security',
            'Chain Integrity',
            'Transaction Verification'
        ],
        'status' => 'COMPLIANT',
        'score' => 96,
        'last_audit' => '2024-04-18',
        'next_audit' => '2024-07-18'
    ],
    'ACCESSIBILITY' => [
        'name' => 'Accessibility',
        'requirements' => [
            'WCAG 2.1 AA',
            'Screen Reader Support',
            'Keyboard Navigation',
            'Color Contrast',
            'Alternative Text'
        ],
        'status' => 'IN_PROGRESS',
        'score' => 88,
        'last_audit' => '2024-04-12',
        'next_audit' => '2024-07-12'
    ]
];

// Generate compliance reports
$compliance_reports = [];
for ($i = 1; $i <= 20; $i++) {
    $categories = array_keys($compliance_categories);
    $category = $categories[array_rand($categories)];
    
    $compliance_reports[] = [
        'id' => 'COMP-' . str_pad($i, 6, '0', STR_PAD_LEFT),
        'category' => $category,
        'title' => generateReportTitle($category),
        'description' => generateReportDescription($category),
        'status' => ['COMPLIANT', 'NON_COMPLIANT', 'IN_PROGRESS', 'REVIEW_REQUIRED'][array_rand(['COMPLIANT', 'NON_COMPLIANT', 'IN_PROGRESS', 'REVIEW_REQUIRED'])],
        'score' => rand(70, 100),
        'generated_date' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30)),
        'generated_by' => 'admin_' . rand(1, 5),
        'reviewer' => 'compliance_officer_' . rand(1, 3),
        'findings' => rand(0, 10),
        'recommendations' => rand(0, 5),
        'deadline' => date('Y-m-d', time() + rand(86400 * 7, 86400 * 90)),
        'priority' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'][array_rand(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
        'documents' => rand(1, 5)
    ];
}

// Compliance statistics
$compliance_stats = [
    'total_reports' => count($compliance_reports),
    'compliant_reports' => count(array_filter($compliance_reports, function($r) { return $r['status'] === 'COMPLIANT'; })),
    'non_compliant_reports' => count(array_filter($compliance_reports, function($r) { return $r['status'] === 'NON_COMPLIANT'; })),
    'in_progress_reports' => count(array_filter($compliance_reports, function($r) { return $r['status'] === 'IN_PROGRESS'; })),
    'review_required_reports' => count(array_filter($compliance_reports, function($r) { return $r['status'] === 'REVIEW_REQUIRED'; })),
    'average_score' => number_format(array_sum(array_column($compliance_reports, 'score')) / count($compliance_reports), 1),
    'critical_findings' => count(array_filter($compliance_reports, function($r) { return $r['priority'] === 'CRITICAL'; })),
    'total_findings' => array_sum(array_column($compliance_reports, 'findings')),
    'total_recommendations' => array_sum(array_column($compliance_reports, 'recommendations'))
];

// Generate audit findings
$audit_findings = [];
$finding_types = ['VULNERABILITY', 'POLICY_VIOLATION', 'PROCEDURE_GAP', 'DOCUMENTATION_MISSING', 'TRAINING_REQUIRED'];
$severity_levels = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];

for ($i = 1; $i <= 15; $i++) {
    $audit_findings[] = [
        'id' => 'FIND-' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'type' => $finding_types[array_rand($finding_types)],
        'severity' => $severity_levels[array_rand($severity_levels)],
        'category' => array_keys($compliance_categories)[array_rand(array_keys($compliance_categories))],
        'title' => generateFindingTitle(),
        'description' => generateFindingDescription(),
        'identified_date' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30)),
        'identified_by' => 'auditor_' . rand(1, 3),
        'status' => ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'][array_rand(['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])],
        'resolution_date' => rand(0, 1) ? date('Y-m-d H:i:s', time() - rand(0, 86400 * 7)) : null,
        'impact' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'][array_rand(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])],
        'remediation_cost' => '$' . number_format(rand(100, 10000), 2),
        'time_to_resolve' => rand(1, 30) . ' days'
    ];
}

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

// Filter reports
if (!empty($search) || !empty($category_filter) || !empty($status_filter) || !empty($priority_filter)) {
    $filtered_reports = [];
    foreach ($compliance_reports as $report) {
        $match = true;
        
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $match = (
                strpos(strtolower($report['id']), $search_lower) !== false ||
                strpos(strtolower($report['title']), $search_lower) !== false ||
                strpos(strtolower($report['description']), $search_lower) !== false
            );
        }
        
        if (!empty($category_filter) && $report['category'] !== $category_filter) {
            $match = false;
        }
        
        if (!empty($status_filter) && $report['status'] !== $status_filter) {
            $match = false;
        }
        
        if (!empty($priority_filter) && $report['priority'] !== $priority_filter) {
            $match = false;
        }
        
        if ($match) {
            $filtered_reports[] = $report;
        }
    }
    $compliance_reports = $filtered_reports;
}

// Helper functions
function generateReportTitle($category) {
    $titles = [
        'DATA_PROTECTION' => ['Q1 2024 Data Protection Audit', 'GDPR Compliance Assessment', 'Data Privacy Impact Review'],
        'ELECTION_INTEGRITY' => ['Election Security Audit', 'Vote Integrity Assessment', 'Electoral Process Review'],
        'SECURITY_STANDARDS' => ['ISO 27001 Compliance Audit', 'Security Controls Assessment', 'Penetration Testing Report'],
        'BLOCKCHAIN_COMPLIANCE' => ['Smart Contract Audit', 'Blockchain Security Review', 'Consensus Mechanism Assessment'],
        'ACCESSIBILITY' => ['WCAG 2.1 Compliance Audit', 'Accessibility Assessment', 'Usability Review']
    ];
    
    return $titles[$category][array_rand($titles[$category])] ?? 'Compliance Report';
}

function generateReportDescription($category) {
    $descriptions = [
        'DATA_PROTECTION' => 'Comprehensive assessment of data protection measures and GDPR compliance status',
        'ELECTION_INTEGRITY' => 'Detailed review of election processes and integrity controls',
        'SECURITY_STANDARDS' => 'Evaluation of security controls against industry standards',
        'BLOCKCHAIN_COMPLIANCE' => 'Assessment of blockchain implementation and smart contract security',
        'ACCESSIBILITY' => 'Review of system accessibility and compliance with WCAG standards'
    ];
    
    return $descriptions[$category] ?? 'Compliance assessment report';
}

function generateFindingTitle() {
    $titles = [
        'Insufficient Access Controls',
        'Missing Security Documentation',
        'Outdated Encryption Standards',
        'Inadequate User Training',
        'Gaps in Audit Trail',
        'Non-compliant Data Storage',
        'Weak Password Policy',
        'Missing Backup Procedures',
        'Inadequate Incident Response',
        'Vulnerability in Smart Contract'
    ];
    
    return $titles[array_rand($titles)];
}

function generateFindingDescription() {
    $descriptions = [
        'Security controls do not meet minimum requirements for data protection',
        'Documentation is incomplete or outdated for critical security procedures',
        'Encryption standards need to be updated to meet current compliance requirements',
        'User training programs are insufficient to ensure proper security awareness',
        'Audit trail gaps prevent comprehensive monitoring of system activities',
        'Data storage practices do not comply with regulatory requirements',
        'Password policy does not meet industry best practices',
        'Backup procedures are inadequate for disaster recovery requirements',
        'Incident response plan needs improvement to meet compliance standards',
        'Smart contract vulnerabilities identified during security audit'
    ];
    
    return $descriptions[array_rand($descriptions)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Report - IUC Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 2rem;
        }

        .sidebar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-subtitle {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .sidebar-section {
            margin-bottom: 2rem;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.7;
            letter-spacing: 0.05em;
            margin: 0 1.5rem 0.5rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.25rem 0.5rem;
            border-radius: 8px;
        }

        .sidebar-item:hover {
            background: rgba(255,255,255,0.1);
            color: #f1f5f9;
            transform: translateX(4px);
        }

        .sidebar-item.active {
            background: rgba(50, 130, 184, 0.2);
            color: #60a5fa;
            border-left: 3px solid #60a5fa;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1rem;
            height: 100vh;
            overflow-y: auto;
        }

        .page-header {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.9rem;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: white;
            padding: 0.75rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-success { border-left: 4px solid #10b981; }
        .stat-warning { border-left: 4px solid #f59e0b; }
        .stat-danger { border-left: 4px solid #ef4444; }
        .stat-info { border-left: 4px solid #3b82f6; }
        .stat-primary { border-left: 4px solid #3282B8; }

        .stat-number {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .stat-success .stat-number { color: #10b981; }
        .stat-warning .stat-number { color: #f59e0b; }
        .stat-danger .stat-number { color: #ef4444; }
        .stat-info .stat-number { color: #3b82f6; }
        .stat-primary .stat-number { color: #3282B8; }

        .stat-label {
            color: #64748b;
            font-size: 0.75rem;
        }

        .compliance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .compliance-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }

        .compliance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .compliance-name {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .compliance-status {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-compliant {
            background: #d1fae5;
            color: #065f46;
        }

        .status-non-compliant {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-in-progress {
            background: #fef3c7;
            color: #92400e;
        }

        .compliance-score {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0B3C5D;
            text-align: center;
            margin: 0.5rem 0;
        }

        .compliance-requirements {
            margin-top: 0.75rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .requirement-check {
            color: #10b981;
        }

        .compliance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }

        .compliance-table th {
            background: #f8fafc;
            padding: 0.5rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
        }

        .compliance-table td {
            padding: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8rem;
        }

        .compliance-table tr:hover {
            background: #f8fafc;
        }

        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .priority-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .priority-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-high {
            background: #fed7aa;
            color: #9a3412;
        }

        .priority-critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .search-filter-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .search-input, .filter-select {
            padding: 0.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
        }

        .filter-select {
            min-width: 120px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3282B8, #0B3C5D);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .findings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 0.75rem;
        }

        .finding-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
        }

        .finding-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .finding-title {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .finding-severity {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .severity-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .severity-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .severity-high {
            background: #fed7aa;
            color: #9a3412;
        }

        .severity-critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .finding-description {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .finding-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #64748b;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #dcfce7;
            color: #166534;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .search-filter-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">
                    <i class="fas fa-vote-yea"></i>
                    IUC Voting System
                </h1>
                <p class="sidebar-subtitle">Admin Panel</p>
            </div>
            
            <!-- Navigation -->
            <nav class="sidebar-nav">
                <!-- Dashboard -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">MAIN</div>
                    <a href="index.php?page=admin" class="sidebar-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                
                <!-- Election Management -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">ELECTION MANAGEMENT</div>
                    <a href="index.php?page=elections" class="sidebar-item">
                        <i class="fas fa-poll"></i>
                        Elections
                    </a>
                    <a href="index.php?page=results" class="sidebar-item">
                        <i class="fas fa-chart-bar"></i>
                        Results
                    </a>
                </div>
                
                <!-- Voter Management -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">VOTER MANAGEMENT</div>
                    <a href="index.php?page=voter_registration" class="sidebar-item">
                        <i class="fas fa-user-plus"></i>
                        Register Voters
                    </a>
                    <a href="index.php?page=voter_list" class="sidebar-item">
                        <i class="fas fa-users-cog"></i>
                        Voter List
                    </a>
                </div>
                
                <!-- Blockchain -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">BLOCKCHAIN</div>
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
                </div>
                
                <!-- Security -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">SECURITY</div>
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
                </div>
                
                <!-- Analytics -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">ANALYTICS</div>
                    <a href="index.php?page=realtime_voting" class="sidebar-item">
                        <i class="fas fa-chart-line"></i>
                        Real-time Voting
                    </a>
                    <a href="index.php?page=participants" class="sidebar-item">
                        <i class="fas fa-users"></i>
                        Participants
                    </a>
                </div>
                
                <!-- Audit -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">AUDIT</div>
                    <a href="index.php?page=audit_logs" class="sidebar-item">
                        <i class="fas fa-history"></i>
                        Audit Logs
                    </a>
                    <a href="index.php?page=vote_verification" class="sidebar-item">
                        <i class="fas fa-check-circle"></i>
                        Vote Verification
                    </a>
                    <a href="index.php?page=compliance_report" class="sidebar-item active">
                        <i class="fas fa-file-alt"></i>
                        Compliance Report
                    </a>
                </div>
                
                <!-- Logout -->
                <div class="sidebar-section">
                    <a href="index.php?page=logout" class="sidebar-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 class="page-title">Compliance Report</h1>
                        <p class="page-subtitle">Comprehensive compliance monitoring and regulatory reporting</p>
                    </div>
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        <span>LIVE</span>
                    </div>
                </div>
            </div>
            
            <!-- Compliance Statistics -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Compliance Overview
                    </h2>
                    <div>
                        <button class="btn btn-success">
                            <i class="fas fa-download"></i>
                            Export Report
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-number"><?php echo $compliance_stats['compliant_reports']; ?></div>
                        <div class="stat-label">Compliant</div>
                    </div>
                    <div class="stat-card stat-danger">
                        <div class="stat-number"><?php echo $compliance_stats['non_compliant_reports']; ?></div>
                        <div class="stat-label">Non-Compliant</div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-number"><?php echo $compliance_stats['in_progress_reports']; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-number"><?php echo $compliance_stats['review_required_reports']; ?></div>
                        <div class="stat-label">Review Required</div>
                    </div>
                    <div class="stat-card stat-primary">
                        <div class="stat-number"><?php echo $compliance_stats['average_score']; ?>%</div>
                        <div class="stat-label">Average Score</div>
                    </div>
                    <div class="stat-card stat-danger">
                        <div class="stat-number"><?php echo $compliance_stats['critical_findings']; ?></div>
                        <div class="stat-label">Critical Findings</div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-number"><?php echo $compliance_stats['total_findings']; ?></div>
                        <div class="stat-label">Total Findings</div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-number"><?php echo $compliance_stats['total_recommendations']; ?></div>
                        <div class="stat-label">Recommendations</div>
                    </div>
                </div>
            </div>
            
            <!-- Compliance Categories -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-shield-alt"></i>
                        Compliance Categories
                    </h2>
                </div>
                
                <div class="compliance-grid">
                    <?php foreach ($compliance_categories as $category_key => $category): ?>
                        <div class="compliance-card">
                            <div class="compliance-header">
                                <div class="compliance-name"><?php echo $category['name']; ?></div>
                                <span class="compliance-status status-<?php echo strtolower(str_replace('_', '-', $category['status'])); ?>">
                                    <?php echo str_replace('_', ' ', $category['status']); ?>
                                </span>
                            </div>
                            
                            <div class="compliance-score"><?php echo $category['score']; ?>%</div>
                            
                            <div class="compliance-requirements">
                                <?php foreach ($category['requirements'] as $requirement): ?>
                                    <div class="requirement-item">
                                        <i class="fas fa-check-circle requirement-check"></i>
                                        <span><?php echo $requirement; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #64748b;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span>Last Audit:</span>
                                    <span><?php echo date('M d, Y', strtotime($category['last_audit'])); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Next Audit:</span>
                                    <span><?php echo date('M d, Y', strtotime($category['next_audit'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Audit Findings -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Recent Audit Findings
                    </h2>
                </div>
                
                <div class="findings-grid">
                    <?php foreach (array_slice($audit_findings, 0, 6) as $finding): ?>
                        <div class="finding-card">
                            <div class="finding-header">
                                <div class="finding-title"><?php echo $finding['title']; ?></div>
                                <span class="finding-severity severity-<?php echo strtolower($finding['severity']); ?>">
                                    <?php echo $finding['severity']; ?>
                                </span>
                            </div>
                            
                            <div class="finding-description"><?php echo $finding['description']; ?></div>
                            
                            <div class="finding-meta">
                                <span><?php echo $finding['category']; ?></span>
                                <span><?php echo date('M d, Y', strtotime($finding['identified_date'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Compliance Reports -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-alt"></i>
                        Compliance Reports
                        <span style="font-size: 0.9rem; color: #64748b; margin-left: 0.5rem;">
                            (<?php echo count($compliance_reports); ?> reports)
                        </span>
                    </h2>
                </div>
                
                <!-- Search and Filter -->
                <form method="GET" class="search-filter-bar">
                    <input type="text" name="search" class="search-input" placeholder="Search by ID, title, or description..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($compliance_categories as $category_key => $category): ?>
                            <option value="<?php echo $category_key; ?>" <?php echo $category_filter === $category_key ? 'selected' : ''; ?>><?php echo $category['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="COMPLIANT" <?php echo $status_filter === 'COMPLIANT' ? 'selected' : ''; ?>>Compliant</option>
                        <option value="NON_COMPLIANT" <?php echo $status_filter === 'NON_COMPLIANT' ? 'selected' : ''; ?>>Non-Compliant</option>
                        <option value="IN_PROGRESS" <?php echo $status_filter === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="REVIEW_REQUIRED" <?php echo $status_filter === 'REVIEW_REQUIRED' ? 'selected' : ''; ?>>Review Required</option>
                    </select>
                    
                    <select name="priority" class="filter-select">
                        <option value="">All Priorities</option>
                        <option value="LOW" <?php echo $priority_filter === 'LOW' ? 'selected' : ''; ?>>Low</option>
                        <option value="MEDIUM" <?php echo $priority_filter === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                        <option value="HIGH" <?php echo $priority_filter === 'HIGH' ? 'selected' : ''; ?>>High</option>
                        <option value="CRITICAL" <?php echo $priority_filter === 'CRITICAL' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    
                    <a href="index.php?page=compliance_report" class="btn btn-primary">
                        <i class="fas fa-redo"></i>
                        Clear
                    </a>
                </form>
                
                <?php if (count($compliance_reports) > 0): ?>
                    <table class="compliance-table">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Priority</th>
                                <th>Findings</th>
                                <th>Generated</th>
                                <th>Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compliance_reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['id']; ?></td>
                                    <td><?php echo $compliance_categories[$report['category']]['name']; ?></td>
                                    <td><?php echo $report['title']; ?></td>
                                    <td>
                                        <span class="compliance-status status-<?php echo strtolower(str_replace('_', '-', $report['status'])); ?>">
                                            <?php echo str_replace('_', ' ', $report['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $report['score']; ?>%</td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo strtolower($report['priority']); ?>">
                                            <?php echo $report['priority']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $report['findings']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($report['generated_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($report['deadline'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button class="btn btn-primary btn-sm" onclick="viewReport('<?php echo $report['id']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-success btn-sm" onclick="downloadReport('<?php echo $report['id']; ?>')">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No compliance reports found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function viewReport(reportId) {
            console.log('Viewing report:', reportId);
            // In production, this would open the report details or navigate to report page
            alert('Report details would be displayed here for report: ' + reportId);
        }
        
        function downloadReport(reportId) {
            console.log('Downloading report:', reportId);
            // In production, this would download the report file
            alert('Report download would be initiated for report: ' + reportId);
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            console.log('Refreshing compliance data...');
            // In production, this would fetch fresh compliance data
        }, 30000);
        
        // Simulate live updates
        setInterval(function() {
            const liveIndicator = document.querySelector('.live-dot');
            if (liveIndicator) {
                liveIndicator.style.background = liveIndicator.style.background === 'rgb(34, 197, 94)' ? '#ef4444' : '#22c55e';
            }
        }, 2000);
    </script>
</body>
</html>
