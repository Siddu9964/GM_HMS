<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GM Hospital Management System - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/lucide-static@0.321.0/font/lucide.css" rel="stylesheet">
    <link href="https://unpkg.com/lucide-static@0.321.0/font/lucide.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1f6b4a;
            --primary-dark: #144d34;
            --accent: #2a8c62;
            --text-dark: #1a202c;
            --text-gray: #718096;
            --bg-gradient: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            --admin-gradient: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            --receptionist-gradient: linear-gradient(135deg, #059669 0%, #047857 100%);
            --doctor-gradient: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        /* Disable scrolling completely */
        html {
            overflow: hidden;
            height: 100vh;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            height: 100vh;
            background: white;
            color: var(--text-dark);
            overflow: hidden;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
        }
        
        body::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        *::-webkit-scrollbar {
            display: none; /* Hide all scrollbars */
        }

        .logo-top-center {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
        }

        .logo-top-center img {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            padding: 8px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-container img {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            padding: 5px;
            background: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-container:hover img {
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
            flex: 1;
            justify-content: center;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .get-started-btn {
            background: white;
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(31, 107, 74, 0.2);
        }

        .get-started-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(31, 107, 74, 0.3);
        }

        .hero-section {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            background: var(--bg-gradient);
            position: relative;
            z-index: 1;
            padding: 120px 0 0;
            margin: 0;
            transition: background 1s ease;
        }

        .hero-section.admin {
            background: var(--admin-gradient);
        }

        .hero-section.receptionist {
            background: var(--receptionist-gradient);
        }

        .hero-section.doctor {
            background: var(--doctor-gradient);
        }

        .hero-container {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            gap: 60px;
            align-items: center;
            height: 100vh;
            padding: 0 40px;
        }

        .hero-left {
            flex: 0 0 60%;
            color: white;
        }

        .hero-right {
            flex: 0 0 40%;
            position: relative;
            height: 70vh;
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.8s ease;
        }

        .hero-image.active {
            opacity: 0.9;
        }

        .hero-image.error {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.9; }
            50% { opacity: 1; }
        }

        .hero-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(31, 107, 74, 0.3) 0%, rgba(20, 77, 52, 0.1) 100%);
            border-radius: 20px;
            z-index: 3;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 20% 30%, white 0%, transparent 1%),
                radial-gradient(circle at 80% 70%, white 0%, transparent 1%);
            background-size: 100px 100px;
            pointer-events: none;
        }

        .hero-content {
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .hero-content.active {
            opacity: 1;
        }

        .role-switcher {
            display: flex;
            gap: 8px;
            margin-bottom: 32px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 6px;
            border-radius: 16px;
            border: 0.5px solid rgba(255, 255, 255, 0.2);
        }

        .role-btn {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .role-btn:hover {
            color: white;
        }

        .role-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .role-btn i {
            font-size: 18px;
        }


        .hero-content h1 {
            font-size: 72px;
            font-weight: 800;
            margin-bottom: 24px;
            letter-spacing: -1px;
            line-height: 1.1;
            text-align: left;
        }

        .hero-content p {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 48px;
            font-weight: 400;
            text-align: left;
            letter-spacing: 0.5px;
        }

        .cta-group {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn {
            padding: 18px 40px;
            border-radius: 20px;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-right: 16px;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-5px);
        }

        .stats-grid {
            display: flex;
            gap: 30px;
            margin-top: 80px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 0.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 4px 3px;
            text-align: center;
            flex: 1;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 14px;
            margin-bottom: 2px;
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-item h2 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 0px;
            text-align: center;
        }

        .stat-item p {
            font-size: 9px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .nav-links {
                gap: 20px;
            }
            
            .nav-links a {
                font-size: 14px;
            }
            
            .logo-container img {
                width: 40px;
                height: 40px;
            }
            
            .get-started-btn {
                display: none;
            }
            
            .hero-container {
                flex-direction: column;
                gap: 40px;
            }
            
            .hero-left, .hero-right {
                flex: 1;
                width: 100%;
            }
            
            .hero-right {
                height: 300px;
            }
            
            .hero-content h1 { font-size: 40px; }
            .cta-group { flex-direction: column; }
            .stats-grid { flex-direction: column; gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="logo-top-center">
        <img src="assets/images/gm_logoo.png" alt="GM HMS Logo">
    </div>
    
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="hero-container">
            <div class="hero-left">
                <div class="hero-content" id="heroContent">
                    <h1 id="heroHeadline">Comprehensive Hospital Oversight</h1>
                    <p id="heroSubtext">Technology that advances care and respects human life.</p>
                    
                    <div class="cta-group">
                        <a href="login.php" class="btn btn-primary">
                            Enter Portal Login
                        </a>
                        <a href="#about" class="btn btn-outline" id="learnMoreBtn">
                            Learn More
                        </a>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="clock"></i>
                            </div>
                            <div class="stat-item">
                                <h2>24/7</h2>
                                <p>Emergency Support</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="user-check"></i>
                            </div>
                            <div class="stat-item">
                                <h2>100+</h2>
                                <p>Expert Doctors</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="shield-check"></i>
                            </div>
                            <div class="stat-item">
                                <h2>Secure</h2>
                                <p>Multi-Role Access</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <img id="heroImage" src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80" alt="Hospital management dashboard" class="hero-image active">
                <div class="hero-image-overlay"></div>
                <div class="role-indicator">
                    <div class="role-dot active" data-role="admin"></div>
                    <div class="role-dot" data-role="receptionist"></div>
                    <div class="role-dot" data-role="doctor"></div>
                </div>
            </div>
        </div>
    </section>
    
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();
        
        // Handle Learn More button click
        document.getElementById('learnMoreBtn').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Coming Soon');
        });
        
        // Role-based content configuration
        const roleConfig = {
            admin: {
                headline: 'Advanced Operations, Intelligently Managed',
                subtext: 'Technology that advances care and respects human life.',
                image: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80',
                alt: 'Hospital management dashboard',
                icon: 'shield'
            },
            receptionist: {
                headline: 'Seamless Patient Care, Intelligently Managed',
                subtext: 'Technology that advances care and respects human life.',
                image: 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1200&q=80',
                alt: 'Professional at digital front desk',
                icon: 'user'
            },
            doctor: {
                headline: 'Clinical Excellence, Intelligently Managed',
                subtext: 'Technology that advances care and respects human life.',
                image: 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=1200&q=80',
                alt: 'Doctor using digital interface',
                icon: 'stethoscope'
            }
        };
        
        let currentRole = 'admin';
        let autoRotateInterval;
        const roles = ['admin', 'receptionist', 'doctor'];
        
        // DOM elements
        const heroSection = document.querySelector('.hero-section');
        const heroContent = document.getElementById('heroContent');
        const heroHeadline = document.getElementById('heroHeadline');
        const heroSubtext = document.getElementById('heroSubtext');
        const heroImage = document.getElementById('heroImage');
        const roleButtons = document.querySelectorAll('.role-btn');
        const roleDots = document.querySelectorAll('.role-dot');
        
        // Update hero content based on role
        function updateHero(role) {
            const config = roleConfig[role];
            
            // Fade out content
            heroContent.classList.remove('active');
            
            // Fade out old image
            const oldImage = document.querySelector('.hero-image.active');
            if (oldImage && oldImage !== heroImage) {
                oldImage.classList.remove('active');
            }
            
            setTimeout(() => {
                // Update content
                heroHeadline.textContent = config.headline;
                heroSubtext.textContent = config.subtext;
                
                // Update background gradient
                heroSection.className = 'hero-section ' + role;
                
                // Update active button
                roleButtons.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.role === role);
                });
                
                // Update role dots
                roleDots.forEach((dot, index) => {
                    dot.classList.toggle('active', roles[index] === role);
                });
                
                // Fade in content
                heroContent.classList.add('active');
                
                // Handle image loading with error handling
                heroImage.onerror = function() {
                    this.classList.add('error');
                    this.alt = 'Image loading failed';
                    // Add fallback icon
                    this.innerHTML = `<i data-lucide="${config.icon}" style="font-size: 48px;"></i>`;
                    lucide.createIcons();
                };
                
                heroImage.onload = function() {
                    this.classList.remove('error');
                    this.classList.add('active');
                };
                
                heroImage.src = config.image;
                heroImage.alt = config.alt;
                
                // Reinitialize Lucide icons
                lucide.createIcons();
            }, 400);
        }
        
        // Auto-rotate through roles
        function startAutoRotate() {
            autoRotateInterval = setInterval(() => {
                const currentIndex = roles.indexOf(currentRole);
                const nextIndex = (currentIndex + 1) % roles.length;
                currentRole = roles[nextIndex];
                updateHero(currentRole);
            }, 5000);
        }
        
        // Stop auto-rotate
        function stopAutoRotate() {
            clearInterval(autoRotateInterval);
        }
        
        // Manual role selection
        roleButtons.forEach(button => {
            button.addEventListener('click', () => {
                stopAutoRotate();
                currentRole = button.dataset.role;
                updateHero(currentRole);
                startAutoRotate(); // Restart auto-rotate after manual selection
            });
        });
        
        // Role dot selection
        roleDots.forEach(dot => {
            dot.addEventListener('click', () => {
                stopAutoRotate();
                currentRole = dot.dataset.role;
                updateHero(currentRole);
                startAutoRotate(); // Restart auto-rotate after manual selection
            });
        });
        
        // Initialize
        updateHero(currentRole);
        startAutoRotate();
    </script>
</body>
</html>