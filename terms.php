<?php
// Start session
session_start();

// Include config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = "Terms of Service";

// Include header
include 'common/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="card-title mb-4">Terms of Service</h1>
                    <p class="text-muted mb-4">Last updated: <?php echo date('F d, Y'); ?></p>
                    
                    <div class="terms-content">
                        <section class="mb-4">
                            <h5>1. Introduction</h5>
                            <p>Welcome to GreenQuest. These terms and conditions outline the rules and regulations for the use of GreenQuest's website and services.</p>
                            <p>By accessing this website, we assume you accept these terms and conditions in full. Do not continue to use GreenQuest's website if you do not accept all of the terms and conditions stated on this page.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>2. Definitions</h5>
                            <p>The following terminology applies to these Terms and Conditions, Privacy Policy and any or all Agreements:</p>
                            <ul>
                                <li><strong>"Service"</strong> refers to the GreenQuest platform, including all content, features, and functionality offered.</li>
                                <li><strong>"User"</strong>, <strong>"You"</strong> and <strong>"Your"</strong> refers to the person accessing this website and accepting the Company's terms and conditions.</li>
                                <li><strong>"The Company"</strong>, <strong>"Ourselves"</strong>, <strong>"We"</strong>, <strong>"Our"</strong> and <strong>"Us"</strong>, refers to GreenQuest.</li>
                                <li><strong>"Content"</strong> refers to all information, data, text, software, music, sound, photographs, graphics, video, messages or other materials available through the Service.</li>
                                <li><strong>"Account"</strong> refers to the personalized section of the Service that is created when a User registers.</li>
                            </ul>
                        </section>
                        
                        <section class="mb-4">
                            <h5>3. Account Registration and Security</h5>
                            <p>To use certain features of the Service, you must register for an account. When you register, you will be asked to choose a password. You are solely responsible for maintaining the confidentiality of your account and password and for restricting access to your computer.</p>
                            <p>You agree to accept responsibility for all activities that occur under your account or password. We reserve the right to refuse service, terminate accounts, remove or edit content, or cancel orders at our sole discretion.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>4. User Conduct</h5>
                            <p>You agree to use the Service only for lawful purposes and in a way that does not infringe the rights of, restrict or inhibit anyone else's use and enjoyment of the Service. Prohibited behavior includes:</p>
                            <ul>
                                <li>Harassment, abuse, or threats of any kind against any user or person;</li>
                                <li>Submitting or posting any defamatory, vulgar, obscene, or otherwise objectionable content;</li>
                                <li>Submitting or posting any content that infringes or violates another person's intellectual property rights;</li>
                                <li>Using the Service to distribute unsolicited promotional or commercial content;</li>
                                <li>Attempting to gain unauthorized access to other computer systems through the Service;</li>
                                <li>Interfering with another user's use and enjoyment of the Service;</li>
                                <li>Engaging in any other conduct that restricts or inhibits any person from using or enjoying the Service, or that, in our judgment, exposes us to liability or detriment of any type.</li>
                            </ul>
                        </section>
                        
                        <section class="mb-4">
                            <h5>5. Intellectual Property</h5>
                            <p>The Service and its original content, features, and functionality are owned by GreenQuest and are protected by international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>
                            <p>Unless explicitly stated, nothing in these Terms shall be construed as conferring any license to intellectual property rights, whether by estoppel, implication, or otherwise.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>6. User Content</h5>
                            <p>When you submit, upload, transmit, or display any data, information, media, or other content in connection with your use of the Service ("User Content"), you understand that you retain any copyright and other rights you already hold in this content.</p>
                            <p>By submitting User Content, you grant GreenQuest a worldwide, non-exclusive, royalty-free, sublicensable, and transferable license to use, reproduce, distribute, prepare derivative works of, display, and perform the User Content in connection with the Service and GreenQuest's business, including for promoting and redistributing part or all of the Service.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>7. Educational Content</h5>
                            <p>The educational content provided through the Service is for informational purposes only. While we strive to provide accurate and up-to-date information, we make no representations or warranties of any kind, express or implied, about the completeness, accuracy, reliability, suitability, or availability of the content.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>8. Points, Badges, and Rewards</h5>
                            <p>GreenQuest may offer points, badges, or other virtual rewards as part of the Service. These are provided at our discretion and have no monetary value. We reserve the right to modify, suspend, or discontinue any aspect of this rewards system at any time.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>9. Termination</h5>
                            <p>We may terminate or suspend your account and access to the Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever, including without limitation if you breach the Terms.</p>
                            <p>Upon termination, your right to use the Service will immediately cease. If you wish to terminate your account, you may simply discontinue using the Service or contact us to request account deletion.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>10. Limitation of Liability</h5>
                            <p>In no event shall GreenQuest, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>11. Governing Law</h5>
                            <p>These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which GreenQuest is established, without regard to its conflict of law provisions.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>12. Changes to Terms</h5>
                            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>
                            <p>By continuing to access or use our Service after any revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, you are no longer authorized to use the Service.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>13. Contact Us</h5>
                            <p>If you have any questions about these Terms, please contact us:</p>
                            <ul>
                                <li>By email: legal@greenquest.org</li>
                                <li>By phone: (123) 456-7890</li>
                                <li>By mail: 123 Eco Street, Green City</li>
                            </ul>
                        </section>
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