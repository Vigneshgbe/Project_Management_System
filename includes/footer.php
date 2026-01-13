<style>
    .footer {
        background: white;
        border-top: 3px solid transparent;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-image-slice: 1;
        box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.1);
        padding: 25px 0;
        margin-top: 60px;
        animation: slideUp 0.5s ease;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .footer .container {
        text-align: center;
    }
    
    .footer p {
        margin: 0;
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
    }
    
    .footer-links {
        margin-top: 15px;
    }
    
    .footer-links a {
        color: #667eea;
        text-decoration: none;
        margin: 0 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .footer-links a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -3px;
        left: 50%;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    
    .footer-links a:hover::after {
        width: 100%;
    }
    
    .footer-links a:hover {
        color: #764ba2;
    }
    
    .footer-social {
        margin-top: 15px;
    }
    
    .footer-social a {
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        color: #667eea;
        border-radius: 50%;
        margin: 0 8px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .footer-social a:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
</style>

<footer class="footer">
    <div class="container">
        <div class="footer-social">
            <a href="#" title="Facebook"><i class="fa fa-facebook"></i></a>
            <a href="#" title="Twitter"><i class="fa fa-twitter"></i></a>
            <a href="#" title="LinkedIn"><i class="fa fa-linkedin"></i></a>
            <a href="#" title="GitHub"><i class="fa fa-github"></i></a>
        </div>
        <div class="footer-links">
            <a href="/dashboard.php">Dashboard</a>
            <a href="/projects.php">Projects</a>
            <a href="/tasks.php">Tasks</a>
            <a href="/profile.php">Profile</a>
        </div>
        <p style="margin-top: 20px;">&copy; <?php echo date('Y'); ?> Project Management System. All rights reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        // Smooth scroll to top
        $('<button class="scroll-top-btn" title="Back to top"><i class="fa fa-chevron-up"></i></button>').appendTo('body');
        
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.scroll-top-btn').addClass('show');
            } else {
                $('.scroll-top-btn').removeClass('show');
            }
        });
        
        $('.scroll-top-btn').on('click', function() {
            $('html, body').animate({scrollTop: 0}, 600);
        });
    });
</script>

<style>
    .scroll-top-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-size: 20px;
        cursor: pointer;
        opacity: 0;
        transform: translateY(100px);
        transition: all 0.3s ease;
        z-index: 9999;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .scroll-top-btn.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .scroll-top-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
    }
</style>

</body>
</html>