<?php
/**
 * IUC Voting System - Welcoming Page
 * Main landing page with candidate campaigns
 */

require_once 'includes/header.php';

// Get candidates from database (we'll create this functionality)
$candidates = [];
try {
    require_once 'config/config.php';
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE status = 'approved' ORDER BY created_at DESC");
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If database fails, show placeholder candidates
    $candidates = [
        [
            'id' => 1,
            'name' => 'Sarah Johnson',
            'position' => 'Student Council President',
            'manifesto' => 'Dedicated to improving student life and creating more opportunities for everyone.',
            'image' => 'candidate1.jpg',
            'campaign_slogan' => 'Your Voice, Your Future'
        ],
        [
            'id' => 2,
            'name' => 'Michael Chen',
            'position' => 'Academic Representative',
            'manifesto' => 'Focused on academic excellence and better learning resources for all students.',
            'image' => 'candidate2.jpg',
            'campaign_slogan' => 'Excellence in Education'
        ]
    ];
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="fade-in">
            <h1>Welcome to IUC Voting System</h1>
            <p>Your voice matters. Vote for the future of our institution.</p>
            <div class="hero-buttons">
                <a href="index.php?page=register" class="btn btn-primary btn-large">
                    <i class="fas fa-user-plus"></i> Register to Vote
                </a>
                <a href="#candidates" class="btn btn-secondary btn-large">
                    <i class="fas fa-users"></i> View Candidates
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Quick Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">2,500+</div>
                    <div class="stat-label">Registered Voters</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">15</div>
                    <div class="stat-label">Active Elections</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Trust Score</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Secure Voting</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Candidates Campaign Section -->
<section id="candidates" class="candidates-section">
    <div class="container">
        <div class="section-header">
            <h2>Election Candidates</h2>
            <p>Meet the candidates running for various positions</p>
        </div>
        
        <?php if (empty($candidates)): ?>
            <div class="no-candidates">
                <i class="fas fa-users"></i>
                <h3>No Active Candidates Yet</h3>
                <p>Candidates will be displayed here once the election period begins.</p>
            </div>
        <?php else: ?>
            <div class="candidates-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate-card">
                        <div class="candidate-image">
                            <?php if (!empty($candidate['image']) && file_exists('assets/images/' . $candidate['image'])): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($candidate['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                            <?php else: ?>
                                <div class="candidate-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="candidate-info">
                            <h3><?php echo htmlspecialchars($candidate['name']); ?></h3>
                            <div class="candidate-position"><?php echo htmlspecialchars($candidate['position'] ?? 'Candidate'); ?></div>
                            <div class="candidate-slogan"><?php echo htmlspecialchars($candidate['campaign_slogan'] ?? ''); ?></div>
                            <p class="candidate-manifesto"><?php echo htmlspecialchars($candidate['manifesto'] ?? ''); ?></p>
                            <div class="candidate-actions">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Profile
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="selectCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-vote-yea"></i> Select
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Our Platform?</h2>
            <p>Experience secure, transparent, and efficient voting</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Voting</h3>
                <p>Advanced blockchain technology ensures your vote remains secure and anonymous</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Real-time Results</h3>
                <p>Track election progress and view results instantly as votes are cast</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Vote from any device with our responsive design</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Transparent Process</h3>
                <p>Every vote is recorded on the blockchain for complete transparency</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Make Your Voice Heard?</h2>
            <p>Join thousands of students who are already part of our democratic voting system</p>
            <div class="cta-buttons">
                <a href="index.php?page=register" class="btn btn-primary btn-large">
                    <i class="fas fa-user-plus"></i> Register Now
                </a>
                <a href="index.php?page=login" class="btn btn-secondary btn-large">
                    <i class="fas fa-sign-in-alt"></i> Login to Vote
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
    document.getElementById('navbar-menu').classList.toggle('active');
});

// Candidate interaction functions
function viewCandidate(candidateId) {
    // Redirect to candidate profile page
    window.location.href = 'index.php?page=candidate&id=' + candidateId;
}

function selectCandidate(candidateId) {
    // Store selection and redirect to voting page
    localStorage.setItem('selectedCandidate', candidateId);
    window.location.href = 'index.php?page=voting';
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>
