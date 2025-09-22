// Main JavaScript file for GreenQuest Platform

// Initialize common functionality when document is ready
$(document).ready(function() {
    // Session timeout handling
    setupSessionTimeout();
    
    // Setup AJAX global settings
    setupAjaxDefaults();
    
    // Setup common event handlers
    setupEventHandlers();
});

// Session timeout handling
function setupSessionTimeout() {
    let timeoutTimer;
    let countdownTimer;
    let countdownValue;
    const sessionTimeoutModal = document.getElementById('sessionTimeoutModal');
    
    // Only setup if the modal exists
    if (!sessionTimeoutModal) return;
    
    function startSessionTimer() {
        clearTimeout(timeoutTimer);
        timeoutTimer = setTimeout(showTimeoutWarning, 25 * 60 * 1000); // 25 minutes
    }
    
    function showTimeoutWarning() {
        const modal = new bootstrap.Modal(sessionTimeoutModal);
        modal.show();
        
        countdownValue = 30;
        $('#countdown').text(countdownValue);
        
        clearInterval(countdownTimer);
        countdownTimer = setInterval(function() {
            countdownValue--;
            $('#countdown').text(countdownValue);
            
            // Update progress bar
            const percentage = (countdownValue / 30) * 100;
            $('.progress-bar').css('width', percentage + '%');
            
            if(countdownValue <= 0) {
                clearInterval(countdownTimer);
                window.location.href = '../logout.php';
            }
        }, 1000);
    }
    
    $('#stayLoggedInBtn').on('click', function() {
        clearInterval(countdownTimer);
        bootstrap.Modal.getInstance(sessionTimeoutModal).hide();
        
        // Reset session timer by making an AJAX call
        $.ajax({
            url: '../api/index.php?action=check_session',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'active') {
                    startSessionTimer();
                } else {
                    window.location.href = '../login.php';
                }
            }
        });
    });
    
    $('#logoutNowBtn').on('click', function() {
        clearInterval(countdownTimer);
        window.location.href = '../logout.php';
    });
    
    // Check for activity to reset timer
    $(document).on('click keypress', function() {
        startSessionTimer();
    });
    
    // Start the initial timer
    startSessionTimer();
    
    // Periodically check session status
    setInterval(function() {
        $.ajax({
            url: '../api/index.php?action=check_session',
            type: 'GET',
            dataType: 'json'
        });
    }, 5 * 60 * 1000); // Every 5 minutes
}

// Setup global AJAX defaults
function setupAjaxDefaults() {
    // Set up AJAX error handling
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        // Only show error for non-abort errors
        if (jqXHR.statusText !== 'abort') {
            console.error('AJAX Error:', thrownError || jqXHR.statusText);
            
            // Show error message if SweetAlert is available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'An error occurred while connecting to the server. Please try again later.',
                    confirmButtonText: 'OK'
                });
            }
        }
    });
}

// Setup common event handlers
function setupEventHandlers() {
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const passwordField = $($(this).data('target'));
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Confirm actions with SweetAlert
    $('.confirm-action').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this).data('target') || $(this).attr('href');
        const title = $(this).data('title') || 'Are you sure?';
        const text = $(this).data('text') || 'This action cannot be undone.';
        const icon = $(this).data('icon') || 'warning';
        const confirmText = $(this).data('confirm-text') || 'Yes, proceed';
        const cancelText = $(this).data('cancel-text') || 'Cancel';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText
            }).then((result) => {
                if (result.isConfirmed) {
                    if (target) {
                        window.location.href = target;
                    }
                }
            });
        } else {
            if (confirm(title + '\n' + text)) {
                if (target) {
                    window.location.href = target;
                }
            }
        }
    });
    
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

// Get leaderboard data and update UI
function refreshLeaderboard(type, period, container) {
    const params = {
        type: type || 'global',
        period: period || 'all'
    };
    
    // Add school_id param if type is school
    if (type === 'school' && typeof schoolId !== 'undefined') {
        params.school_id = schoolId;
    }
    
    $.ajax({
        url: '../api/index.php?action=get_leaderboard',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && container) {
                updateLeaderboardUI(container, response.leaderboard);
            }
        }
    });
}

// Update leaderboard UI with data
function updateLeaderboardUI(container, data) {
    const $container = $(container);
    $container.empty();
    
    if (data.length === 0) {
        $container.append('<div class="text-center py-3"><p class="text-muted">No data available</p></div>');
        return;
    }
    
    data.forEach((item, index) => {
        let rankClass = 'rank-other';
        if (index === 0) rankClass = 'rank-1';
        else if (index === 1) rankClass = 'rank-2';
        else if (index === 2) rankClass = 'rank-3';
        
        const html = `
            <div class="leaderboard-item">
                <div class="leaderboard-rank ${rankClass}">${index + 1}</div>
                <div class="flex-grow-1">
                    <h6 class="mb-0">${escapeHtml(item.name)}</h6>
                    <small class="text-muted">${item.school_name ? escapeHtml(item.school_name) : 'N/A'}</small>
                </div>
                <div class="text-end">
                    <h5 class="mb-0 text-primary">${numberWithCommas(item.total_points || 0)}</h5>
                    <small class="text-muted">${item.badge_count || 0} badges</small>
                </div>
            </div>
        `;
        
        $container.append(html);
    });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Format numbers with commas
function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Check if user has earned a new badge
function checkForNewBadge(userId) {
    $.ajax({
        url: '../api/index.php?action=get_user_badges',
        type: 'GET',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const badges = response.badges.filter(badge => badge.earned);
                
                // Get last awarded badge (if any)
                const recentBadges = badges.filter(badge => {
                    if (!badge.awarded_at) return false;
                    
                    const awardedDate = new Date(badge.awarded_at);
                    const now = new Date();
                    const diffTime = Math.abs(now - awardedDate);
                    const diffMinutes = Math.floor(diffTime / (1000 * 60));
                    
                    // Consider badges awarded in the last 5 minutes as "new"
                    return diffMinutes < 5;
                });
                
                if (recentBadges.length > 0) {
                    const latestBadge = recentBadges[0];
                    
                    // Show celebration
                    Swal.fire({
                        title: 'New Badge Earned!',
                        text: `Congratulations! You've earned the "${latestBadge.name}" badge.`,
                        icon: 'success',
                        confirmButtonText: 'Awesome!',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                }
            }
        }
    });
}