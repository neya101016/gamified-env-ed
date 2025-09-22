<?php
// Start session
session_start();

// Include config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = "Cookie Policy";

// Include header
include 'common/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="card-title mb-4">Cookie Policy</h1>
                    <p class="text-muted mb-4">Last updated: <?php echo date('F d, Y'); ?></p>
                    
                    <div class="cookie-content">
                        <section class="mb-4">
                            <h5>1. Introduction</h5>
                            <p>This Cookie Policy explains how GreenQuest ("we", "us", and "our") uses cookies and similar technologies to recognize you when you visit our website. It explains what these technologies are and why we use them, as well as your rights to control our use of them.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>2. What Are Cookies?</h5>
                            <p>Cookies are small data files that are placed on your computer or mobile device when you visit a website. Cookies are widely used by website owners in order to make their websites work, or to work more efficiently, as well as to provide reporting information.</p>
                            <p>Cookies set by the website owner (in this case, GreenQuest) are called "first party cookies". Cookies set by parties other than the website owner are called "third party cookies". Third party cookies enable third party features or functionality to be provided on or through the website (e.g., advertising, interactive content and analytics). The parties that set these third party cookies can recognize your computer both when it visits the website in question and also when it visits certain other websites.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>3. Why Do We Use Cookies?</h5>
                            <p>We use first and third party cookies for several reasons. Some cookies are required for technical reasons in order for our website to operate, and we refer to these as "essential" or "strictly necessary" cookies. Other cookies also enable us to track and target the interests of our users to enhance the experience on our online properties. Third parties serve cookies through our website for advertising, analytics and other purposes.</p>
                            <p>The specific types of first and third party cookies served through our website and the purposes they perform are described below:</p>
                            
                            <h6 class="mt-3">Essential Cookies</h6>
                            <p>These cookies are strictly necessary to provide you with services available through our website and to use some of its features, such as access to secure areas. Because these cookies are strictly necessary to deliver the website, you cannot refuse them without impacting how our website functions.</p>
                            
                            <h6 class="mt-3">Performance and Functionality Cookies</h6>
                            <p>These cookies are used to enhance the performance and functionality of our website but are non-essential to their use. However, without these cookies, certain functionality may become unavailable.</p>
                            
                            <h6 class="mt-3">Analytics and Customization Cookies</h6>
                            <p>These cookies collect information that is used either in aggregate form to help us understand how our website is being used or how effective our marketing campaigns are, or to help us customize our website for you.</p>
                            
                            <h6 class="mt-3">Advertising Cookies</h6>
                            <p>These cookies are used to make advertising messages more relevant to you. They perform functions like preventing the same ad from continuously reappearing, ensuring that ads are properly displayed for advertisers, and in some cases selecting advertisements that are based on your interests.</p>
                            
                            <h6 class="mt-3">Social Media Cookies</h6>
                            <p>These cookies are used to enable you to share pages and content that you find interesting on our website through third party social networking and other websites. These cookies may also be used for advertising purposes.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>4. How Can You Control Cookies?</h5>
                            <p>You have the right to decide whether to accept or reject cookies. You can exercise your cookie preferences by clicking on the appropriate opt-out links provided below.</p>
                            <p>You can set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our website though your access to some functionality and areas of our website may be restricted. As the means by which you can refuse cookies through your web browser controls vary from browser-to-browser, you should visit your browser's help menu for more information.</p>
                            <p>In addition, most advertising networks offer you a way to opt out of targeted advertising. If you would like to find out more information, please visit <a href="http://www.aboutads.info/choices/" target="_blank">http://www.aboutads.info/choices/</a> or <a href="http://www.youronlinechoices.com" target="_blank">http://www.youronlinechoices.com</a>.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>5. Cookies We Use</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cookie Name</th>
                                            <th>Purpose</th>
                                            <th>Duration</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PHPSESSID</td>
                                            <td>Preserves user session state across page requests</td>
                                            <td>Session</td>
                                            <td>Essential</td>
                                        </tr>
                                        <tr>
                                            <td>user_login</td>
                                            <td>Remembers user login status for returning users</td>
                                            <td>30 days</td>
                                            <td>Functionality</td>
                                        </tr>
                                        <tr>
                                            <td>eco_preferences</td>
                                            <td>Stores user preferences for site customization</td>
                                            <td>1 year</td>
                                            <td>Functionality</td>
                                        </tr>
                                        <tr>
                                            <td>_ga</td>
                                            <td>Google Analytics - Registers a unique ID used to generate statistical data</td>
                                            <td>2 years</td>
                                            <td>Analytics</td>
                                        </tr>
                                        <tr>
                                            <td>_gid</td>
                                            <td>Google Analytics - Registers a unique ID used to generate statistical data</td>
                                            <td>24 hours</td>
                                            <td>Analytics</td>
                                        </tr>
                                        <tr>
                                            <td>_gat</td>
                                            <td>Google Analytics - Used to throttle request rate</td>
                                            <td>1 minute</td>
                                            <td>Analytics</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        
                        <section class="mb-4">
                            <h5>6. Updates to This Cookie Policy</h5>
                            <p>We may update this Cookie Policy from time to time in order to reflect, for example, changes to the cookies we use or for other operational, legal or regulatory reasons. Please therefore re-visit this Cookie Policy regularly to stay informed about our use of cookies and related technologies.</p>
                            <p>The date at the top of this Cookie Policy indicates when it was last updated.</p>
                        </section>
                        
                        <section class="mb-4">
                            <h5>7. Where Can You Get Further Information?</h5>
                            <p>If you have any questions about our use of cookies or other technologies, please email us at privacy@greenquest.org or by post to:</p>
                            <address class="mb-0">
                                GreenQuest<br>
                                123 Eco Street<br>
                                Green City
                            </address>
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