<?php
// Start session
session_start();

// Include config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = "Privacy Policy";

// Include header
include 'common/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="card-title mb-4">Privacy Policy</h1>
                    <p class="text-muted mb-4">Last updated: <?php echo date('F d, Y'); ?></p>
                    
                    <div class="policy-content">
                        <section class="mb-4">
                            <h5>1. Introduction</h5>
                            <p>Welcome to GreenQuest. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our website and tell you about your privacy rights and how the law protects you.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>2. The Data We Collect About You</h5>
                            <p>Personal data, or personal information, means any information about an individual from which that person can be identified. We may collect, use, store and transfer different kinds of personal data about you which we have grouped together as follows:</p>
                            
                            <ul>
                                <li><strong>Identity Data</strong> includes first name, last name, username or similar identifier.</li>
                                <li><strong>Contact Data</strong> includes email address and telephone numbers.</li>
                                <li><strong>Technical Data</strong> includes internet protocol (IP) address, your login data, browser type and version, time zone setting and location, browser plug-in types and versions, operating system and platform, and other technology on the devices you use to access this website.</li>
                                <li><strong>Profile Data</strong> includes your username and password, your interests, preferences, feedback and survey responses.</li>
                                <li><strong>Usage Data</strong> includes information about how you use our website, products and services.</li>
                                <li><strong>Education Data</strong> includes your quiz scores, completed lessons, challenges, badges, and eco-points earned.</li>
                            </ul>
                        </section>
                        
                        <section class="mb-4">
                            <h5>3. How We Use Your Personal Data</h5>
                            <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                            
                            <ul>
                                <li>Where we need to perform the contract we are about to enter into or have entered into with you.</li>
                                <li>Where it is necessary for our legitimate interests (or those of a third party) and your interests and fundamental rights do not override those interests.</li>
                                <li>Where we need to comply with a legal obligation.</li>
                            </ul>
                            
                            <p>We have set out below a description of all the ways we plan to use your personal data, and which of the legal bases we rely on to do so:</p>
                            
                            <ul>
                                <li>To register you as a new user</li>
                                <li>To deliver educational content to you</li>
                                <li>To manage your relationship with us</li>
                                <li>To track your progress and award eco-points and badges</li>
                                <li>To personalize your experience</li>
                                <li>To administer and protect our business and this website</li>
                                <li>To use data analytics to improve our website, products/services, marketing, customer relationships and experiences</li>
                            </ul>
                        </section>
                        
                        <section class="mb-4">
                            <h5>4. Data Security</h5>
                            <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed. In addition, we limit access to your personal data to those employees, agents, contractors and other third parties who have a business need to know. They will only process your personal data on our instructions and they are subject to a duty of confidentiality.</p>
                            <p>We have put in place procedures to deal with any suspected personal data breach and will notify you and any applicable regulator of a breach where we are legally required to do so.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>5. Data Retention</h5>
                            <p>We will only retain your personal data for as long as reasonably necessary to fulfill the purposes we collected it for, including for the purposes of satisfying any legal, regulatory, tax, accounting or reporting requirements. We may retain your personal data for a longer period in the event of a complaint or if we reasonably believe there is a prospect of litigation in respect to our relationship with you.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>6. Your Legal Rights</h5>
                            <p>Under certain circumstances, you have rights under data protection laws in relation to your personal data. These include the right to:</p>
                            
                            <ul>
                                <li>Request access to your personal data</li>
                                <li>Request correction of your personal data</li>
                                <li>Request erasure of your personal data</li>
                                <li>Object to processing of your personal data</li>
                                <li>Request restriction of processing your personal data</li>
                                <li>Request transfer of your personal data</li>
                                <li>Right to withdraw consent</li>
                            </ul>
                            
                            <p>If you wish to exercise any of the rights set out above, please contact us at privacy@greenquest.org.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>7. Children's Privacy</h5>
                            <p>Our service is directed to children and students, but we take additional steps to protect children's privacy. We do not knowingly collect personal information from children under 13 without parental consent. If you are a parent or guardian and you are aware that your child has provided us with personal information without your consent, please contact us so that we can take necessary actions.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>8. Changes to This Privacy Policy</h5>
                            <p>We may update our privacy policy from time to time. We will notify you of any changes by posting the new privacy policy on this page and updating the "last updated" date at the top of this privacy policy.</p>
                            <p>You are advised to review this privacy policy periodically for any changes. Changes to this privacy policy are effective when they are posted on this page.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>9. Contact Us</h5>
                            <p>If you have any questions about this privacy policy, please contact us:</p>
                            <ul>
                                <li>By email: privacy@greenquest.org</li>
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