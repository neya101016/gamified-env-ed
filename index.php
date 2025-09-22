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
    <!-- AOS - Animate on Scroll -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Claude-inspired Landing Page Styles */
        :root {
            --primary-gradient: linear-gradient(135deg, #0E7C3F, #18A559);
            --secondary-gradient: linear-gradient(135deg, #1A8FB3, #27BDEC);
            --accent-gradient: linear-gradient(135deg, #FFAB00, #FFD54F);
            --dark-gradient: linear-gradient(135deg, #1A1A2E, #16213E);
            --light-gradient: linear-gradient(135deg, #F7FAFC, #EDF2F7);
            --text-primary: #1A202C;
            --text-secondary: #4A5568;
            --text-light: #F7FAFC;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px rgba(0,0,0,0.15);
            --border-radius-sm: 0.375rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            --border-radius-2xl: 1.5rem;
            --border-radius-3xl: 2rem;
            --border-radius-full: 9999px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background-color: #FAFAFA;
            color: var(--text-primary);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
        }
        
        p {
            color: var(--text-secondary);
            line-height: 1.7;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 2.5rem;
            z-index: 1;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            height: 4px;
            width: 50px;
            background: var(--primary-gradient);
            border-radius: var(--border-radius-full);
        }
        
        .section-title.text-center::after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        /* Floating Elements Animation */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .float-element {
            animation: float 3s ease-in-out infinite;
        }
        
        .float-element-slow {
            animation: float 6s ease-in-out infinite;
        }
        
        .float-element-delay-1 {
            animation-delay: 1s;
        }
        
        .float-element-delay-2 {
            animation-delay: 2s;
        }
        
        /* Animated Background Gradient */
        .animated-gradient {
            background: linear-gradient(-45deg, #28a745, #17a2b8, #0E7C3F, #1A8FB3);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Modern Navbar */
        .navbar {
            background-color: rgba(14, 124, 63, 0.9);
            transition: all 0.3s ease;
            padding: 1.25rem 0;
        }
        
        .navbar.scrolled {
            background-color: #FFFFFF;
            box-shadow: var(--shadow-md);
            padding: 0.75rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .nav-link {
            position: relative;
            margin: 0 0.5rem;
            padding: 0.5rem 0.25rem !important;
            color: white !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled .nav-link {
            color: var(--text-primary) !important;
        }
        
        .navbar.scrolled .navbar-brand {
            color: var(--text-primary) !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: currentColor;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }
        
        .btn-login {
            border-radius: var(--border-radius-full);
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 6rem;
            overflow: hidden;
            background-color: #000;
        }
        
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.7;
            background-image: url('assets/images/eco-hero.jpg');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(14, 124, 63, 0.8), rgba(26, 143, 179, 0.8));
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .hero-title {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero-description {
            font-size: 1.25rem;
            font-weight: 400;
            max-width: 600px;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .hero-blob {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
        }
        
        .blob-1 {
            top: 10%;
            right: 10%;
            width: 400px;
            height: 400px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            background-color: #28a745;
            animation: blob-morph 15s linear infinite alternate;
        }
        
        .blob-2 {
            bottom: 10%;
            left: 5%;
            width: 300px;
            height: 300px;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            background-color: #17a2b8;
            animation: blob-morph 20s linear infinite alternate;
        }
        
        @keyframes blob-morph {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 50% 50% 30% 70% / 60% 40% 60% 40%; }
            50% { border-radius: 70% 30% 50% 50% / 40% 60% 40% 60%; }
            75% { border-radius: 40% 60% 70% 30% / 30% 70% 30% 70%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }
        
        .hero-cta-btn {
            padding: 1rem 2.5rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }
        
        .btn-outline-light {
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.8);
            background-color: transparent;
        }
        
        .btn-success {
            background: var(--primary-gradient);
            border: none;
        }
        
        .btn-success:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
</style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="fas fa-leaf me-2"></i>GreenQuest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events">Events</a>
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
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        
        <!-- Animated Blob Shapes -->
        <div class="hero-blob blob-1 float-element-slow"></div>
        <div class="hero-blob blob-2 float-element-slow float-element-delay-1"></div>
        
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="hero-title" data-aos="fade-up">
                        Learn, Act, Earn & <span class="text-warning">Make a Difference</span>
                    </h1>
                    <p class="hero-description" data-aos="fade-up" data-aos-delay="100">
                        GreenQuest transforms environmental education into an immersive journey with interactive lessons, 
                        real-world eco challenges, and a rewarding gamified experience.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-5" data-aos="fade-up" data-aos-delay="200">
                        <a href="register.php" class="btn btn-success hero-cta-btn">Get Started</a>
                        <a href="#features" class="btn btn-outline-light hero-cta-btn">Learn More</a>
                    </div>
                    
                    <div class="d-flex mt-5 pt-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="me-4 text-center">
                            <div class="display-6 fw-bold">15K+</div>
                            <div class="text-white-50">Students</div>
                        </div>
                        <div class="me-4 text-center">
                            <div class="display-6 fw-bold">480+</div>
                            <div class="text-white-50">Schools</div>
                        </div>
                        <div class="text-center">
                            <div class="display-6 fw-bold">120K+</div>
                            <div class="text-white-50">kg CO₂ Saved</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left">
                    <div class="position-relative">
                        <img src="assets/images/eco-hero-illustration.png" alt="Eco Hero" class="img-fluid float-element">
                        <!-- Animated Icons -->
                        <div class="position-absolute top-0 start-0 translate-middle p-3 bg-success rounded-circle shadow-lg float-element-delay-1 float-element">
                            <i class="fas fa-leaf fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute top-50 end-0 translate-middle p-3 bg-warning rounded-circle shadow-lg float-element-delay-2 float-element">
                            <i class="fas fa-seedling fa-2x text-white"></i>
                        </div>
                        <div class="position-absolute bottom-0 start-50 translate-middle p-3 bg-info rounded-circle shadow-lg float-element float-element-delay-1">
                            <i class="fas fa-globe-americas fa-2x text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator position-absolute bottom-0 start-50 translate-middle-x mb-4">
            <a href="#features" class="text-white">
                <div class="d-flex flex-column align-items-center">
                    <span class="mb-2">Scroll down</span>
                    <i class="fas fa-chevron-down fa-bounce"></i>
                </div>
            </a>
        </div>
    </section>
    
    
    <!-- Feature Cards with Hover Effects -->
    <style>
        /* Feature Cards Styles */
        .feature-section {
            padding: 8rem 0;
            background-color: #FFFFFF;
            position: relative;
            overflow: hidden;
        }
        
        .feature-bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(40, 167, 69, 0.05);
            z-index: 0;
        }
        
        .shape-1 {
            width: 500px;
            height: 500px;
            top: -250px;
            right: -250px;
        }
        
        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -150px;
            left: -150px;
        }
        
        .feature-card {
            background-color: #FFFFFF;
            border-radius: var(--border-radius-lg);
            padding: 2.5rem 2rem;
            height: 100%;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-card:hover .feature-title,
        .feature-card:hover .feature-text,
        .feature-card:hover .feature-icon i {
            color: white;
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius-lg);
            background-color: rgba(40, 167, 69, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.4s ease;
        }
        
        .feature-card:hover .feature-icon {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .feature-icon i {
            font-size: 2rem;
            color: #28a745;
            transition: all 0.4s ease;
        }
        
        .feature-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.4s ease;
        }
        
        .feature-text {
            margin-bottom: 0;
            transition: all 0.4s ease;
        }
        
        /* Badge Showcase Styles */
        .badge-showcase-section {
            padding: 4rem 0;
            background-color: #F7FAFC;
        }
        
        .badge-item {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }
        
        .badge-item::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            background: var(--primary-gradient);
            z-index: -1;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .badge-item:hover {
            transform: translateY(-10px) scale(1.05);
        }
        
        .badge-item:hover::before {
            opacity: 1;
        }
        
        .badge-icon {
            font-size: 3rem;
            color: #28a745;
        }
        
        .badge-name {
            margin-top: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .badge-item:hover + .badge-name {
            color: #28a745;
        }
    </style>
    
    <!-- Features Section -->
    <section class="feature-section" id="features">
        <div class="feature-bg-shape shape-1"></div>
        <div class="feature-bg-shape shape-2"></div>
        
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title text-center" data-aos="fade-up">Explore GreenQuest Features</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Discover how our platform makes environmental learning engaging and rewarding
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h4 class="feature-title">Interactive Lessons</h4>
                        <p class="feature-text">Engaging multimedia content and quizzes that make learning about sustainability fun and memorable.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4 class="feature-title">Real-World Challenges</h4>
                        <p class="feature-text">Complete eco-friendly tasks in your community and upload proof to earn recognition and points.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4 class="feature-title">Badges & Rewards</h4>
                        <p class="feature-text">Earn digital badges and eco-points as you progress, showcasing your environmental achievements.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4 class="feature-title">Leaderboards</h4>
                        <p class="feature-text">Compete with friends and schools on local and global leaderboards to drive positive change.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">Progress Tracking</h4>
                        <p class="feature-text">Monitor your environmental impact and learning journey with detailed analytics and reports.</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="feature-title">Community Engagement</h4>
                        <p class="feature-text">Connect with like-minded individuals and organizations committed to environmental sustainability.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Badge Showcase -->
    <section class="badge-showcase-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-12 mb-5">
                    <h3 class="section-title text-center" data-aos="fade-up">Earn Badges as You Learn and Grow</h3>
                    <p class="text-muted" data-aos="fade-up" data-aos-delay="100">Collect achievements that showcase your environmental expertise</p>
                </div>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up">
                    <div class="badge-item float-element">
                        <i class="fas fa-seedling badge-icon"></i>
                    </div>
                    <p class="badge-name">Eco Starter</p>
                </div>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="badge-item float-element-delay-1 float-element">
                        <i class="fas fa-leaf badge-icon"></i>
                    </div>
                    <p class="badge-name">Nature Guardian</p>
                </div>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="badge-item float-element-delay-2 float-element">
                        <i class="fas fa-graduation-cap badge-icon"></i>
                    </div>
                    <p class="badge-name">Eco Scholar</p>
                </div>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="badge-item float-element float-element-delay-1">
                        <i class="fas fa-shield-alt badge-icon"></i>
                    </div>
                    <p class="badge-name">Planet Protector</p>
                </div>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up" data-aos-delay="400">
                    <div class="badge-item float-element-delay-2 float-element">
                        <i class="fas fa-star badge-icon"></i>
                    </div>
                    <p class="badge-name">Eco Champion</p>
                </div>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 text-center" data-aos="fade-up" data-aos-delay="500">
                    <div class="badge-item float-element float-element-delay-1">
                        <i class="fas fa-crown badge-icon"></i>
                    </div>
                    <p class="badge-name">Sustainability Master</p>
                </div>
            </div>
        </div>
    </section>
    
    
    <!-- Stats Section with Animated Counters -->
    <style>
        /* Stats Section Styles */
        .stats-section {
            padding: 8rem 0;
            background: var(--light-gradient);
            position: relative;
            overflow: hidden;
        }
        
        .stats-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: 
                radial-gradient(rgba(40, 167, 69, 0.1) 2px, transparent 2px),
                radial-gradient(rgba(23, 162, 184, 0.1) 2px, transparent 2px);
            background-size: 40px 40px;
            background-position: 0 0, 20px 20px;
            opacity: 0.5;
            z-index: 0;
        }
        
        .stat-card {
            background-color: white;
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            height: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-gradient);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card:hover::before {
            width: 100%;
            opacity: 0.1;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            background-color: #28a745;
            color: white;
            transform: rotateY(360deg);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-number {
            transform: scale(1.1);
        }
        
        .stat-label {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0;
        }
        
        /* Counter Animation */
        @keyframes count-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- Stats Section -->
    <section class="stats-section" id="stats">
        <div class="stats-pattern"></div>
        
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title text-center" data-aos="fade-up">Our Environmental Impact</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Together we're making a measurable difference in our world
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tree"></i>
                        </div>
                        <h3 class="stat-number" data-counter="24780">0</h3>
                        <p class="stat-label">Trees Planted</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="stat-number" data-counter="15642">0</h3>
                        <p class="stat-label">Students Engaged</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <h3 class="stat-number" data-counter="487">0</h3>
                        <p class="stat-label">Schools Participating</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <h3 class="stat-number" data-counter="127340">0</h3>
                        <p class="stat-label">kg CO₂ Reduced</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-5 text-center">
                <div class="col-md-8 mx-auto" data-aos="fade-up" data-aos-delay="400">
                    <div class="p-4 rounded-4 bg-white shadow-sm">
                        <h4 class="mb-3">Join us in making an impact!</h4>
                        <p class="mb-4">Every challenge completed contributes to our collective environmental goals.</p>
                        <a href="register.php" class="btn btn-success rounded-pill px-4">Get Started Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    <!-- Testimonial Section -->
    <style>
        /* Testimonials Styles */
        .testimonial-section {
            padding: 8rem 0;
            background-color: #FFFFFF;
            position: relative;
            overflow: hidden;
        }
        
        .testimonial-pattern {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2328a745' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }
        
        .testimonial-card {
            background-color: white;
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .testimonial-card::before {
            content: '\f10d';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 3rem;
            color: rgba(40, 167, 69, 0.1);
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover::before {
            transform: scale(1.2) rotate(10deg);
        }
        
        .testimonial-content {
            position: relative;
            z-index: 1;
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            line-height: 1.7;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-author-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 3px solid rgba(40, 167, 69, 0.1);
            transition: all 0.3s ease;
        }
        
        .testimonial-card:hover .testimonial-author-img {
            border-color: #28a745;
        }
        
        .testimonial-author-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .testimonial-author-title {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .testimonial-rating {
            color: #FFD700;
            margin-bottom: 1rem;
        }
        
        /* Testimonial Indicators */
        .testimonial-indicators {
            display: flex;
            justify-content: center;
            margin-top: 3rem;
        }
        
        .testimonial-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(40, 167, 69, 0.2);
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .testimonial-indicator.active {
            background-color: #28a745;
            transform: scale(1.2);
        }
    </style>
    
    <!-- Testimonials Section -->
    <section class="testimonial-section" id="testimonials">
        <div class="testimonial-pattern"></div>
        
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title text-center" data-aos="fade-up">What Our Users Say</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Hear from students, teachers and organizations using GreenQuest
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-content">
                            "GreenQuest makes learning about the environment so much fun! I've earned 5 badges already and my whole class is competing to see who can get the most eco-points this semester."
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/student1.jpg" alt="Student" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Emily Johnson</h5>
                                <p class="testimonial-author-title">Student, Green Valley High</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-content">
                            "As an educator, I've seen a remarkable increase in student engagement since implementing GreenQuest. The platform makes it easy to track progress and the real-world challenges create meaningful learning experiences."
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/teacher1.jpg" alt="Teacher" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Mr. Thomas Wright</h5>
                                <p class="testimonial-author-title">Science Teacher</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="testimonial-content">
                            "GreenQuest has been an invaluable partner in our mission to promote environmental awareness. The platform connects us with motivated students eager to make a difference in their communities."
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/ngo1.jpg" alt="NGO Representative" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Sarah Martinez</h5>
                                <p class="testimonial-author-title">EcoAction NGO</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-content">
                            "I never thought learning about sustainability could be this engaging. The challenges pushed me to start a recycling program at home, and now my whole family is involved!"
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/student2.jpg" alt="Student" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Jason Kim</h5>
                                <p class="testimonial-author-title">Student, Eco Warriors Academy</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-content">
                            "Implementing GreenQuest across our school has transformed how we teach environmental education. Our students are more motivated and our school recently won a regional sustainability award."
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/principal1.jpg" alt="Principal" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Dr. Patricia Moore</h5>
                                <p class="testimonial-author-title">School Principal</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <p class="testimonial-content">
                            "As a parent, I'm thrilled to see my daughter so excited about environmental conservation. GreenQuest has sparked meaningful conversations at home about how we can reduce our carbon footprint."
                        </p>
                        <div class="testimonial-author">
                            <img src="assets/images/parent1.jpg" alt="Parent" class="testimonial-author-img">
                            <div>
                                <h5 class="testimonial-author-name">Michael Thompson</h5>
                                <p class="testimonial-author-title">Parent</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-indicators" data-aos="fade-up" data-aos-delay="400">
                <div class="testimonial-indicator active"></div>
                <div class="testimonial-indicator"></div>
                <div class="testimonial-indicator"></div>
            </div>
        </div>
    </section>
    
    
    <!-- How It Works Section -->
    <style>
        /* How It Works Styles */
        .how-it-works-section {
            padding: 8rem 0;
            background-color: #F8FAFC;
            position: relative;
            overflow: hidden;
        }
        
        .how-it-works-bg {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%2328a745' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            z-index: 0;
        }
        
        .timeline {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            z-index: 1;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 4px;
            background: linear-gradient(to bottom, #28a745 0%, #17a2b8 100%);
            transform: translateX(-50%);
            border-radius: var(--border-radius-full);
            z-index: -1;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 4rem;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-content {
            position: relative;
            width: calc(50% - 40px);
            padding: 2rem;
            background-color: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }
        
        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            margin-right: auto;
        }
        
        .timeline-item:nth-child(odd) .timeline-content::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -10px;
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-right: 10px solid white;
            transform: translateY(-50%);
        }
        
        .timeline-item:nth-child(even) .timeline-content::before {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-left: 10px solid white;
            transform: translateY(-50%);
        }
        
        .timeline-circle {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(-50%, -50%);
            box-shadow: var(--shadow-md);
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover .timeline-circle {
            transform: translate(-50%, -50%) scale(1.1);
            box-shadow: var(--shadow-lg);
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover .timeline-icon {
            transform: rotate(360deg);
        }
        
        .timeline-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .timeline-text {
            color: var(--text-secondary);
            margin-bottom: 0;
        }
        
        @media (max-width: 767.98px) {
            .timeline::before {
                left: 40px;
            }
            
            .timeline-content {
                width: calc(100% - 80px);
                margin-left: 80px !important;
            }
            
            .timeline-item:nth-child(odd) .timeline-content::before,
            .timeline-item:nth-child(even) .timeline-content::before {
                left: -10px;
                right: auto;
                border-right: 10px solid white;
                border-left: none;
            }
            
            .timeline-circle {
                left: 40px;
                transform: translateY(-50%);
            }
            
            .timeline-item:hover .timeline-circle {
                transform: translateY(-50%) scale(1.1);
            }
        }
    </style>
    
    <!-- How It Works Section -->
    <section class="how-it-works-section" id="how-it-works">
        <div class="how-it-works-bg"></div>
        
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title text-center" data-aos="fade-up">How GreenQuest Works</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Follow these simple steps to start your environmental journey
                    </p>
                </div>
            </div>
            
            <div class="timeline">
                <!-- Timeline Item 1 -->
                <div class="timeline-item" data-aos="fade-up">
                    <div class="timeline-circle">
                        <div class="timeline-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Create Your Account</h4>
                        <p class="timeline-text">
                            Register with your email or school credentials to join the GreenQuest community. 
                            Set up your profile and connect with classmates and teachers.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 2 -->
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                    <div class="timeline-circle">
                        <div class="timeline-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Complete Lessons</h4>
                        <p class="timeline-text">
                            Explore interactive environmental lessons and test your knowledge with quizzes. 
                            Each completed lesson earns you eco-points and brings you closer to earning badges.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 3 -->
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="timeline-circle">
                        <div class="timeline-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Take on Eco Challenges</h4>
                        <p class="timeline-text">
                            Participate in real-world environmental challenges to make a tangible impact. 
                            Upload proof of completion to verify your actions and earn rewards.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 4 -->
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="timeline-circle">
                        <div class="timeline-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Earn Rewards</h4>
                        <p class="timeline-text">
                            Collect badges, eco-points, and climb the leaderboards as you learn and contribute.
                            Showcase your achievements on your profile and compete with friends.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 5 -->
                <div class="timeline-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="timeline-circle">
                        <div class="timeline-icon">
                            <i class="fas fa-globe-americas"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Make a Difference</h4>
                        <p class="timeline-text">
                            Track your environmental impact and inspire others to join the cause.
                            See how your individual actions contribute to global environmental goals.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="500">
                <a href="register.php" class="btn btn-success btn-lg rounded-pill px-5 py-3">Start Your Journey Now</a>
            </div>
        </div>
    </section>
    
    <!-- Events Section -->
    <style>
        /* Events Section Styles */
        .events-section {
            padding: 8rem 0;
            background-color: #F8FAFC;
            position: relative;
            overflow: hidden;
        }
        
        .events-bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%2328a745' fill-opacity='0.05' fill-rule='evenodd'%3E%3Ccircle cx='3' cy='3' r='3'/%3E%3Ccircle cx='13' cy='13' r='3'/%3E%3C/g%3E%3C/svg%3E");
            z-index: 0;
        }
        
        .event-card {
            background-color: white;
            border-radius: var(--border-radius-xl);
            overflow: hidden;
            height: 100%;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .event-date {
            padding: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .event-date::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            z-index: -1;
            transition: all 0.3s ease;
        }
        
        .event-card:hover .event-date::before {
            transform: scale(1.1);
        }
        
        .event-month {
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .event-day {
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .event-year {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }
        
        .event-content {
            padding: 1.5rem;
        }
        
        .event-badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--border-radius-full);
            margin-bottom: 0.75rem;
        }
        
        .event-participants {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            float: right;
        }
        
        .event-participants i {
            margin-right: 0.35rem;
            color: #28a745;
        }
        
        .event-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }
        
        .event-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        .event-location {
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .event-location i {
            margin-right: 0.5rem;
            color: #dc3545;
        }
    </style>
    
    <!-- Events Section -->
    <section class="events-section" id="events">
        <div class="events-bg-pattern"></div>
        
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title text-center" data-aos="fade-up">Upcoming Events & Challenges</h2>
                    <p class="lead text-muted" data-aos="fade-up" data-aos-delay="100">
                        Join our environmental activities and make a real-world impact
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-6 mb-4" data-aos="fade-up">
                    <div class="event-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <div class="event-date h-100 d-flex flex-column justify-content-center">
                                    <div class="event-month">OCT</div>
                                    <div class="event-day">12</div>
                                    <div class="event-year">2025</div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="event-content">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="event-badge bg-success text-white">Challenge</span>
                                        <span class="event-participants"><i class="fas fa-users"></i> 358 Participants</span>
                                    </div>
                                    <h5 class="event-title">City-Wide Clean-Up Challenge</h5>
                                    <p class="event-description">Join students from across the city for our biggest clean-up event of the year. Registration is open to all schools and community groups.</p>
                                    <div class="event-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Multiple Locations</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="event-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <div class="event-date h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #17a2b8, #1ca7bd);">
                                    <div class="event-month">NOV</div>
                                    <div class="event-day">5</div>
                                    <div class="event-year">2025</div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="event-content">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="event-badge bg-info text-white">Webinar</span>
                                        <span class="event-participants"><i class="fas fa-users"></i> 175 Registered</span>
                                    </div>
                                    <h5 class="event-title">Climate Action Workshop</h5>
                                    <p class="event-description">Learn practical strategies for advocating environmental policy changes in your school and community from expert activists.</p>
                                    <div class="event-location">
                                        <i class="fas fa-video"></i>
                                        <span>Online - Zoom</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="event-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <div class="event-date h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #ffc107, #ffce3a);">
                                    <div class="event-month text-dark">NOV</div>
                                    <div class="event-day text-dark">18</div>
                                    <div class="event-year text-dark">2025</div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="event-content">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="event-badge bg-warning text-dark">Competition</span>
                                        <span class="event-participants"><i class="fas fa-users"></i> 42 Teams</span>
                                    </div>
                                    <h5 class="event-title">EcoHack: Innovation Challenge</h5>
                                    <p class="event-description">A hackathon for students to develop innovative solutions to local environmental problems using technology and creativity.</p>
                                    <div class="event-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Tech Hub Convention Center</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="event-card">
                        <div class="row g-0">
                            <div class="col-4">
                                <div class="event-date h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #dc3545, #e15361);">
                                    <div class="event-month">DEC</div>
                                    <div class="event-day">3</div>
                                    <div class="event-year">2025</div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="event-content">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="event-badge bg-primary text-white">Challenge</span>
                                        <span class="event-participants"><i class="fas fa-users"></i> 246 Participants</span>
                                    </div>
                                    <h5 class="event-title">30-Day Plastic-Free Challenge</h5>
                                    <p class="event-description">Take on this month-long challenge to reduce your plastic consumption and document your journey on GreenQuest.</p>
                                    <div class="event-location">
                                        <i class="fas fa-globe"></i>
                                        <span>Virtual Event</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4" data-aos="fade-up" data-aos-delay="400">
                <a href="register.php" class="btn btn-outline-success rounded-pill px-4 py-2">
                    View All Events <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <style>
        /* About Section Styles */
        .about-section {
            padding: 8rem 0;
            background-color: #FFFFFF;
            position: relative;
            overflow: hidden;
        }
        
        .about-bg-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(40, 167, 69, 0.05);
            z-index: 0;
        }
        
        .about-shape-1 {
            width: 600px;
            height: 600px;
            top: -300px;
            left: -300px;
        }
        
        .about-shape-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
        }
        
        .about-image {
            position: relative;
            z-index: 1;
            border-radius: var(--border-radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .about-image img {
            transition: all 0.8s ease;
            width: 100%;
        }
        
        .about-image:hover img {
            transform: scale(1.05);
        }
        
        .about-content {
            position: relative;
            z-index: 1;
        }
        
        .about-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .about-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            height: 4px;
            width: 60px;
            background: var(--primary-gradient);
            border-radius: var(--border-radius-full);
        }
        
        .about-subtitle {
            font-size: 1.25rem;
            font-weight: 600;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        
        .about-text {
            margin-bottom: 1.5rem;
        }
        
        .about-features {
            margin-bottom: 2rem;
        }
        
        .about-feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .about-feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
            color: #28a745;
            transition: all 0.3s ease;
        }
        
        .about-feature-item:hover .about-feature-icon {
            background-color: #28a745;
            color: white;
            transform: rotateY(360deg);
        }
        
        .about-feature-text {
            font-weight: 500;
        }
        
        .about-cta {
            display: flex;
            gap: 1rem;
        }
    </style>
    
    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="about-bg-shape about-shape-1"></div>
        <div class="about-bg-shape about-shape-2"></div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <div class="about-image">
                        <img src="assets/images/about-eco.jpg" alt="About GreenQuest" class="img-fluid">
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-content">
                        <h2 class="about-title">About GreenQuest</h2>
                        <p class="about-subtitle">Empowering the next generation of environmental stewards through gamified education.</p>
                        <p class="about-text">
                            GreenQuest was developed by a team of educators, environmental scientists, and software developers
                            who share a passion for sustainability and innovative education.
                        </p>
                        <p class="about-text">
                            Our mission is to make environmental education engaging, accessible, and actionable for students of all ages.
                            By combining cutting-edge technology with proven educational methods, we're creating a generation of
                            informed and motivated environmental champions.
                        </p>
                        
                        <div class="about-features">
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="about-feature-text">Research-based curriculum aligned with education standards</div>
                            </div>
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="about-feature-text">Gamification elements that enhance motivation and engagement</div>
                            </div>
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="about-feature-text">Verified impact tracking and measurable environmental outcomes</div>
                            </div>
                            <div class="about-feature-item">
                                <div class="about-feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="about-feature-text">Community partnerships with schools, NGOs, and local governments</div>
                            </div>
                        </div>
                        
                        <div class="about-cta">
                            <a href="register.php" class="btn btn-success rounded-pill px-4 py-2">Join GreenQuest</a>
                            <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    
    <!-- Call to Action Section -->
    <style>
        /* CTA Section Styles */
        .cta-section {
            position: relative;
            padding: 6rem 0;
            background: var(--primary-gradient);
            color: white;
            overflow: hidden;
        }
        
        .cta-bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .cta-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .cta-description {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 2.5rem;
            opacity: 0.9;
        }
        
        .cta-btn {
            background-color: white;
            color: #28a745;
            border: none;
            font-weight: 600;
            border-radius: var(--border-radius-full);
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .cta-shapes .shape {
            position: absolute;
            z-index: 0;
        }
        
        .cta-shape-1 {
            top: -30px;
            left: 10%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .cta-shape-2 {
            bottom: -20px;
            right: 10%;
            width: 150px;
            height: 150px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            background-color: rgba(255, 255, 255, 0.1);
            animation: float 8s ease-in-out infinite, rotate 15s linear infinite;
            animation-delay: 1s;
        }
        
        .cta-shape-3 {
            top: 30%;
            right: 20%;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            animation: float 5s ease-in-out infinite;
            animation-delay: 2s;
        }
        
        .cta-shape-4 {
            bottom: 30%;
            left: 15%;
            width: 80px;
            height: 80px;
            border-radius: 30% 70% 50% 50% / 40% 60% 40% 60%;
            background-color: rgba(255, 255, 255, 0.1);
            animation: float 7s ease-in-out infinite, rotate 20s linear infinite;
            animation-delay: 3s;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="cta-bg-pattern"></div>
        
        <div class="cta-shapes">
            <div class="shape cta-shape-1"></div>
            <div class="shape cta-shape-2"></div>
            <div class="shape cta-shape-3"></div>
            <div class="shape cta-shape-4"></div>
        </div>
        
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <h2 class="cta-title">Ready to Start Your Eco Journey?</h2>
                <p class="cta-description">Join thousands of students, teachers, and schools who are making a difference with GreenQuest. Start your environmental learning adventure today!</p>
                <a href="register.php" class="cta-btn">Create Your Account Today</a>
            </div>
        </div>
    </section>
    
    
    <!-- Footer Section -->
    <style>
        /* Footer Styles */
        .footer-section {
            position: relative;
            background: var(--dark-gradient);
            color: white;
            padding: 6rem 0 2rem;
            overflow: hidden;
        }
        
        .footer-bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3Cpath d='M6 5V0H5v5H0v1h5v94h1V6h94V5H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.1;
            z-index: 0;
        }
        
        .footer-wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            transform: translateY(-99%);
        }
        
        .footer-wave svg {
            position: relative;
            display: block;
            width: calc(100% + 1.3px);
            height: 90px;
        }
        
        .footer-wave .shape-fill {
            fill: #1A1A2E;
        }
        
        .footer-logo {
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .footer-description {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }
        
        .footer-social {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            background-color: #28a745;
            transform: translateY(-5px);
        }
        
        .footer-heading {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .footer-heading::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            height: 2px;
            width: 30px;
            background: #28a745;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .footer-links a i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }
        
        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .footer-contact-icon {
            margin-right: 1rem;
            color: #28a745;
        }
        
        .footer-bottom {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-copyright {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .footer-bottom-links {
            display: flex;
            justify-content: flex-end;
        }
        
        .footer-bottom-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-left: 1.5rem;
            transition: color 0.3s ease;
        }
        
        .footer-bottom-links a:hover {
            color: white;
        }
        
        @media (max-width: 767.98px) {
            .footer-heading {
                margin-top: 2rem;
            }
            
            .footer-bottom-links {
                justify-content: flex-start;
                margin-top: 1rem;
            }
            
            .footer-bottom-links a:first-child {
                margin-left: 0;
            }
        }
        
        /* Newsletter Form */
        .newsletter-form {
            position: relative;
            margin-top: 1.5rem;
        }
        
        .newsletter-input {
            width: 100%;
            padding: 0.75rem 1.5rem;
            padding-right: 3.5rem;
            border-radius: var(--border-radius-full);
            border: none;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .newsletter-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .newsletter-input:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .newsletter-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            border: none;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .newsletter-btn:hover {
            background-color: #218838;
            transform: scale(1.1);
        }
    </style>
    
    <!-- Footer Section -->
    <footer class="footer-section">
        <div class="footer-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>
        
        <div class="footer-bg-pattern"></div>
        
        <div class="container position-relative">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-logo">
                        <i class="fas fa-leaf me-2"></i>GreenQuest
                    </div>
                    <p class="footer-description">
                        Making environmental education engaging, accessible, and actionable for everyone. Join us in creating a sustainable future through gamified learning.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="newsletter">
                        <h5 class="footer-heading">Subscribe to Our Newsletter</h5>
                        <p class="footer-description">Get the latest updates and resources</p>
                        <form class="newsletter-form">
                            <input type="email" class="newsletter-input" placeholder="Your email address">
                            <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Features</a></li>
                        <li><a href="#how-it-works"><i class="fas fa-chevron-right"></i> How It Works</a></li>
                        <li><a href="#events"><i class="fas fa-chevron-right"></i> Events</a></li>
                        <li><a href="#testimonials"><i class="fas fa-chevron-right"></i> Testimonials</a></li>
                        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="footer-heading">Resources</h5>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Blog</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQs</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Support</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Documentation</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Community</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Partners</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-heading">Contact Us</h5>
                    <div class="footer-contact-item">
                        <i class="fas fa-map-marker-alt footer-contact-icon"></i>
                        <div>123 Eco Street, Green City, EC01 2GQ</div>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-envelope footer-contact-icon"></i>
                        <div>info@greenquest.edu</div>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-phone footer-contact-icon"></i>
                        <div>(123) 456-7890</div>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-clock footer-contact-icon"></i>
                        <div>Monday - Friday: 9am - 5pm<br>Saturday: 10am - 2pm</div>
                    </div>
                </div>
            </div>
            
            <div class="row footer-bottom">
                <div class="col-md-6">
                    <p class="footer-copyright mb-md-0">&copy; 2025 GreenQuest. All rights reserved.</p>
                </div>
                <div class="col-md-6">
                    <div class="footer-bottom-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                    </div>
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
    <!-- AOS - Animate on Scroll -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            
            // Navbar Scroll Effect
            $(window).scroll(function() {
                if($(this).scrollTop() > 50) {
                    $('.navbar').addClass('scrolled');
                } else {
                    $('.navbar').removeClass('scrolled');
                }
            });
            
            // Smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                
                var target = this.hash;
                var $target = $(target);
                
                $('html, body').animate({
                    'scrollTop': $target.offset().top - 70
                }, 800, 'swing');
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
            
            // Counter Animation
            function animateCounters() {
                $('.stat-number').each(function () {
                    var $this = $(this);
                    var countTo = parseInt($this.attr('data-counter').replace(/,/g, ''));
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum).toLocaleString());
                        },
                        complete: function() {
                            $this.text(this.countNum.toLocaleString());
                        }
                    });
                });
            }
            
            // Trigger counter animation when stats section is scrolled to
            var waypointStats = new Waypoint({
                element: document.getElementById('stats'),
                handler: function() {
                    animateCounters();
                    this.destroy();
                },
                offset: '80%'
            });
            
            // Testimonial Indicators
            $('.testimonial-indicator').click(function() {
                $('.testimonial-indicator').removeClass('active');
                $(this).addClass('active');
                // You can add additional code here to implement testimonial carousel if needed
            });
            
            // Add class to animate elements when they come into view
            $(window).scroll(function() {
                $('.float-element, .float-element-slow').each(function() {
                    var elementPos = $(this).offset().top;
                    var topOfWindow = $(window).scrollTop();
                    var windowHeight = $(window).height();
                    
                    if (elementPos < topOfWindow + windowHeight - 100) {
                        $(this).addClass('animated');
                    }
                });
            });
            
            // Newsletter Form Submission (prevent default for demo)
            $('.newsletter-form').submit(function(e) {
                e.preventDefault();
                var email = $('.newsletter-input').val();
                
                if(email) {
                    Swal.fire({
                        title: 'Thank you!',
                        text: 'You have successfully subscribed to our newsletter.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    });
                    
                    $('.newsletter-input').val('');
                } else {
                    Swal.fire({
                        title: 'Oops!',
                        text: 'Please enter a valid email address.',
                        icon: 'error',
                        confirmButtonColor: '#28a745'
                    });
                }
            });
        });
    </script>
</body>
</html>