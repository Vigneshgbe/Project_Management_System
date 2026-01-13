<style>
    .footer {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-top: none !important;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15) !important;
        padding: 30px 0 !important;
        margin-top: 60px !important;
        position: relative !important;
        overflow: hidden !important;
        animation: slideUp 0.6s ease !important;
    }
    
    .footer::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 3px !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%) !important;
        background-size: 200% 100% !important;
        animation: gradientShift 3s ease infinite !important;
    }
    
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .footer .container {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 20px !important;
        padding: 0 20px !important;
    }
    
    .footer-copyright {
        color: #64748b !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }
    
    .footer-links {
        display: flex !important;
        gap: 30px !important;
        flex-wrap: wrap !important;
    }
    
    .footer-links a {
        color: #64748b !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        position: relative !important;
    }
    
    .footer-links a::after {
        content: '' !important;
        position: absolute !important;
        width: 0 !important;
        height: 2px !important;
        bottom: -3px !important;
        left: 0 !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        transition: width 0.3s ease !important;
    }
    
    .footer-links a:hover {
        color: #667eea !important;
    }
    
    .footer-links a:hover::after {
        width: 100% !important;
    }
    
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
        font-size: 20px !important;
        cursor: pointer !important;
        opacity: 0 !important;
        transform: translateY(100px) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        z-index: 99999 !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .scroll-top-btn.show {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .scroll-top-btn:hover {
        transform: translateY(-8px) scale(1.05) !important;
        box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6) !important;
    }
    
    .scroll-top-btn:active {
        transform: translateY(-5px) scale(0.98) !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .footer {
            padding: 25px 0 !important;
            margin-top: 40px !important;
        }
        
        .footer .container {
            flex-direction: column !important;
            text-align: center !important;
            gap: 15px !important;
        }
        
        .footer-links {
            justify-content: center !important;
            gap: 20px !important;
        }
        
        .scroll-top-btn {
            bottom: 20px !important;
            right: 20px !important;
            width: 50px !important;
            height: 50px !important;
        }
    }
    
    @media (max-width: 480px) {
        .footer {
            padding: 20px 0 !important;
        }
        
        .footer-copyright {
            font-size: 13px !important;
        }
        
        .footer-links {
            flex-direction: column !important;
            gap: 12px !important;
        }
        
        .footer-links a {
            font-size: 13px !important;
        }
        
        .scroll-top-btn {
            width: 45px !important;
            height: 45px !important;
            font-size: 18px !important;
        }
    }
</style>

<footer class="footer">
    <div class="container">
        <p class="footer-copyright">© <?php echo date('Y'); ?> Project Management System. All rights reserved.</p>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Cookie Policy</a>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        // SCROLL TO TOP BUTTON
        $('<button class="scroll-top-btn" title="Back to top" aria-label="Back to top"><i class="fa fa-chevron-up"></i></button>').appendTo('body');
        
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 400) {
                $('.scroll-top-btn').addClass('show');
            } else {
                $('.scroll-top-btn').removeClass('show');
            }
        });
        
        $('.scroll-top-btn').on('click', function() {
            $('html, body').animate({scrollTop: 0}, 800, 'swing');
        });
    });
</script>

</body>
</html>