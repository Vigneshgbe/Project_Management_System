<style>
    /* MODERN FOOTER DESIGN */
    .footer {
        background: white;
        border-top: 1px solid var(--border, #e2e8f0);
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
        padding: 32px 0;
        margin-top: 60px;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.4s ease;
    }
    
    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #6366f1);
        background-size: 200% 100%;
        animation: gradientFlow 3s ease-in-out infinite;
    }
    
    @keyframes gradientFlow {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .footer .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        padding: 0 20px;
    }
    
    .footer-copyright {
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .footer-copyright i {
        color: #6366f1;
        font-size: 16px;
    }
    
    .footer-links {
        display: flex;
        gap: 32px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .footer-links a {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        padding: 4px 0;
    }
    
    .footer-links a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 0;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        transition: width 0.3s ease;
        border-radius: 2px;
    }
    
    .footer-links a:hover {
        color: #6366f1;
        transform: translateY(-2px);
    }
    
    .footer-links a:hover::after {
        width: 100%;
    }
    
    /* SCROLL TO TOP BUTTON */
    .scroll-top-btn {
        position: fixed;
        bottom: 32px;
        right: 32px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        font-size: 20px;
        cursor: pointer;
        opacity: 0;
        transform: scale(0) translateY(20px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        outline: none;
    }
    
    .scroll-top-btn.show {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    
    .scroll-top-btn:hover {
        transform: scale(1.1) translateY(-4px);
        box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5);
    }
    
    .scroll-top-btn:active {
        transform: scale(0.95) translateY(-2px);
    }
    
    .scroll-top-btn i {
        transition: transform 0.3s ease;
    }
    
    .scroll-top-btn:hover i {
        transform: translateY(-2px);
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .footer {
            padding: 28px 0;
            margin-top: 48px;
        }
        
        .footer .container {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
        
        .footer-copyright {
            flex-direction: column;
            gap: 4px;
        }
        
        .footer-links {
            justify-content: center;
            gap: 24px;
        }
        
        .scroll-top-btn {
            bottom: 24px;
            right: 24px;
            width: 50px;
            height: 50px;
            font-size: 18px;
        }
    }
    
    @media (max-width: 480px) {
        .footer {
            padding: 24px 0;
            margin-top: 40px;
        }
        
        .footer-copyright {
            font-size: 13px;
        }
        
        .footer-links {
            flex-direction: column;
            gap: 16px;
        }
        
        .footer-links a {
            font-size: 13px;
        }
        
        .scroll-top-btn {
            bottom: 20px;
            right: 20px;
            width: 46px;
            height: 46px;
            font-size: 16px;
        }
    }
</style>

<footer class="footer">
    <div class="container">
        <p class="footer-copyright">
            <i class="fa fa-copyright"></i>
            <span><?php echo date('Y'); ?> Project Management System. All rights reserved.</span>
        </p>
        <div class="footer-links">
            <a href="#"><i class="fa fa-shield"></i> Privacy Policy</a>
            <a href="#"><i class="fa fa-file-text"></i> Terms of Service</a>
            <a href="#"><i class="fa fa-cookie-bite"></i> Cookies</a>
        </div>
    </div>
</footer>

<button class="scroll-top-btn" title="Back to top" aria-label="Scroll to top">
    <i class="fa fa-chevron-up"></i>
</button>

<script>
    $(document).ready(function() {
        // Smooth scroll to top
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 300) {
                $('.scroll-top-btn').addClass('show');
            } else {
                $('.scroll-top-btn').removeClass('show');
            }
        });
        
        $('.scroll-top-btn').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, 600, 'swing');
        });
        
        // Footer links smooth scroll (if they're anchor links)
        $('.footer-links a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if(target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 600);
            }
        });
    });
</script>

</body>
</html>