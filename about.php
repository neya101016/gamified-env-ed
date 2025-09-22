<?php
// Start session
session_start();

// Include database configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Initialize variables
$pageTitle = "About GreenQuest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GreenQuest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/eco-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #28a745;
        }
        
        .team-member {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 5px solid #f8f9fa;
        }
        
        .stats-section {
            background-color: #f8f9fa;
            padding: 3rem 0;
            margin: 3rem 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .faq-item {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-leaf me-2"></i>GreenQuest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if(isLoggedIn()): ?>
                        <a href="<?php echo getUserDashboardUrl(); ?>" class="btn btn-outline-light me-2">Dashboard</a>
                        <a href="logout.php" class="btn btn-light">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="register.php" class="btn btn-light">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Environmental Education Gamified</h1>
            <p class="lead mb-4">Empowering students to learn about environmental sustainability through interactive lessons, challenges, and rewards.</p>
            <?php if(!isLoggedIn()): ?>
                <div>
                    <a href="register.php" class="btn btn-success btn-lg me-2">Join GreenQuest</a>
                    <a href="#learn-more" class="btn btn-outline-light btn-lg">Learn More</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container py-4" id="learn-more">
        <!-- Mission Section -->
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="mb-4">Our Mission</h2>
                <p class="lead">GreenQuest is dedicated to making environmental education engaging, interactive, and impactful for students of all ages. Through gamification elements, we transform learning about sustainability into an exciting journey.</p>
                <p>We believe that by combining education with gamification, students will not only learn about environmental issues but also be motivated to take real-world actions that contribute to a more sustainable future.</p>
            </div>
        </div>
        
        <!-- Features Section -->
        <h2 class="text-center mb-4">Key Features</h2>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card feature-card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h5 class="card-title">Interactive Lessons</h5>
                        <p class="card-text">Engaging content about sustainability, climate change, conservation, and environmental science designed for various age groups.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h5 class="card-title">Real-World Challenges</h5>
                        <p class="card-text">Participate in environmental challenges created by NGOs and verified for impact, turning learning into practical action.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h5 class="card-title">Eco-Points & Badges</h5>
                        <p class="card-text">Earn rewards for completing lessons, quizzes, and challenges, with achievements visible on leaderboards to encourage healthy competition.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-section">
            <div class="container">
                <h2 class="text-center mb-5">Our Impact</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">10,000+</div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">250+</div>
                            <div class="stat-label">Schools</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Environmental Challenges</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">15,000+</div>
                            <div class="stat-label">Trees Planted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- How It Works Section -->
        <h2 class="text-center mb-4">How GreenQuest Works</h2>
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="howItWorksAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                For Students
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#howItWorksAccordion">
                            <div class="accordion-body">
                                <p>Students join GreenQuest and get access to a variety of environmental lessons, quizzes, and challenges. As they complete activities, they earn eco-points and badges, tracking their progress on personalized dashboards and competing with peers on leaderboards.</p>
                                <p>Real-world environmental challenges allow students to apply what they've learned and make a tangible impact on their communities and the planet.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                For Teachers
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#howItWorksAccordion">
                            <div class="accordion-body">
                                <p>Teachers can create accounts to monitor their students' progress, create customized lessons, and track classroom engagement with environmental topics. The platform provides analytics on student performance and participation.</p>
                                <p>The gamification elements help motivate students and make environmental education more engaging, while alignment with curriculum standards ensures educational value.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                For NGOs
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#howItWorksAccordion">
                            <div class="accordion-body">
                                <p>Environmental NGOs partner with GreenQuest to create real-world challenges for students. These challenges promote environmental action while educating students about important conservation issues.</p>
                                <p>NGOs verify student challenge submissions and can track the collective impact of students' environmental actions, helping to quantify their educational outreach efforts.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Team Section -->
        <h2 class="text-center mb-4">Meet Our Team</h2>
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="team-member">
                    <img src="assets/images/team/team1.jpg" alt="Team Member" class="img-fluid">
                    <h5>Sarah Johnson</h5>
                    <p class="text-muted">Founder & CEO</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="team-member">
                    <img src="assets/images/team/team2.jpg" alt="Team Member" class="img-fluid">
                    <h5>Michael Chen</h5>
                    <p class="text-muted">Education Director</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="team-member">
                    <img src="assets/images/team/team3.jpg" alt="Team Member" class="img-fluid">
                    <h5>Elena Rodriguez</h5>
                    <p class="text-muted">Environmental Scientist</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="team-member">
                    <img src="assets/images/team/team4.jpg" alt="Team Member" class="img-fluid">
                    <h5>David Okafor</h5>
                    <p class="text-muted">Technology Lead</p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <h2 class="text-center mb-4">Frequently Asked Questions</h2>
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
                                Is GreenQuest free to use?
                            </button>
                        </h2>
                        <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, GreenQuest is completely free for students, teachers, and participating schools. We're funded through partnerships with environmental organizations and educational grants to make sustainability education accessible to everyone.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                How do you verify challenge completions?
                            </button>
                        </h2>
                        <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Challenges have different verification requirements depending on their nature. Some may require photo evidence, others need teacher confirmation, and some are verified by participating NGO partners. Our verification system ensures that eco-points are awarded fairly.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                Can schools integrate GreenQuest with their curriculum?
                            </button>
                        </h2>
                        <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolutely! GreenQuest is designed to complement existing environmental science and sustainability curricula. We provide teacher resources and lesson plans that align with educational standards, making it easy to incorporate into classroom activities.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeadingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour" aria-expanded="false" aria-controls="faqCollapseFour">
                                What age groups is GreenQuest suitable for?
                            </button>
                        </h2>
                        <div id="faqCollapseFour" class="accordion-collapse collapse" aria-labelledby="faqHeadingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                GreenQuest offers content for students from elementary school through high school. Lessons and challenges are categorized by age appropriateness, with varying complexity levels to engage different age groups while maintaining educational value.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA Section -->
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto text-center">
                <div class="card bg-success text-white p-4">
                    <div class="card-body">
                        <h3 class="mb-3">Ready to Join the GreenQuest?</h3>
                        <p class="lead mb-4">Start your environmental education journey today and make a positive impact on our planet!</p>
                        <?php if(!isLoggedIn()): ?>
                            <div>
                                <a href="register.php" class="btn btn-light btn-lg me-2">Sign Up Now</a>
                                <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo getUserDashboardUrl(); ?>" class="btn btn-light btn-lg">Go to Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5><i class="fas fa-leaf me-2"></i>GreenQuest</h5>
                    <p>Making environmental education engaging, interactive, and impactful for students everywhere.</p>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                        <li><a href="leaderboard.php" class="text-white">Leaderboard</a></li>
                        <li><a href="login.php" class="text-white">Login</a></li>
                        <li><a href="register.php" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i>123 Eco Street, Green City<br>
                        <i class="fas fa-envelope me-2"></i><a href="mailto:info@greenquest.org" class="text-white">info@greenquest.org</a><br>
                        <i class="fas fa-phone me-2"></i>(123) 456-7890
                    </address>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> GreenQuest. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white me-3">Terms of Service</a>
                    <a href="#" class="text-white">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>