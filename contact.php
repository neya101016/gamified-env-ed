<?php
// Start session
session_start();

// Include config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = "Contact Us";

// Process form submission
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // In a real application, you would send an email here
        // For demonstration purposes, we'll just simulate success
        
        // Log the contact form submission for development purposes
        error_log("Contact form submission from {$name} ({$email}): {$subject}");
        
        $success_message = "Thank you for your message! We'll get back to you as soon as possible.";
        
        // Clear form data after successful submission
        $name = $email = $subject = $message = "";
    }
}

// Include header
include 'common/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="card-title mb-4">Contact Us</h1>
                    <p class="text-muted mb-4">Have questions or feedback? We'd love to hear from you! Fill out the form below and our team will get back to you as soon as possible.</p>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" id="contactForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" required value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="privacy_agree" name="privacy_agree" required>
                            <label class="form-check-label" for="privacy_agree">
                                I agree to the <a href="privacy.php" target="_blank">Privacy Policy</a> and consent to the processing of my personal data.
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="contact_submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow mt-4">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">Other Ways to Reach Us</h4>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="text-center">
                                <div class="mb-3">
                                    <i class="fas fa-map-marker-alt fa-3x text-primary"></i>
                                </div>
                                <h5>Visit Us</h5>
                                <p class="mb-0">123 Eco Street<br>Green City</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="text-center">
                                <div class="mb-3">
                                    <i class="fas fa-envelope fa-3x text-primary"></i>
                                </div>
                                <h5>Email Us</h5>
                                <p class="mb-0">
                                    <a href="mailto:info@greenquest.org" class="text-decoration-none">info@greenquest.org</a>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="mb-3">
                                    <i class="fas fa-phone fa-3x text-primary"></i>
                                </div>
                                <h5>Call Us</h5>
                                <p class="mb-0">(123) 456-7890</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'common/footer.php';
?>

<script>
    // Form validation with SweetAlert2
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const subject = document.getElementById('subject').value.trim();
                const message = document.getElementById('message').value.trim();
                const privacyAgree = document.getElementById('privacy_agree').checked;
                
                let isValid = true;
                let errorMessage = '';
                
                // Validate name
                if (name === '') {
                    isValid = false;
                    errorMessage = 'Please enter your name.';
                }
                // Validate email
                else if (email === '') {
                    isValid = false;
                    errorMessage = 'Please enter your email address.';
                }
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
                // Validate subject
                else if (subject === '') {
                    isValid = false;
                    errorMessage = 'Please enter a subject.';
                }
                // Validate message
                else if (message === '') {
                    isValid = false;
                    errorMessage = 'Please enter your message.';
                }
                // Validate privacy agreement
                else if (!privacyAgree) {
                    isValid = false;
                    errorMessage = 'You must agree to the Privacy Policy.';
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage,
                        confirmButtonColor: '#0d6efd'
                    });
                }
            });
        }
    });
</script>