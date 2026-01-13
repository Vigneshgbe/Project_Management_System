<style>
    .footer {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-top: none !important;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15) !important;
        padding: 50px 0 30px 0 !important;
        margin-top: 80px !important;
        animation: slideUp 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .footer::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 4px !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
    }
    
    .footer::after {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        left: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.03) 0%, transparent 70%) !important;
        animation: rotate 30s linear infinite !important;
        pointer-events: none !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .footer .container {
        position: relative !important;
        z-index: 1 !important;
    }
    
    .footer-brand {
        margin-bottom: 25px !important;
    }
    
    .footer-brand h3 {
        margin: 0 0 10px 0 !important;
        font-weight: 800 !important;
        font-size: 26px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .footer-brand p {
        margin: 0 !important;
        color: #64748b !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        line-height: 1.6 !important;
        max-width: 500px !important;
        margin: 0 auto !important;
    }
    
    .footer-divider {
        height: 2px !important;
        background: linear-gradient(90deg, transparent 0%, rgba(102, 126, 234, 0.2) 50%, transparent 100%) !important;
        margin: 30px 0 25px 0 !important;
    }
    
    .footer-content {
        text-align: center !important;
    }
    
    .footer-links {
        margin-bottom: 25px !important;
        display: flex !important;
        justify-content: center !important;
        flex-wrap: wrap !important;
        gap: 10px 30px !important;
    }
    
    .footer-links a {
        color: #374151 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        position: relative !important;
        padding: 5px 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .footer-links a i {
        font-size: 16px !important;
        transition: transform 0.3s ease !important;
    }
    
    .footer-links a::after {
        content: '' !important;
        position: absolute !important;
        width: 0 !important;
        height: 2px !important;
        bottom: 0 !important;
        left: 50% !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        transition: all 0.3s ease !important;
        transform: translateX(-50%) !important;
        border-radius: 2px !important;
    }
    
    .footer-links a:hover {
        color: #667eea !important;
    }
    
    .footer-links a:hover::after {
        width: 100% !important;
    }
    
    .footer-links a:hover i {
        transform: translateY(-2px) !important;
    }
    
    .footer-social {
        margin-bottom: 30px !important;
        display: flex !important;
        justify-content: center !important;
        gap: 15px !important;
    }
    
    .footer-social a {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 45px !important;
        height: 45px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        color: #667eea !important;
        border-radius: 50% !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
        font-size: 18px !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .footer-social a::before {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 0 !important;
        height: 0 !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        transition: all 0.4s ease !important;
        transform: translate(-50%, -50%) !important;
    }
    
    .footer-social a:hover::before {
        width: 100% !important;
        height: 100% !important;
    }
    
    .footer-social a i {
        position: relative !important;
        z-index: 1 !important;
        transition: all 0.3s ease !important;
    }
    
    .footer-social a:hover {
        transform: translateY(-5px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35) !important;
    }
    
    .footer-social a:hover i {
        color: white !important;
        transform: scale(1.1) !important;
    }
    
    .footer-bottom {
        padding-top: 25px !important;
        border-top: 1px solid rgba(102, 126, 234, 0.1) !important;
    }
    
    .footer-bottom p {
        margin: 0 !important;
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        letter-spacing: 0.3px !important;
    }
    
    .footer-bottom p i {
        color: #ef4444 !important;
        margin: 0 5px !important;
        animation: heartbeat 1.5s ease infinite !important;
    }
    
    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        10%, 30% { transform: scale(1.1); }
        20%, 40% { transform: scale(1); }
    }
    
    .footer-info {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 20px !important;
        margin-top: 15px !important;
        flex-wrap: wrap !important;
    }
    
    .footer-info span {
        color: #64748b !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    
    .footer-info span i {
        color: #667eea !important;
        font-size: 14px !important;
    }
    
    /* SCROLL TO TOP BUTTON */
    .scroll-top-btn {
        position: fixed !important;
        bottom: 30px !important;
        right: 30px !important;
        width: 55px !important;
        height: 55px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
        font-size: 22px !important;
        cursor: pointer !important;
        opacity: 0 !important;
        transform: translateY(100px) scale(0.8) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        z-index: 99998 !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .scroll-top-btn::before {
        content: '' !important;
        position: absolute !important;
        top: -5px !important;
        left: -5px !important;
        right: -5px !important;
        bottom: -5px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        z-index: -1 !important;
    }
    
    .scroll-top-btn.show {
        opacity: 1 !important;
        transform: translateY(0) scale(1) !important;
    }
    
    .scroll-top-btn:hover {
        transform: translateY(-8px) scale(1.05) !important;
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5) !important;
    }
    
    .scroll-top-btn:hover::before {
        opacity: 0.5 !important;
        animation: pulse-ring 1.5s ease infinite !important;
    }
    
    @keyframes pulse-ring {
        0% { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(1.3); opacity: 0; }
    }
    
    .scroll-top-btn:active {
        transform: translateY(-5px) scale(0.95) !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .footer {
            padding: 40px 15px 25px 15px !important;
            margin-top: 60px !important;
        }
        
        .footer-brand h3 {
            font-size: 22px !important;
        }
        
        .footer-brand p {
            font-size: 13px !important;
        }
        
        .footer-links {
            gap: 8px 20px !important;
        }
        
        .footer-links a {
            font-size: 13px !important;
        }
        
        .footer-social {
            gap: 12px !important;
        }
        
        .footer-social a {
            width: 42px !important;
            height: 42px !important;
            font-size: 16px !important;
        }
        
        .footer-info {
            gap: 15px !important;
        }
        
        .scroll-top-btn {
            width: 50px !important;
            height: 50px !important;
            bottom: 25px !important;
            right: 25px !important;
            font-size: 20px !important;
        }
    }
    
    @media (max-width: 480px) {
        .footer {
            padding: 35px 10px 20px 10px !important;
            margin-top: 50px !important;
        }
        
        .footer-brand h3 {
            font-size: 20px !important;
        }
        
        .footer-links {
            flex-direction: column !important;
            gap: 12px !important;
        }
        
        .footer-social {
            gap: 10px !important;
        }
        
        .footer-social a {
            width: 40px !important;
            height: 40px !important;
            font-size: 15px !important;
        }
        
        .scroll-top-btn {
            width: 45px !important;
            height: 45px !important;
            bottom: 20px !important;
            right: 20px !important;
            font-size: 18px !important;
        }
    }
</style>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            
            <div class="footer-bottom">
                <p>
                    &copy; <?php echo date('Y'); ?> Project Management System. All rights reserved. 
                    Made with <i class="fa fa-heart"></i> by Your Team
                </p>
                <div class="footer-info">
                    <span><i class="fa fa-shield"></i> Secure</span>
                    <span><i class="fa fa-bolt"></i> Fast</span>
                    <span><i class="fa fa-check-circle"></i> Reliable</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<button class="scroll-top-btn" title="Back to top" aria-label="Scroll to top">
    <i class="fa fa-chevron-up"></i>
</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        // SCROLL TO TOP BUTTON
        $(window).scroll(function() {
            if ($(this).scrollTop() > 400) {
                $('.scroll-top-btn').addClass('show');
            } else {
                $('.scroll-top-btn').removeClass('show');
            }
        });
        
        $('.scroll-top-btn').on('click', function() {
            $('html, body').animate({
                scrollTop: 0
            }, 800, 'swing');
        });
        
        // FOOTER ANIMATION ON SCROLL
        function checkFooterVisibility() {
            var footerTop = $('.footer').offset().top;
            var windowBottom = $(window).scrollTop() + $(window).height();
            
            if (windowBottom > footerTop + 100) {
                $('.footer').addClass('footer-visible');
            }
        }
        
        $(window).on('scroll', checkFooterVisibility);
        checkFooterVisibility();
        
        // SMOOTH ANIMATIONS FOR FOOTER LINKS
        $('.footer-links a, .footer-social a').hover(
            function() {
                $(this).css('transition', 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)');
            }
        );
    });
</script>

</body>
</html>