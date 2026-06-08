<?php
/**
 * IUC Voting System - Welcome Page
 */
require_once 'config/config.php';

// Real stats
$totalStudents  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE type='student' AND status='approved'")->fetchColumn();
$totalVotes     = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalElections = (int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IUC Voting System - Secure Digital Democracy</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;color:#1e293b;background:#fff}

/*  Navbar  */
.navbar{position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #e2e8f0;padding:0 5%;height:70px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.nav-brand{display:flex;align-items:center;gap:.75rem;text-decoration:none}
.nav-brand .logo{width:42px;height:42px;background:linear-gradient(135deg,#0B3C5D,#3282B8);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
.nav-brand h1{font-size:1.1rem;font-weight:800;color:#0B3C5D;line-height:1.1}
.nav-brand p{font-size:.72rem;color:#64748b}
.nav-links{display:flex;align-items:center;gap:2rem}
.nav-links a{text-decoration:none;color:#374151;font-size:.9rem;font-weight:500;transition:color .2s}
.nav-links a:hover{color:#0B3C5D}
.nav-links a.active{color:#0B3C5D;font-weight:700;border-bottom:2px solid #3282B8;padding-bottom:2px}
.nav-actions{display:flex;align-items:center;gap:.75rem}
.btn{padding:.6rem 1.4rem;border-radius:8px;font-weight:600;font-size:.875rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;transition:all .2s;border:2px solid transparent;cursor:pointer}
.btn-outline{border-color:#0B3C5D;color:#0B3C5D;background:#fff}
.btn-outline:hover{background:#0B3C5D;color:#fff}
.btn-primary{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;border:none}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(11,60,93,.35)}
.btn-lg{padding:.85rem 2rem;font-size:1rem;border-radius:10px}
.btn-white{background:#fff;color:#0B3C5D;border:2px solid #fff}
.btn-white:hover{background:transparent;color:#fff}
.btn-outline-white{background:transparent;color:#fff;border:2px solid rgba(255,255,255,.6)}
.btn-outline-white:hover{background:rgba(255,255,255,.15)}

/*  Hero  */
.hero{background:linear-gradient(135deg,#0B3C5D 0%,#1a5276 50%,#3282B8 100%);color:#fff;padding:5rem 5%;min-height:90vh;display:flex;align-items:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.hero-inner{max-width:1200px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center}
.hero-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);padding:.4rem 1rem;border-radius:20px;font-size:.78rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;margin-bottom:1.5rem}
.hero h1{font-size:3rem;font-weight:800;line-height:1.15;margin-bottom:1.25rem}
.hero h1 span{color:#BBE1FA}
.hero p{font-size:1.05rem;opacity:.85;line-height:1.7;margin-bottom:2rem;max-width:480px}
.hero-actions{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2.5rem}
.hero-social-proof{display:flex;align-items:center;gap:1rem}
.avatars{display:flex}
.avatars div{width:36px;height:36px;border-radius:50%;border:2px solid rgba(255,255,255,.5);background:linear-gradient(135deg,#BBE1FA,#3282B8);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:#0B3C5D;margin-left:-8px}
.avatars div:first-child{margin-left:0}
.social-text{font-size:.82rem;opacity:.85}
.social-text strong{display:block;font-size:.9rem}

/* Hero card mockup */
.hero-mockup{display:flex;justify-content:center;align-items:center}
.mockup-card{background:#fff;border-radius:20px;padding:1.5rem;box-shadow:0 25px 60px rgba(0,0,0,.3);width:100%;max-width:380px;color:#1e293b}
.mockup-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
.mockup-header h3{font-size:1rem;font-weight:700;color:#0B3C5D}
.mockup-stat{background:linear-gradient(135deg,#0B3C5D,#3282B8);border-radius:12px;padding:1rem 1.25rem;color:#fff;margin-bottom:.75rem;display:flex;align-items:center;justify-content:space-between}
.mockup-stat .num{font-size:1.6rem;font-weight:800}
.mockup-stat .lbl{font-size:.75rem;opacity:.8;margin-top:.1rem}
.mockup-stat .icon{font-size:1.8rem;opacity:.6}
.mockup-elections{margin-top:1rem}
.mockup-elections h4{font-size:.78rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem}
.mockup-election-item{display:flex;align-items:center;justify-content:space-between;padding:.6rem .75rem;background:#f8fafc;border-radius:8px;margin-bottom:.5rem;font-size:.82rem}
.mockup-election-item:last-child{margin-bottom:0}
.pill-active{background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:20px;font-size:.7rem;font-weight:700}
.pill-soon{background:#dbeafe;color:#1e40af;padding:.2rem .6rem;border-radius:20px;font-size:.7rem;font-weight:700}

/*  How It Works  */
.hiw{padding:5rem 5%;background:#f8fafc}
.section-label{display:inline-block;background:#dbeafe;color:#1e40af;padding:.3rem .9rem;border-radius:20px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:1rem}
.section-title{font-size:2rem;font-weight:800;color:#0B3C5D;margin-bottom:.6rem}
.section-sub{color:#64748b;font-size:1rem;max-width:520px;margin:0 auto}
.text-center{text-align:center}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.5rem;margin-top:3rem;position:relative}
.steps::before{content:'';position:absolute;top:32px;left:10%;right:10%;height:2px;background:repeating-linear-gradient(90deg,#3282B8 0,#3282B8 8px,transparent 8px,transparent 16px);z-index:0}
.step{text-align:center;position:relative;z-index:1}
.step-num{width:64px;height:64px;border-radius:50%;background:#fff;border:3px solid #3282B8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#3282B8;box-shadow:0 4px 14px rgba(50,130,184,.2)}
.step:nth-child(odd) .step-num{background:linear-gradient(135deg,#0B3C5D,#3282B8);color:#fff;border:none}
.step h3{font-size:.95rem;font-weight:700;color:#0B3C5D;margin-bottom:.4rem}
.step p{font-size:.82rem;color:#64748b;line-height:1.5}

/*  Features  */
.features{padding:5rem 5%;background:#fff}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-top:3rem}
.feature-card{background:#f8fafc;border-radius:16px;padding:1.75rem;border:1px solid #e2e8f0;transition:all .2s}
.feature-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(11,60,93,.1);border-color:#3282B8}
.feature-icon{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#1e40af;margin-bottom:1rem}
.feature-card h3{font-size:1rem;font-weight:700;color:#0B3C5D;margin-bottom:.5rem}
.feature-card p{font-size:.85rem;color:#64748b;line-height:1.6}

/*  Stats bar  */
.stats-bar{background:linear-gradient(135deg,#0B3C5D,#1a5276);color:#fff;padding:3rem 5%}
.stats-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr auto 1fr auto 1fr auto 1fr;align-items:center;gap:1rem}
.stat-item{text-align:center}
.stat-item .num{font-size:2.2rem;font-weight:800;color:#BBE1FA}
.stat-item .lbl{font-size:.85rem;opacity:.8;margin-top:.2rem}
.stat-divider{width:1px;height:60px;background:rgba(255,255,255,.2)}
.stats-left{max-width:280px}
.stats-left h2{font-size:1.6rem;font-weight:800;margin-bottom:.5rem}
.stats-left p{font-size:.875rem;opacity:.75;line-height:1.6}

/*  CTA  */
.cta{padding:5rem 5%;background:#f0f7ff;text-align:center}
.cta h2{font-size:2.2rem;font-weight:800;color:#0B3C5D;margin-bottom:.75rem}
.cta p{color:#64748b;font-size:1rem;max-width:480px;margin:0 auto 2rem}
.cta-actions{display:flex;justify-content:center;gap:1rem;flex-wrap:wrap}

/*  Footer  */
footer{background:#0B3C5D;color:#fff;padding:3rem 5% 1.5rem}
.footer-top{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr 1.2fr;gap:2.5rem;margin-bottom:2.5rem}
.footer-brand .logo{width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:.75rem}
.footer-brand h3{font-size:1rem;font-weight:800;margin-bottom:.5rem}
.footer-brand p{font-size:.82rem;opacity:.7;line-height:1.6;margin-bottom:1rem}
.social-links{display:flex;gap:.6rem}
.social-links a{width:34px;height:34px;background:rgba(255,255,255,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-size:.9rem;transition:all .2s}
.social-links a:hover{background:rgba(255,255,255,.2)}
.footer-col h4{font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:.55;margin-bottom:1rem}
.footer-col ul{list-style:none}
.footer-col ul li{margin-bottom:.5rem}
.footer-col ul li a{color:rgba(255,255,255,.75);text-decoration:none;font-size:.85rem;transition:color .2s}
.footer-col ul li a:hover{color:#fff}
.footer-col .contact-item{display:flex;align-items:center;gap:.5rem;color:rgba(255,255,255,.75);font-size:.85rem;margin-bottom:.6rem}
.footer-col .contact-item i{color:#BBE1FA;width:16px}
.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:1.25rem;text-align:center;font-size:.8rem;opacity:.55}

@media(max-width:900px){
.hero-inner{grid-template-columns:1fr;text-align:center}
.hero h1{font-size:2.2rem}
.hero-mockup{display:none}
.hero p,.hero-actions{margin:0 auto 1.5rem;justify-content:center}
.steps::before{display:none}
.footer-top{grid-template-columns:1fr 1fr}
.stats-inner{grid-template-columns:1fr 1fr;gap:2rem}
.stat-divider{display:none}
.stats-left{max-width:100%;grid-column:1/-1}
.nav-links{display:none}
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="nav-brand" href="index.php">
        <div class="logo"><i class="fas fa-shield-alt"></i></div>
        <div><h1>IUC Voting</h1><p>Secure Digital Democracy</p></div>
    </a>
    <div class="nav-links">
        <a href="#" class="active">Home</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#features">Features</a>
        <a href="index.php?page=contact">Contact</a>
    </div>
    <div class="nav-actions">
        <a href="index.php?page=student_login" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Student Login</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-lock"></i> Blockchain-Secured Voting Platform</div>
            <h1>Your Voice<br>Shapes the<br><span>Future of IUC.</span></h1>
            <p>Participate in free, fair and transparent elections. Every vote is secured by blockchain technology and verifiable by you.</p>
            <div class="hero-actions">
                <a href="index.php?page=student_login" class="btn btn-white btn-lg">
                    <i class="fas fa-vote-yea"></i> Vote Now
                </a>
                <a href="#how-it-works" class="btn btn-outline-white btn-lg">
                    <i class="fas fa-play-circle"></i> Learn More
                </a>
            </div>
            <div class="hero-social-proof">
                <div class="avatars">
                    <div>SJ</div><div>MC</div><div>AN</div><div>RK</div>
                </div>
                <div class="social-text">
                    <strong><?= $totalStudents > 0 ? number_format($totalStudents) . '+ registered voters' : 'Join your fellow students' ?></strong>
                    making their voices heard
                </div>
            </div>
        </div>
        <div class="hero-mockup">
            <div class="mockup-card">
                <div class="mockup-header">
                    <div>
                        <div style="font-size:.75rem;color:#64748b;">Welcome back </div>
                        <h3>Student Portal</h3>
                    </div>
                    <i class="fas fa-bell" style="color:#3282B8;font-size:1.2rem;"></i>
                </div>
                <div class="mockup-stat">
                    <div>
                        <div class="num"><?= number_format($totalVotes) ?></div>
                        <div class="lbl">Total Votes Cast</div>
                    </div>
                    <div class="icon"><i class="fas fa-ballot-check"></i></div>
                </div>
                <div class="mockup-elections">
                    <h4>Active Elections</h4>
                    <?php
                    $activeElecs = $pdo->query("SELECT title, status FROM elections WHERE status='active' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($activeElecs)):
                    ?>
                    <div class="mockup-election-item">
                        <span>No active elections</span>
                        <span class="pill-soon">Pending</span>
                    </div>
                    <?php else: foreach($activeElecs as $e): ?>
                    <div class="mockup-election-item">
                        <span style="font-weight:600;color:#0B3C5D;"><?= htmlspecialchars(substr($e['title'],0,22)) ?><?= strlen($e['title'])>22?'':'' ?></span>
                        <span class="pill-active">Open</span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <a href="index.php?page=student_login" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1rem;">
                    <i class="fas fa-sign-in-alt"></i> Login to Vote
                </a>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="hiw" id="how-it-works">
    <div style="max-width:1100px;margin:0 auto;">
        <div class="text-center">
            <div class="section-label">Simple Process</div>
            <h2 class="section-title">How It Works</h2>
            <p class="section-sub">A simple, secure process that ensures every vote counts and is verifiable.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num"><i class="fas fa-user-plus"></i></div>
                <h3>1. Register</h3>
                <p>Admin registers you as a voter and generates your unique voting code.</p>
            </div>
            <div class="step">
                <div class="step-num"><i class="fas fa-key"></i></div>
                <h3>2. Receive Code</h3>
                <p>You receive your Student ID and voting code to access the system.</p>
            </div>
            <div class="step">
                <div class="step-num"><i class="fas fa-sign-in-alt"></i></div>
                <h3>3. Login</h3>
                <p>Enter your Student ID and voting code to securely access the portal.</p>
            </div>
            <div class="step">
                <div class="step-num"><i class="fas fa-vote-yea"></i></div>
                <h3>4. Vote</h3>
                <p>Choose your candidate and cast your vote with one click.</p>
            </div>
            <div class="step">
                <div class="step-num"><i class="fas fa-check-circle"></i></div>
                <h3>5. Verify</h3>
                <p>Your vote is secured on the blockchain and can be verified anytime.</p>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features" id="features">
    <div style="max-width:1100px;margin:0 auto;">
        <div class="text-center">
            <div class="section-label">Core Features</div>
            <h2 class="section-title">Built for Trust &amp; Transparency</h2>
            <p class="section-sub">Every feature is designed to make elections secure, fair, and accessible.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-lock"></i></div>
                <h3>Blockchain Security</h3>
                <p>Every vote is recorded on an immutable blockchain ledger, making tampering impossible.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Real-time Results</h3>
                <p>Watch live vote counts and standings update in real time as the election progresses.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Vote Verification</h3>
                <p>Use your transaction hash to independently verify your vote was recorded correctly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
                <h3>Admin Dashboard</h3>
                <p>Powerful admin tools to manage elections, voters, and monitor activity in real time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3>Mobile Friendly</h3>
                <p>Vote from any device  desktop, tablet, or mobile  with a seamless experience.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                <h3>Instant Reports</h3>
                <p>Download full election reports in CSV format for transparency and record-keeping.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bell"></i></div>
                <h3>Notifications</h3>
                <p>Admins receive instant alerts for registrations, votes, and important system events.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-history"></i></div>
                <h3>Audit Trail</h3>
                <p>Complete activity logs keep a transparent record of every action in the system.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="stats-bar">
    <div class="stats-inner" style="max-width:1100px;margin:0 auto;">
        <div class="stats-left">
            <h2>Together, we build a fair and democratic IUC.</h2>
            <p>Small actions today, big impact on our institution's future.</p>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="num"><?= $totalStudents > 0 ? number_format($totalStudents).'+' : '' ?></div>
            <div class="lbl">Registered Voters</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="num"><?= $totalVotes > 0 ? number_format($totalVotes).'+' : '' ?></div>
            <div class="lbl">Votes Cast</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="num"><?= $totalElections > 0 ? number_format($totalElections) : '' ?></div>
            <div class="lbl">Elections Held</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="num">100%</div>
            <div class="lbl">Blockchain Verified</div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div style="max-width:600px;margin:0 auto;">
        <h2>Ready to Make Your Voice Heard?</h2>
        <p>Login with your Student ID and voting code provided by the administrator to participate in elections.</p>
        <div class="cta-actions">
            <a href="index.php?page=student_login" class="btn btn-primary btn-lg">
                <i class="fas fa-vote-yea"></i> Student Login
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="footer-top">
        <div class="footer-brand">
            <div class="logo"><i class="fas fa-shield-alt"></i></div>
            <h3>IUC Voting System</h3>
            <p>A blockchain-powered platform to run secure, transparent, and fair student elections at IUC.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="index.php?page=contact">Contact</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>For Students</h4>
            <ul>
                <li><a href="index.php?page=student_login">Login to Vote</a></li>
                <li><a href="index.php?page=results">View Results</a></li>
                <li><a href="index.php?page=verify">Verify My Vote</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>For Admins</h4>
            <ul>
                <li style="color:rgba(255,255,255,.5);font-size:.85rem;">Contact your system administrator</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact Us</h4>
            <div class="contact-item"><i class="fas fa-phone"></i> +237 6XX XXX XXX</div>
            <div class="contact-item"><i class="fas fa-envelope"></i> voting@iuc.edu.cm</div>
            <div class="contact-item"><i class="fas fa-map-marker-alt"></i> IUC Campus, Cameroon</div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> IUC Voting System. All rights reserved. &nbsp;|&nbsp; Powered by Blockchain Technology</p>
    </div>
</footer>

</body>
</html>
