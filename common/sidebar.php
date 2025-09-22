<?php
// Common sidebar file for GreenQuest application dashboard pages
// Include this in dashboard pages between header and main content

// Get user role for role-specific sidebar items
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Determine active page from the current filename
$current_page = basename($_SERVER['PHP_SELF']);

// Function to check if a page is active for sidebar highlighting
function isSidebarActive($page) {
    global $current_page;
    return ($current_page == $page) ? 'active' : '';
}
?>

<!-- Sidebar -->
<div class="sidebar bg-light border-end">
    <div class="user-profile text-center p-3 border-bottom">
        <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
            <img src="<?php echo getBaseUrl() . 'uploads/profile_pics/' . htmlspecialchars($_SESSION['profile_picture']); ?>" 
                 alt="Profile Picture" class="rounded-circle mb-2" width="80" height="80">
        <?php else: ?>
            <img src="<?php echo getBaseUrl(); ?>uploads/avatars/default_avatar.png" 
                 alt="Default Avatar" class="rounded-circle mb-2" width="80" height="80">
        <?php endif; ?>
        <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['name']); ?></h6>
        <small class="text-muted"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></small>
        <?php if (isset($_SESSION['eco_points'])): ?>
            <div class="mt-2">
                <span class="badge bg-success"><?php echo number_format($_SESSION['eco_points']); ?> Eco-Points</span>
            </div>
        <?php endif; ?>
    </div>
    
    <nav class="sidebar-nav p-3">
        <ul class="nav flex-column">
            <?php if ($user_role == 'student'): ?>
                <!-- Student Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>student/dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('lessons.php'); ?>" href="<?php echo getBaseUrl(); ?>student/lessons.php">
                        <i class="fas fa-book me-2"></i>Lessons
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['lesson_details.php', 'quiz.php', 'quiz_result.php']) ? 'active' : ''; ?>" href="#lessonSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-graduation-cap me-2"></i>Learning
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <ul class="collapse <?php echo in_array($current_page, ['lesson_details.php', 'quiz.php', 'quiz_result.php']) ? 'show' : ''; ?> list-unstyled ms-3" id="lessonSubmenu">
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('lesson_details.php'); ?>" href="<?php echo getBaseUrl(); ?>student/lessons.php">
                                <i class="fas fa-file-alt me-2"></i>Lesson Content
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('quiz.php') || isSidebarActive('quiz_result.php'); ?>" href="<?php echo getBaseUrl(); ?>student/lessons.php">
                                <i class="fas fa-question-circle me-2"></i>Quizzes
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('badges.php'); ?>" href="<?php echo getBaseUrl(); ?>student/badges.php">
                        <i class="fas fa-award me-2"></i>Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>student/leaderboard.php">
                        <i class="fas fa-trophy me-2"></i>Leaderboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo getBaseUrl(); ?>points_history.php">
                        <i class="fas fa-history me-2"></i>Points History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('profile.php'); ?>" href="<?php echo getBaseUrl(); ?>student/profile.php">
                        <i class="fas fa-user-cog me-2"></i>Profile
                    </a>
                </li>
            <?php elseif ($user_role == 'teacher'): ?>
                <!-- Teacher Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['lessons.php', 'create_lesson.php']) ? 'active' : ''; ?>" href="#lessonManagement" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-book me-2"></i>Lessons
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <ul class="collapse <?php echo in_array($current_page, ['lessons.php', 'create_lesson.php']) ? 'show' : ''; ?> list-unstyled ms-3" id="lessonManagement">
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('lessons.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/lessons.php">
                                <i class="fas fa-list me-2"></i>Manage Lessons
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('create_lesson.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/create_lesson.php">
                                <i class="fas fa-plus-circle me-2"></i>Create Lesson
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('students.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/students.php">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/leaderboard.php">
                        <i class="fas fa-trophy me-2"></i>Leaderboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('profile.php'); ?>" href="<?php echo getBaseUrl(); ?>teacher/profile.php">
                        <i class="fas fa-user-cog me-2"></i>Profile
                    </a>
                </li>
            <?php elseif ($user_role == 'ngo'): ?>
                <!-- NGO Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['challenges.php', 'create_challenge.php', 'challenge_details.php']) ? 'active' : ''; ?>" href="#challengeManagement" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-tasks me-2"></i>Challenges
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <ul class="collapse <?php echo in_array($current_page, ['challenges.php', 'create_challenge.php', 'challenge_details.php']) ? 'show' : ''; ?> list-unstyled ms-3" id="challengeManagement">
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('challenges.php') || isSidebarActive('challenge_details.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/challenges.php">
                                <i class="fas fa-list me-2"></i>Manage Challenges
                            </a>
                        </li>
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('create_challenge.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/create_challenge.php">
                                <i class="fas fa-plus-circle me-2"></i>Create Challenge
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('verifications.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/verifications.php">
                        <i class="fas fa-check-circle me-2"></i>Verifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('impact.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/impact.php">
                        <i class="fas fa-chart-line me-2"></i>Impact
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('profile.php'); ?>" href="<?php echo getBaseUrl(); ?>ngo/profile.php">
                        <i class="fas fa-user-cog me-2"></i>Profile
                    </a>
                </li>
            <?php elseif ($user_role == 'admin'): ?>
                <!-- Admin Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('dashboard.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['users.php', 'get_user.php', 'process_user.php']) ? 'active' : ''; ?>" href="#userManagement" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-users me-2"></i>Users
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <ul class="collapse <?php echo in_array($current_page, ['users.php', 'get_user.php', 'process_user.php']) ? 'show' : ''; ?> list-unstyled ms-3" id="userManagement">
                        <li>
                            <a class="nav-link <?php echo isSidebarActive('users.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/users.php">
                                <i class="fas fa-list me-2"></i>All Users
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/users.php?role=student">
                                <i class="fas fa-user-graduate me-2"></i>Students
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/users.php?role=teacher">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/users.php?role=ngo">
                                <i class="fas fa-building me-2"></i>NGOs
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['content.php', 'get_content.php']) ? 'active' : ''; ?>" href="#contentManagement" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-file-alt me-2"></i>Content
                        <i class="fas fa-angle-down float-end mt-1"></i>
                    </a>
                    <ul class="collapse <?php echo in_array($current_page, ['content.php', 'get_content.php']) ? 'show' : ''; ?> list-unstyled ms-3" id="contentManagement">
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/content.php?type=lessons">
                                <i class="fas fa-book me-2"></i>Lessons
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/content.php?type=quizzes">
                                <i class="fas fa-question-circle me-2"></i>Quizzes
                            </a>
                        </li>
                        <li>
                            <a class="nav-link" href="<?php echo getBaseUrl(); ?>admin/content.php?type=challenges">
                                <i class="fas fa-tasks me-2"></i>Challenges
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('badges.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/badges.php">
                        <i class="fas fa-award me-2"></i>Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('leaderboard.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/leaderboard.php">
                        <i class="fas fa-trophy me-2"></i>Leaderboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isSidebarActive('analytics.php'); ?>" href="<?php echo getBaseUrl(); ?>admin/analytics.php">
                        <i class="fas fa-chart-bar me-2"></i>Analytics
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item mt-2">
                <a class="nav-link text-danger" href="<?php echo getBaseUrl(); ?>logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>
    </nav>
</div>