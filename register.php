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

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Fetch roles for dropdown
$query = "SELECT * FROM roles ORDER BY role_id";
$stmt = $db->prepare($query);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch schools for dropdown
$query = "SELECT * FROM schools ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenQuest - Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5 mb-5">
            <div class="col-lg-7 col-md-9">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-success text-white text-center py-4">
                        <h3 class="my-2"><i class="fas fa-leaf me-2"></i>GreenQuest</h3>
                        <p class="mb-0">Create Your Account</p>
                    </div>
                    <div class="card-body p-4">
                        <div id="registerAlert" class="alert alert-danger d-none"></div>
                        
                        <form id="registerForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input class="form-control" id="name" name="name" type="text" placeholder="Enter your full name" required />
                                        <label for="name">Full Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input class="form-control" id="email" name="email" type="email" placeholder="name@example.com" required />
                                        <label for="email">Email Address</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input class="form-control" id="password" name="password" type="password" placeholder="Create a password" required />
                                        <label for="password">Password</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Confirm password" required />
                                        <label for="confirm_password">Confirm Password</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-floating mb-3">
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">-- Select Role --</option>
                                    <?php foreach($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo ucfirst($role['role_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="role_id">I am a...</label>
                            </div>
                            
                            <div class="form-floating mb-3 school-field d-none">
                                <select class="form-select" id="school_id" name="school_id">
                                    <option value="">-- Select School --</option>
                                    <?php foreach($schools as $school): ?>
                                    <option value="<?php echo $school['school_id']; ?>"><?php echo $school['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="school_id">School</label>
                            </div>
                            
                            <!-- Student-specific fields -->
                            <div class="student-fields d-none">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="grade" name="grade" type="text" placeholder="Enter your grade/class" />
                                    <label for="grade">Grade/Class</label>
                                </div>
                            </div>
                            
                            <!-- Teacher-specific fields -->
                            <div class="teacher-fields d-none">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="subject" name="subject" type="text" placeholder="Enter subject taught" />
                                    <label for="subject">Subject Taught</label>
                                </div>
                            </div>
                            
                            <!-- NGO-specific fields -->
                            <div class="ngo-fields d-none">
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="org_name" name="org_name" type="text" placeholder="Enter organization name" />
                                    <label for="org_name">Organization Name</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="org_description" name="org_description" placeholder="Briefly describe your organization" style="height: 100px"></textarea>
                                    <label for="org_description">Organization Description</label>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" id="terms_agreed" name="terms_agreed" type="checkbox" required />
                                <label class="form-check-label" for="terms_agreed">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white py-3 text-center">
                        <div class="small">Already have an account? <a href="login.php">Login</a></div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Acceptance of Terms</h5>
                    <p>By accessing and using GreenQuest, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
                    
                    <h5>2. User Accounts</h5>
                    <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times.</p>
                    
                    <h5>3. Content and Conduct</h5>
                    <p>You are responsible for all content you post and activities that occur under your account.</p>
                    
                    <h5>4. Intellectual Property</h5>
                    <p>The content, features, and functionality of GreenQuest are owned by us and are protected by copyright, trademark, and other intellectual property laws.</p>
                    
                    <h5>5. Disclaimer of Warranties</h5>
                    <p>GreenQuest is provided "as is," without warranty of any kind, express or implied.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Information Collection</h5>
                    <p>We collect personal information that you voluntarily provide to us when you register on GreenQuest.</p>
                    
                    <h5>2. Use of Information</h5>
                    <p>We use the collected information to provide, maintain, and improve GreenQuest.</p>
                    
                    <h5>3. Information Sharing</h5>
                    <p>We do not sell, trade, or rent users' personal identification information to others.</p>
                    
                    <h5>4. Data Security</h5>
                    <p>We adopt appropriate data collection, storage, and processing practices and security measures.</p>
                    
                    <h5>5. Children's Privacy</h5>
                    <p>For users under 18, we require parental consent before collecting personal information.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Show/hide role-specific fields based on selection
            $('#role_id').on('change', function() {
                const roleId = $(this).val();
                
                // Hide all role-specific fields first
                $('.school-field, .student-fields, .teacher-fields, .ngo-fields').addClass('d-none');
                
                // Reset required attributes
                $('#school_id, #grade, #subject, #org_name, #org_description').prop('required', false);
                
                // Show fields based on selected role
                if(roleId == '2') { // Student
                    $('.school-field, .student-fields').removeClass('d-none');
                    $('#school_id, #grade').prop('required', true);
                } else if(roleId == '3') { // Teacher
                    $('.school-field, .teacher-fields').removeClass('d-none');
                    $('#school_id, #subject').prop('required', true);
                } else if(roleId == '4') { // NGO
                    $('.ngo-fields').removeClass('d-none');
                    $('#org_name').prop('required', true);
                }
            });
            
            // Handle registration form submission
            $('#registerForm').on('submit', function(e) {
                e.preventDefault();
                
                // Hide any previous alerts
                $('#registerAlert').addClass('d-none').text('');
                
                // Validate passwords match
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if(password !== confirmPassword) {
                    $('#registerAlert').removeClass('d-none').text('The passwords you entered do not match.');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Password Mismatch',
                        text: 'The passwords you entered do not match.'
                    });
                    return;
                }
                
                // Validate password strength
                if(password.length < 8) {
                    $('#registerAlert').removeClass('d-none').text('Password must be at least 8 characters long.');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Weak Password',
                        text: 'Password must be at least 8 characters long.'
                    });
                    return;
                }
                
                // Prepare form data
                const formData = $(this).serialize();
                
                // Submit registration
                $.ajax({
                    url: 'api/index.php?action=register',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful',
                                text: 'Welcome to GreenQuest! You can now log in with your credentials.',
                                confirmButtonText: 'Go to Login'
                            }).then(function() {
                                // Redirect to login page or dashboard
                                if(response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    window.location.href = 'login.php';
                                }
                            });
                        } else {
                            // Show error message
                            $('#registerAlert').removeClass('d-none').text(response.message);
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Registration Failed',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        // Show error message
                        $('#registerAlert').removeClass('d-none').text('An error occurred while trying to register. Please try again.');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'An error occurred while trying to register. Please try again.'
                        });
                    }
                });
            });
            
            // Password strength indicator (could be implemented)
            $('#password').on('input', function() {
                // Simple password strength check
                const password = $(this).val();
                let strength = 0;
                
                if(password.length >= 8) strength += 1;
                if(password.match(/[A-Z]/)) strength += 1;
                if(password.match(/[0-9]/)) strength += 1;
                if(password.match(/[^A-Za-z0-9]/)) strength += 1;
                
                // Update UI based on strength (optional implementation)
            });
        });
    </script>
</body>
</html>