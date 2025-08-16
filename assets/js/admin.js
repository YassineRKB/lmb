jQuery(document).ready(function($) {
    // Quick status change functionality
    $('.lmb-quick-status-change').on('change', function() {
        const select = $(this);
        const postId = select.data('post-id');
        const newStatus = select.val();
        
        if (!newStatus || !confirm(lmbAdmin.strings.confirm_status_change)) {
            select.val('');
            return;
        }
        
        select.prop('disabled', true);
        
        $.ajax({
            url: lmbAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_quick_status_change',
                post_id: postId,
                new_status: newStatus,
                nonce: lmbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the status badge
                    const statusBadge = select.closest('tr').find('.lmb-status-badge');
                    statusBadge.removeClass().addClass('lmb-status-badge lmb-status-' + newStatus.replace('_', '-'));
                    statusBadge.text(newStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
                    
                    // Show success message
                    showNotice(lmbAdmin.strings.status_changed, 'success');
                } else {
                    showNotice(response.data.message || lmbAdmin.strings.error_occurred, 'error');
                }
                select.prop('disabled', false).val('');
            },
            error: function() {
                showNotice(lmbAdmin.strings.error_occurred, 'error');
                select.prop('disabled', false).val('');
            }
        });
    });
    
    // Quick approve functionality
    $('.lmb-quick-approve').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const postId = button.data('post-id');
        
        if (!confirm('Are you sure you want to approve this ad?')) {
            return;
        }
        
        button.prop('disabled', true).text('Approving...');
        
        $.ajax({
            url: lmbAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_quick_status_change',
                post_id: postId,
                new_status: 'published',
                nonce: lmbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                    showNotice('Ad approved successfully!', 'success');
                } else {
                    showNotice(response.data.message || lmbAdmin.strings.error_occurred, 'error');
                    button.prop('disabled', false).text('Approve');
                }
            },
            error: function() {
                showNotice(lmbAdmin.strings.error_occurred, 'error');
                button.prop('disabled', false).text('Approve');
            }
        });
    });
    
    // Quick deny functionality
    $('.lmb-quick-deny').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const postId = button.data('post-id');
        
        if (!confirm('Are you sure you want to deny this ad?')) {
            return;
        }
        
        button.prop('disabled', true).text('Denying...');
        
        $.ajax({
            url: lmbAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_quick_status_change',
                post_id: postId,
                new_status: 'denied',
                nonce: lmbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut();
                    showNotice('Ad denied.', 'success');
                } else {
                    showNotice(response.data.message || lmbAdmin.strings.error_occurred, 'error');
                    button.prop('disabled', false).text('Deny');
                }
            },
            error: function() {
                showNotice(lmbAdmin.strings.error_occurred, 'error');
                button.prop('disabled', false).text('Deny');
            }
        });
    });
    
    // Bulk actions confirmation
    $('form#posts-filter').on('submit', function(e) {
        const action = $('select[name="action"]').val() || $('select[name="action2"]').val();
        
        if (action && action.startsWith('lmb_bulk_')) {
            if (!confirm(lmbAdmin.strings.confirm_bulk_action)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Dashboard stats refresh
    $('.lmb-refresh-stats').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        button.prop('disabled', true).text('Refreshing...');
        
        $.ajax({
            url: lmbAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_get_ad_stats',
                nonce: lmbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotice('Failed to refresh stats', 'error');
                }
                button.prop('disabled', false).text('Refresh Stats');
            },
            error: function() {
                showNotice('Failed to refresh stats', 'error');
                button.prop('disabled', false).text('Refresh Stats');
            }
        });
    });
    
    // Auto-dismiss notices
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut();
    }, 5000);
    
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    // Helper function to show notices
    function showNotice(message, type) {
        const noticeClass = 'notice notice-' + type + ' is-dismissible';
        const notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 3000);
    }
    
    // Initialize dashboard charts
    function initializeCharts() {
        // Ad types chart
        const adTypesCanvas = document.getElementById('lmb-ad-types-chart');
        if (adTypesCanvas && typeof adTypesData !== 'undefined') {
            new Chart(adTypesCanvas, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(adTypesData),
                    datasets: [{
                        data: Object.values(adTypesData),
                        backgroundColor: [
                            '#0073aa', '#00a32a', '#d63638', '#dba617',
                            '#8b5cf6', '#f59e0b', '#ef4444', '#10b981'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Monthly submissions chart
        const monthlyCanvas = document.getElementById('lmb-monthly-chart');
        if (monthlyCanvas && typeof monthlyData !== 'undefined') {
            new Chart(monthlyCanvas, {
                type: 'line',
                data: {
                    labels: Object.keys(monthlyData),
                    datasets: [{
                        label: 'Submissions',
                        data: Object.values(monthlyData),
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
    
    // Form validation
    $('.lmb-form').on('submit', function(e) {
        const form = $(this);
        let hasErrors = false;
        
        // Clear previous errors
        form.find('.lmb-field-error').removeClass('lmb-field-error');
        form.find('.lmb-error-message').remove();
        
        // Validate required fields
        form.find('[required]').each(function() {
            const field = $(this);
            if (!field.val().trim()) {
                field.addClass('lmb-field-error');
                field.after('<div class="lmb-error-message">This field is required.</div>');
                hasErrors = true;
            }
        });
        
        // Validate email fields
        form.find('input[type="email"]').each(function() {
            const field = $(this);
            const email = field.val().trim();
            if (email && !isValidEmail(email)) {
                field.addClass('lmb-field-error');
                field.after('<div class="lmb-error-message">Please enter a valid email address.</div>');
                hasErrors = true;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            form.find('.lmb-field-error').first().focus();
        }
    });
    
    // Email validation helper
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Real-time form validation
    $('.lmb-form input, .lmb-form select, .lmb-form textarea').on('blur', function() {
        const field = $(this);
        field.removeClass('lmb-field-error');
        field.next('.lmb-error-message').remove();
        
        if (field.prop('required') && !field.val().trim()) {
            field.addClass('lmb-field-error');
            field.after('<div class="lmb-error-message">This field is required.</div>');
        }
        
        if (field.attr('type') === 'email' && field.val().trim() && !isValidEmail(field.val().trim())) {
            field.addClass('lmb-field-error');
            field.after('<div class="lmb-error-message">Please enter a valid email address.</div>');
        }
    });
});

// Global LMB Admin object
window.LMB_Admin = {
    initCharts: function(adTypesData, monthlyData) {
        // Store data globally for chart initialization
        window.adTypesData = adTypesData;
        window.monthlyData = monthlyData;
        
        // Initialize charts if Chart.js is loaded
        if (typeof Chart !== 'undefined') {
            jQuery(document).ready(function() {
                // Charts will be initialized by the main ready function
            });
        }
    }
};