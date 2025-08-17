/**
 * LMB Core Frontend JavaScript
 */
(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        LMBCore.init();
    });

    // Main LMB Core object
    window.LMBCore = {
        
        /**
         * Initialize all components
         */
        init: function() {
            this.initModals();
            this.initForms();
            this.initFilters();
            this.initLoadMore();
            this.initTooltips();
        },

        /**
         * Initialize modal functionality
         */
        initModals: function() {
            // Ad details modal
            $(document).on('click', '.lmb-view-details', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var adId = $button.data('id');
                var $adItem = $button.closest('.lmb-ad');
                var fullContent = $adItem.find('.lmb-ad-full-content').html();
                var title = $adItem.find('.lmb-ad-title').text();
                
                if (fullContent) {
                    var modalContent = '<h3>' + title + '</h3>' + fullContent;
                    LMBCore.showModal(modalContent);
                }
            });

            // Close modal handlers
            $(document).on('click', '.lmb-modal-close, .lmb-modal', function(e) {
                if (e.target === this) {
                    LMBCore.hideModal();
                }
            });

            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    LMBCore.hideModal();
                }
            });
        },

        /**
         * Show modal with content
         */
        showModal: function(content) {
            var modalHtml = '<div id="lmb-modal" class="lmb-modal">' +
                           '<div class="lmb-modal-content">' +
                           '<span class="lmb-modal-close">&times;</span>' +
                           '<div class="lmb-modal-body">' + content + '</div>' +
                           '</div></div>';
            
            $('body').append(modalHtml);
            $('#lmb-modal').fadeIn(300);
            $('body').addClass('lmb-modal-open');
        },

        /**
         * Hide modal
         */
        hideModal: function() {
            $('#lmb-modal').fadeOut(300, function() {
                $(this).remove();
                $('body').removeClass('lmb-modal-open');
            });
        },

        /**
         * Initialize form enhancements
         */
        initForms: function() {
            // Form validation
            $('.lmb-form').on('submit', function(e) {
                var $form = $(this);
                var isValid = LMBCore.validateForm($form);
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                LMBCore.setFormLoading($form, true);
            });

            // Real-time validation
            $('.lmb-form input, .lmb-form select, .lmb-form textarea').on('blur', function() {
                LMBCore.validateField($(this));
            });

            // File upload preview
            $('.lmb-form input[type="file"]').on('change', function() {
                LMBCore.handleFilePreview($(this));
            });
        },

        /**
         * Validate form
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Clear previous errors
            $form.find('.lmb-field-error').removeClass('lmb-field-error');
            $form.find('.lmb-error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                if (!LMBCore.validateField($(this))) {
                    isValid = false;
                }
            });
            
            return isValid;
        },

        /**
         * Validate individual field
         */
        validateField: function($field) {
            var value = $field.val().trim();
            var isValid = true;
            var errorMessage = '';
            
            // Remove previous error state
            $field.removeClass('lmb-field-error');
            $field.next('.lmb-error-message').remove();
            
            // Required field validation
            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }
            
            // File validation
            if ($field.attr('type') === 'file' && $field[0].files.length > 0) {
                var file = $field[0].files[0];
                var maxSize = 5 * 1024 * 1024; // 5MB
                var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                
                if (file.size > maxSize) {
                    isValid = false;
                    errorMessage = 'File size must be less than 5MB.';
                } else if (!allowedTypes.includes(file.type)) {
                    isValid = false;
                    errorMessage = 'Please upload a valid file (JPG, PNG, or PDF).';
                }
            }
            
            // Show error if validation failed
            if (!isValid) {
                $field.addClass('lmb-field-error');
                $field.after('<div class="lmb-error-message">' + errorMessage + '</div>');
            }
            
            return isValid;
        },

        /**
         * Handle file upload preview
         */
        handleFilePreview: function($input) {
            var file = $input[0].files[0];
            if (!file) return;
            
            // Remove existing preview
            $input.siblings('.lmb-file-preview').remove();
            
            var previewHtml = '<div class="lmb-file-preview">' +
                             '<span class="lmb-file-name">' + file.name + '</span>' +
                             '<span class="lmb-file-size">(' + LMBCore.formatFileSize(file.size) + ')</span>' +
                             '</div>';
            
            $input.after(previewHtml);
        },

        /**
         * Format file size for display
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Set form loading state
         */
        setFormLoading: function($form, loading) {
            if (loading) {
                $form.addClass('lmb-loading');
                $form.find('button[type="submit"]').prop('disabled', true).text('Processing...');
            } else {
                $form.removeClass('lmb-loading');
                $form.find('button[type="submit"]').prop('disabled', false).text('Submit');
            }
        },

        /**
         * Initialize filter functionality
         */
        initFilters: function() {
            // Auto-submit filters on change
            $('.lmb-filter-form select').on('change', function() {
                $(this).closest('form').submit();
            });

            // Clear filters
            $(document).on('click', '.lmb-clear-filters', function(e) {
                e.preventDefault();
                
                var $form = $('.lmb-filter-form');
                $form.find('input, select').val('');
                $form.submit();
            });

            // Search input debouncing
            var searchTimeout;
            $('.lmb-search-input').on('input', function() {
                clearTimeout(searchTimeout);
                var $input = $(this);
                
                searchTimeout = setTimeout(function() {
                    if ($input.val().length >= 3 || $input.val().length === 0) {
                        $input.closest('form').submit();
                    }
                }, 500);
            });
        },

        /**
         * Initialize load more functionality
         */
        initLoadMore: function() {
            $(document).on('click', '.lmb-load-more', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var page = parseInt($button.data('page')) + 1;
                var $container = $button.data('container');
                
                $button.text('Loading...').prop('disabled', true);
                
                // Make AJAX request to load more content
                $.ajax({
                    url: window.location.href,
                    type: 'GET',
                    data: {
                        paged: page,
                        ajax: 1
                    },
                    success: function(response) {
                        var $newContent = $(response).find($container + ' > *');
                        
                        if ($newContent.length > 0) {
                            $($container).append($newContent);
                            $button.data('page', page);
                            $button.text('Load More').prop('disabled', false);
                        } else {
                            $button.text('No more items').prop('disabled', true);
                        }
                    },
                    error: function() {
                        $button.text('Error loading content').prop('disabled', true);
                    }
                });
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                if (tooltipText) {
                    var $tooltip = $('<div class="lmb-tooltip">' + tooltipText + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 10,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    }).fadeIn(200);
                }
            });

            $(document).on('mouseleave', '[data-tooltip]', function() {
                $('.lmb-tooltip').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Show notification message
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="lmb-notification lmb-notification-' + type + '">' +
                                '<span class="lmb-notification-message">' + message + '</span>' +
                                '<button class="lmb-notification-close">&times;</button>' +
                                '</div>');
            
            $('body').append($notification);
            
            // Position notification
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999
            }).fadeIn(300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close
            $notification.find('.lmb-notification-close').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Utility function to get URL parameters
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Utility function to update URL parameter
         */
        updateUrlParameter: function(key, value) {
            var url = new URL(window.location);
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
            window.history.replaceState({}, '', url);
        }
    };

    // Add CSS for notifications and tooltips
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .lmb-modal-open { overflow: hidden; }
            
            .lmb-notification {
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 300px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .lmb-notification-success { border-left: 4px solid #00a32a; }
            .lmb-notification-error { border-left: 4px solid #d63638; }
            .lmb-notification-warning { border-left: 4px solid #dba617; }
            .lmb-notification-info { border-left: 4px solid #0073aa; }
            
            .lmb-notification-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
                color: #666;
            }
            
            .lmb-tooltip {
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                white-space: nowrap;
            }
            
            .lmb-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: #333 transparent transparent transparent;
            }
            
            .lmb-field-error {
                border-color: #d63638 !important;
                box-shadow: 0 0 0 1px #d63638;
            }
            
            .lmb-error-message {
                color: #d63638;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }
            
            .lmb-file-preview {
                margin-top: 10px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .lmb-file-size {
                color: #666;
                margin-left: 10px;
            }
        `)
        .appendTo('head');

})(jQuery);