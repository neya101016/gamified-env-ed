<?php
// Start session
session_start();

// Include database configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if(isLoggedIn()) {
    // Redirect to appropriate dashboard
    header("Location: " . getRedirectUrl($_SESSION['role_name']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Gamified Environmental Learning Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Landing Page Specific Styles */
        body {
            overflow-x: hidden;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/eco-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 8rem 0;
            position: relative;
        }
        
        .features-section {
            padding: 5rem 0;
        }
        
        .feature-card {
            transition: transform 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .testimonial-section {
            background-color: #f8f9fa;
            padding: 5rem 0;
        }
        
        .testimonial-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .testimonial-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 5rem 0;
        }
        
        .cta-btn {
            background-color: white;
            color: var(--primary-color);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 2rem;
            transition: all 0.3s;
        }
        
        .cta-btn:hover {
            background-color: var(--accent-color);
            color: var(--dark-color);
            transform: scale(1.05);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 3rem 0;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .scroll-down {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0) translateX(-50%);
            }
            40% {
                transform: translateY(-20px) translateX(-50%);
            }
            60% {
                transform: translateY(-10px) translateX(-50%);
            }
        }
        
        .btn-login {
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
        }
        
        .badge-showcase {
            display: inline-block;
            background-color: white;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            line-height: 100px;
            text-align: center;
            margin: 0 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .badge-icon-showcase {
            font-size: 3rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-leaf me-2"></i>GreenQuest</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-light btn-login me-2">Login</a>
                    <a href="register.php" class="btn btn-light btn-login">Register</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeIn">Learn, Act, Earn & Make a Difference</h1>
                    <p class="lead mb-5 animate__animated animate__fadeIn animate__delay-1s">
                        GreenQuest turns environmental education into an exciting adventure with interactive lessons, 
                        real-world eco challenges, and a rewarding points system.
                    </p>
                    <div class="animate__animated animate__fadeIn animate__delay-2s">
                        <a href="register.php" class="btn btn-success btn-lg me-3 rounded-pill">Get Started</a>
                        <a href="#features" class="btn btn-outline-light btn-lg rounded-pill">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-down">
            <a href="#features" class="text-white">
                <i class="fas fa-chevron-down fa-2x"></i>
            </a>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold">Explore GreenQuest Features</h2>
                    <p class="lead text-muted">Discover how our platform makes environmental learning engaging and rewarding</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-book-reader"></i>
                            </div>
                            <h4 class="card-title">Interactive Lessons</h4>
                            <p class="card-text">Engaging multimedia content and quizzes that make learning about sustainability fun and memorable.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h4 class="card-title">Real-World Challenges</h4>
                            <p class="card-text">Complete eco-friendly tasks in your community and upload proof to earn recognition and points.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <h4 class="card-title">Badges & Rewards</h4>
                            <p class="card-text">Earn digital badges and eco-points as you progress, showcasing your environmental achievements.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h4 class="card-title">Leaderboards</h4>
                            <p class="card-text">Compete with friends and schools on local and global leaderboards to drive positive change.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4 class="card-title">Progress Tracking</h4>
                            <p class="card-text">Monitor your environmental impact and learning journey with detailed analytics and reports.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4 class="card-title">Community Engagement</h4>
                            <p class="card-text">Connect with like-minded individuals and organizations committed to environmental sustainability.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5 text-center">
                <div class="col-12">
                    <h3 class="mb-4">Earn Badges as You Learn and Grow</h3>
                    <div class="d-flex justify-content-center flex-wrap">
                        <div class="badge-showcase mb-3">
                            <i class="fas fa-seedling badge-icon-showcase"></i>
                        </div>
                        <div class="badge-showcase mb-3">
                            <i class="fas fa-leaf badge-icon-showcase"></i>
                        </div>
                        <div class="badge-showcase mb-3">
                            <i class="fas fa-graduation-cap badge-icon-showcase"></i>
                        </div>
                        <div class="badge-showcase mb-3">
                            <i class="fas fa-shield-alt badge-icon-showcase"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials Section -->
    <section class="testimonial-section" id="testimonials">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold">What Our Users Say</h2>
                    <p class="lead text-muted">Hear from students, teachers and organizations using GreenQuest</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/student1.jpg" alt="Student" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Emily Johnson</h5>
                                <p class="text-muted mb-0">Student, Green Valley High</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "GreenQuest makes learning about the environment so much fun! I've earned 5 badges already and my whole class is competing to see who can get the most eco-points this semester."
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/teacher1.jpg" alt="Teacher" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Mr. Thomas Wright</h5>
                                <p class="text-muted mb-0">Science Teacher</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "As an educator, I've seen a remarkable increase in student engagement since implementing GreenQuest. The platform makes it easy to track progress and the real-world challenges create meaningful learning experiences."
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/ngo1.jpg" alt="NGO Representative" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Sarah Martinez</h5>
                                <p class="text-muted mb-0">EcoAction NGO</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "GreenQuest has been an invaluable partner in our mission to promote environmental awareness. The platform connects us with motivated students eager to make a difference in their communities."
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/student2.jpg" alt="Student" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Jason Kim</h5>
                                <p class="text-muted mb-0">Student, Eco Warriors Academy</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "I never thought learning about sustainability could be this engaging. The challenges pushed me to start a recycling program at home, and now my whole family is involved!"
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/principal1.jpg" alt="Principal" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Dr. Patricia Moore</h5>
                                <p class="text-muted mb-0">School Principal</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "Implementing GreenQuest across our school has transformed how we teach environmental education. Our students are more motivated and our school recently won a regional sustainability award."
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/images/parent1.jpg" alt="Parent" class="testimonial-img me-3">
                            <div>
                                <h5 class="mb-0">Michael Thompson</h5>
                                <p class="text-muted mb-0">Parent</p>
                            </div>
                        </div>
                        <p class="mb-0">
                            "As a parent, I'm thrilled to see my daughter so excited about environmental conservation. GreenQuest has sparked meaningful conversations at home about how we can reduce our carbon footprint."
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="py-5" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="assets/images/about-eco.jpg" alt="About GreenQuest" class="img-fluid rounded shadow">
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">About GreenQuest</h2>
                    <p class="lead">Empowering the next generation of environmental stewards through gamified education.</p>
                    <p>
                        GreenQuest was developed by a team of educators, environmental scientists, and software developers
                        who share a passion for sustainability and innovative education.
                    </p>
                    <p>
                        Our mission is to make environmental education engaging, accessible, and actionable for students of all ages.
                        By combining cutting-edge technology with proven educational methods, we're creating a generation of
                        informed and motivated environmental champions.
                    </p>
                    <p>
                        The platform seamlessly integrates into existing curricula, providing teachers with powerful tools
                        to track student progress while offering students a fun and rewarding learning experience.
                    </p>
                    <div class="mt-4">
                        <a href="register.php" class="btn btn-success me-2">Join GreenQuest</a>
                        <a href="#" class="btn btn-outline-primary">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-4">Ready to Start Your Eco Journey?</h2>
                    <p class="lead mb-5">Join thousands of students, teachers, and schools who are making a difference with GreenQuest.</p>
                    <a href="register.php" class="btn cta-btn btn-lg">Create Your Account Today</a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5><i class="fas fa-leaf me-2"></i>GreenQuest</h5>
                    <p>Making environmental education engaging, accessible, and actionable for everyone.</p>
                    <div class="social-icons">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home">Home</a></li>
                        <li class="mb-2"><a href="#features">Features</a></li>
                        <li class="mb-2"><a href="#testimonials">Testimonials</a></li>
                        <li class="mb-2"><a href="#about">About</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Blog</a></li>
                        <li class="mb-2"><a href="#">FAQ</a></li>
                        <li class="mb-2"><a href="#">Support</a></li>
                        <li class="mb-2"><a href="#">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>123 Eco Street, Green City, EC01 2GQ</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>info@greenquest.edu</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>(123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">&copy; 2025 GreenQuest. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#">Privacy Policy</a>
                    <span class="mx-2">|</span>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                
                var target = this.hash;
                var $target = $(target);
                
                $('html, body').animate({
                    'scrollTop': $target.offset().top - 70
                }, 800, 'swing');
            });
            
            // Change navbar color on scroll
            $(window).scroll(function() {
                if($(this).scrollTop() > 50) {
                    $('.navbar').addClass('bg-dark').removeClass('bg-primary');
                } else {
                    $('.navbar').addClass('bg-primary').removeClass('bg-dark');
                }
            });
            
            // Activate navbar links based on scroll position
            $(window).scroll(function() {
                var scrollPosition = $(window).scrollTop();
                
                $('section').each(function() {
                    var currentSection = $(this);
                    var sectionTop = currentSection.offset().top - 100;
                    var sectionBottom = sectionTop + currentSection.outerHeight();
                    
                    if(scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        var id = currentSection.attr('id');
                        
                        $('.navbar-nav .nav-link').removeClass('active');
                        $('.navbar-nav .nav-link[href="#' + id + '"]').addClass('active');
                    }
                });
            });
        });
    </script>
</body>
</html>