// =====================================================
// MODERN PROJECT MANAGEMENT SYSTEM - JAVASCRIPT
// Interactive Features & Smooth Animations
// =====================================================

$(document).ready(function() {
    
    // Initialize all interactive features
    initSmoothScrolling();
    initAnimations();
    initTooltips();
    initModals();
    initFormValidation();
    initSearchFeatures();
    initNotifications();
    initDashboardCharts();
    initInteractiveElements();
    
    // ===== SMOOTH SCROLLING =====
    function initSmoothScrolling() {
        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if(target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 800, 'swing');
            }
        });
        
        // Smooth scroll to top button
        const scrollBtn = $('<button class="scroll-to-top" title="Back to top"><i class="fa fa-chevron-up"></i></button>');
        $('body').append(scrollBtn);
        
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                scrollBtn.addClass('show');
            } else {
                scrollBtn.removeClass('show');
            }
        });
        
        scrollBtn.on('click', function() {
            $('html, body').animate({scrollTop: 0}, 600);
        });
    }
    
    // ===== ANIMATIONS =====
    function initAnimations() {
        // Fade in elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        // Observe all cards and panels
        document.querySelectorAll('.project-card, .stat-box, .panel, .task-item').forEach(el => {
            observer.observe(el);
        });
        
        // Counter animation for stat boxes
        $('.stat-box h3').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text());
            
            if (!isNaN(countTo)) {
                $({ countNum: 0 }).animate({
                    countNum: countTo
                }, {
                    duration: 2000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.countNum));
                    },
                    complete: function() {
                        $this.text(this.countNum);
                    }
                });
            }
        });
    }
    
    // ===== TOOLTIPS & POPOVERS =====
    function initTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            animation: true,
            delay: { show: 500, hide: 100 }
        });
        
        $('[data-toggle="popover"]').popover({
            trigger: 'hover',
            animation: true
        });
    }
    
    // ===== MODAL ENHANCEMENTS =====
    function initModals() {
        // Add fade animation to modals
        $('.modal').addClass('fade-scale');
        
        // Close modal on outside click
        $(document).on('click', '.modal-backdrop', function() {
            $('.modal').modal('hide');
        });
    }
    
    // ===== FORM VALIDATION =====
    function initFormValidation() {
        // Enhanced form validation
        $('form[data-validate="true"]').on('submit', function(e) {
            let valid = true;
            const $form = $(this);
            
            $form.find('[required]').each(function() {
                const $field = $(this);
                const $group = $field.closest('.form-group');
                
                if ($field.val() === '') {
                    $group.addClass('has-error shake');
                    setTimeout(() => $group.removeClass('shake'), 500);
                    valid = false;
                } else {
                    $group.removeClass('has-error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Please fill in all required fields', 'warning');
                return false;
            }
        });
        
        // Real-time validation
        $('input[required], textarea[required], select[required]').on('blur', function() {
            const $field = $(this);
            const $group = $field.closest('.form-group');
            
            if ($field.val() === '') {
                $group.addClass('has-error');
            } else {
                $group.removeClass('has-error');
            }
        });
    }
    
    // ===== SEARCH FEATURES =====
    function initSearchFeatures() {
        // Real-time search with highlight
        $('.search-input').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            const $searchable = $('.searchable');
            
            $searchable.each(function() {
                const $item = $(this);
                const text = $item.text().toLowerCase();
                
                if (text.indexOf(value) > -1) {
                    $item.show().addClass('search-highlight');
                    setTimeout(() => $item.removeClass('search-highlight'), 300);
                } else {
                    $item.hide();
                }
            });
            
            // Show no results message
            if ($searchable.filter(':visible').length === 0) {
                if ($('.no-results').length === 0) {
                    $searchable.parent().append('<div class="no-results alert alert-info">No results found</div>');
                }
            } else {
                $('.no-results').remove();
            }
        });
        
        // Clear search
        $('.search-clear').on('click', function() {
            $('.search-input').val('').trigger('keyup');
        });
    }
    
    // ===== NOTIFICATIONS =====
    function initNotifications() {
        // Auto-hide alerts
        $('.alert').each(function() {
            const $alert = $(this);
            setTimeout(() => {
                $alert.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        });
    }
    
    // Show notification function
    window.showNotification = function(message, type = 'info') {
        const notification = $(`
            <div class="notification notification-${type}">
                <i class="fa fa-${getIconForType(type)}"></i>
                <span>${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => notification.addClass('show'), 100);
        
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 4000);
        
        notification.find('.close-notification').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        });
    };
    
    function getIconForType(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // ===== DASHBOARD CHARTS =====
    function initDashboardCharts() {
        // Add loading animation to charts
        $('.chart-container canvas').each(function() {
            $(this).wrap('<div class="chart-wrapper"></div>');
            $(this).before('<div class="chart-loading"><div class="spinner"></div></div>');
            
            setTimeout(() => {
                $(this).siblings('.chart-loading').fadeOut();
            }, 1000);
        });
    }
    
    // ===== INTERACTIVE ELEMENTS =====
    function initInteractiveElements() {
        // Confirmation dialogs with animation
        $('.delete-confirm').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const href = $btn.attr('href');
            
            showConfirmDialog(
                'Are you sure?',
                'This action cannot be undone!',
                function() {
                    window.location.href = href;
                }
            );
        });
        
        // Progress bar animations
        $('.progress-bar').each(function() {
            const $bar = $(this);
            const width = $bar.attr('style').match(/width:\s*(\d+)%/);
            if (width) {
                $bar.css('width', '0%');
                setTimeout(() => {
                    $bar.css('width', width[1] + '%');
                }, 500);
            }
        });
        
        // Interactive stat boxes
        $('.stat-box').on('mouseenter', function() {
            $(this).find('.stat-icon').addClass('bounce');
        }).on('mouseleave', function() {
            $(this).find('.stat-icon').removeClass('bounce');
        });
        
        // Badge hover effects
        $('.badge-status, .badge-priority').hover(
            function() { $(this).addClass('pulse-subtle'); },
            function() { $(this).removeClass('pulse-subtle'); }
        );
        
        // Card flip on double click (optional feature)
        let clickCount = 0;
        let clickTimer = null;
        
        $('.project-card').on('click', function() {
            clickCount++;
            if (clickCount === 1) {
                clickTimer = setTimeout(() => {
                    clickCount = 0;
                }, 300);
            } else if (clickCount === 2) {
                clearTimeout(clickTimer);
                clickCount = 0;
                $(this).toggleClass('flipped');
            }
        });
    }
    
    // ===== CONFIRM DIALOG =====
    function showConfirmDialog(title, message, onConfirm) {
        const dialog = $(`
            <div class="custom-modal-overlay">
                <div class="custom-modal">
                    <div class="custom-modal-header">
                        <h4>${title}</h4>
                    </div>
                    <div class="custom-modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="custom-modal-footer">
                        <button class="btn btn-default modal-cancel">Cancel</button>
                        <button class="btn btn-danger modal-confirm">Confirm</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(dialog);
        setTimeout(() => dialog.addClass('show'), 10);
        
        dialog.find('.modal-confirm').on('click', function() {
            dialog.removeClass('show');
            setTimeout(() => dialog.remove(), 300);
            if (onConfirm) onConfirm();
        });
        
        dialog.find('.modal-cancel, .custom-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                dialog.removeClass('show');
                setTimeout(() => dialog.remove(), 300);
            }
        });
    }
    
    // ===== AJAX FORM SUBMISSION =====
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<span class="loading"></span> Processing...');
        
        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else if (response.reload) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ===== CHARACTER COUNTER =====
    $('.char-counter').each(function() {
        const $field = $(this);
        const maxLength = $field.attr('maxlength');
        const counterId = $field.attr('id') + '-counter';
        
        if ($('#' + counterId).length === 0) {
            $field.after(`<small id="${counterId}" class="char-count">0/${maxLength}</small>`);
        }
        
        $field.on('keyup', function() {
            const length = $(this).val().length;
            $(`#${counterId}`).text(`${length}/${maxLength}`);
            
            if (length > maxLength * 0.9) {
                $(`#${counterId}`).addClass('text-danger');
            } else {
                $(`#${counterId}`).removeClass('text-danger');
            }
        });
    });
    
    // ===== EXPORT FUNCTIONALITY =====
    $('.btn-export-csv').on('click', function() {
        const tableId = $(this).data('table');
        exportTableToCSV(tableId);
    });
    
    function exportTableToCSV(tableId) {
        const csv = [];
        const rows = document.querySelectorAll('#' + tableId + ' tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            
            csv.push(row.join(','));
        }
        
        downloadCSV(csv.join('\n'), 'export-' + Date.now() + '.csv');
    }
    
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // ===== PRINT FUNCTIONALITY =====
    $('.btn-print').on('click', function() {
        window.print();
    });
    
    // ===== KEYBOARD SHORTCUTS =====
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K for search focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $('.search-input').focus();
        }
        
        // ESC to clear search or close modals
        if (e.key === 'Escape') {
            $('.search-input').val('').trigger('keyup');
            $('.modal').modal('hide');
            $('.custom-modal-overlay').click();
        }
    });
    
    // ===== DRAG AND DROP (Optional for future use) =====
    if (typeof Sortable !== 'undefined') {
        $('.sortable-list').each(function() {
            new Sortable(this, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost'
            });
        });
    }
    
    // ===== LAZY LOADING IMAGES =====
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // ===== DYNAMIC THEME SWITCHING (Optional) =====
    const themeToggle = $('<button class="theme-toggle" title="Toggle theme"><i class="fa fa-moon-o"></i></button>');
    $('body').append(themeToggle);
    
    themeToggle.on('click', function() {
        $('body').toggleClass('dark-mode');
        const icon = $('body').hasClass('dark-mode') ? 'sun-o' : 'moon-o';
        $(this).find('i').attr('class', 'fa fa-' + icon);
        
        // Save preference
        localStorage.setItem('theme', $('body').hasClass('dark-mode') ? 'dark' : 'light');
    });
    
    // Load saved theme
    if (localStorage.getItem('theme') === 'dark') {
        $('body').addClass('dark-mode');
        themeToggle.find('i').attr('class', 'fa fa-sun-o');
    }
    
    // ===== PERFORMANCE MONITORING =====
    console.log('%c🚀 Project Management System Loaded', 'color: #6366f1; font-size: 14px; font-weight: bold;');
    console.log('%cAll interactive features initialized successfully!', 'color: #10b981; font-size: 12px;');
    
    // Track page load time
    if (window.performance) {
        const loadTime = window.performance.timing.domContentLoadedEventEnd - window.performance.timing.navigationStart;
        console.log(`%c⚡ Page loaded in ${loadTime}ms`, 'color: #f59e0b; font-size: 12px;');
    }
});

// ===== UTILITY FUNCTIONS =====

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Get time ago
function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return interval + ' ' + unit + (interval === 1 ? '' : 's') + ' ago';
        }
    }
    return 'just now';
}
