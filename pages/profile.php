<?php
/**
 * IUC Voting System - Student Profile Page
 * Student profile management
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'config/config.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Get student profile
$stmt = $pdo->prepare("SELECT s.student_id, s.department, s.level FROM students s WHERE s.user_id = ?");
$stmt->execute([$userId]);
$studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $department = $_POST['department'];
    $level = $_POST['level'];
    
    $stmt = $pdo->prepare("UPDATE students SET department = ?, level = ? WHERE user_id = ?");
    $result = $stmt->execute([$department, $level, $userId]);
    
    if ($result) {
        $success = "Profile updated successfully!";
        // Refresh student profile data
        $stmt = $pdo->prepare("SELECT s.student_id, s.department, s.level FROM students s WHERE s.user_id = ?");
        $stmt->execute([$userId]);
        $studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Failed to update profile";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - IUC Voting System</title>
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
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .back-btn {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(107, 114, 128, 0.4);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            color: white;
            padding: 1.5rem;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .card-content {
            padding: 2rem;
        }

        .profile-info {
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 600;
            margin: 0 auto 1.5rem;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .profile-email {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3282B8;
            box-shadow: 0 0 0 3px rgba(50, 130, 184, 0.1);
        }

        .form-group input:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0B3C5D 0%, #3282B8 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(11, 60, 93, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .voting-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>My Profile</h1>
                <p>Manage your profile information and voting activity</p>
            </div>
            <a href="index.php?page=dashboard" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Profile Info Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                </div>
                <div class="card-content">
                    <div class="profile-info">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($userName, 0, 2)); ?>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($userEmail); ?></div>
                        
                        <div class="profile-details">
                            <div class="detail-item">
                                <span class="detail-label">Student ID:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($studentProfile['student_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Department:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($studentProfile['department']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Level:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($studentProfile['level']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Account Type:</span>
                                <span class="detail-value">Student</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Card -->
            <div class="card">
                <div class="card-header">
                    <h2>Edit Profile</h2>
                </div>
                <div class="card-content">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName); ?>" disabled>
                            <small style="color: #666;">Name cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
                            <small style="color: #666;">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="student_id">Student ID</label>
                            <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($studentProfile['student_id']); ?>" disabled>
                            <small style="color: #666;">Student ID cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department" required>
                                <option value="Computer Science" <?php echo $studentProfile['department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                <option value="Business Administration" <?php echo $studentProfile['department'] === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                <option value="Engineering" <?php echo $studentProfile['department'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                <option value="Medicine" <?php echo $studentProfile['department'] === 'Medicine' ? 'selected' : ''; ?>>Medicine</option>
                                <option value="Law" <?php echo $studentProfile['department'] === 'Law' ? 'selected' : ''; ?>>Law</option>
                                <option value="Arts" <?php echo $studentProfile['department'] === 'Arts' ? 'selected' : ''; ?>>Arts</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="level">Level</label>
                            <select id="level" name="level" required>
                                <option value="100" <?php echo $studentProfile['level'] === '100' ? 'selected' : ''; ?>>100 Level</option>
                                <option value="200" <?php echo $studentProfile['level'] === '200' ? 'selected' : ''; ?>>200 Level</option>
                                <option value="300" <?php echo $studentProfile['level'] === '300' ? 'selected' : ''; ?>>300 Level</option>
                                <option value="400" <?php echo $studentProfile['level'] === '400' ? 'selected' : ''; ?>>400 Level</option>
                                <option value="500" <?php echo $studentProfile['level'] === '500' ? 'selected' : ''; ?>>500 Level</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Voting Activity Card -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2>Voting Activity</h2>
            </div>
            <div class="card-content">
                <?php
                require_once 'includes/election.php';
                $election = new Election();
                $userVotes = $election->getUserVotes($userId);
                ?>
                
                <div class="voting-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($userVotes); ?></div>
                        <div class="stat-label">Total Votes Cast</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php 
                            $activeElections = $election->getActiveElections();
                            $eligibleToVote = 0;
                            foreach ($activeElections as $elec) {
                                if ($election->canUserVote($userId) && !$election->hasUserVoted($userId, $elec['id'])) {
                                    $eligibleToVote++;
                                }
                            }
                            echo $eligibleToVote;
                            ?>
                        </div>
                        <div class="stat-label">Eligible to Vote</div>
                    </div>
                </div>

                <?php if (!empty($userVotes)): ?>
                    <h3 style="margin-top: 1.5rem; margin-bottom: 1rem;">Recent Voting History</h3>
                    <?php foreach (array_slice($userVotes, 0, 3) as $vote): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 10px; margin-bottom: 0.5rem;">
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($vote['election_title']); ?></div>
                            <div style="color: #666; font-size: 0.9rem;">
                                Voted for: <?php echo htmlspecialchars($vote['candidate_name']); ?> • 
                                <?php echo date('M d, Y', strtotime($vote['voted_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">You haven't voted in any elections yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
