<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: view/admin_dashboard.php");
            break;
        case 'Doctor':
            header("Location: doctors_view/dashboard.php");
            break;
        case 'Receptionist':
            header("Location: reception_view/index.php");
            break;
        case 'Nurse':
            header("Location: nurse_view/dashboard.php");
            break;
        case 'Pharmacist':
            header("Location: pharmacy_view/dashboard.php");
            break;
        case 'LabTechnician':
        case 'laboratory':
            header("Location: laboratory_view/dashboard.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GM Hospital Management System</title>
    <!-- SweetAlert2 for premium popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1f6b4a;
            --primary-dark: #144d34;
            --accent: #2a8c62;
            --text-dark: #1a202c;
            --text-gray: #718096;
            --bg-gradient: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
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
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: 
                radial-gradient(circle at 10% 10%, rgba(31, 107, 74, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(20, 77, 52, 0.08) 0%, transparent 40%),
                linear-gradient(to right, #05445E 33.3%);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(31, 107, 74, 0.1) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.6;
            pointer-events: none;
            z-index: 1;
        }

        .login-container {
            width: 95%;
            max-width: 1200px;
            min-height: 600px;
            background: white;
            border-radius: 32px;
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.15),
                0 0 80px rgba(31, 107, 74, 0.1);
            display: flex;
            margin: 20px;
            position: relative;
            z-index: 10;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: containerSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes containerSlideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-section {
            flex: 0.35;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            background: white;
            z-index: 20;
            padding-top: 20px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-top: 0;
            margin-bottom: 0;
            letter-spacing: -1px;
        }

        .form-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            padding: 5px;
            background: white;
            box-shadow: 0 10px 25px rgba(31, 107, 74, 0.3);
            object-fit: contain;
        }
        
        .visual-slider {
            flex: 0.65;
            position: relative;
            overflow: hidden;
            background: white;
        }

        .visual-track {
            display: flex;
            width: 200%;
            height: 100%;
            transition: transform 0.6s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .image-section, .diagram-section {
            width: 50%;
            height: 100%;
            flex: none;
        }

        .image-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .diagram-section {
            background: #fcfdfe;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .slider-control {
            position: absolute;
            bottom: 40px;
            right: 40px;
            z-index: 30;
            display: flex;
            gap: 15px;
        }

        .slider-btn {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s;
            color: var(--primary);
            font-size: 14px;
        }

        .slider-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(31, 107, 74, 0.3);
        }

        .slider-btn i {
            pointer-events: none;
        }

        .slide-indicator {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 30;
        }

        .dot {
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transition: all 0.3s;
        }

        .dot.active {
            width: 24px;
            border-radius: 10px;
            background: white;
        }
        
        .diagram-section {
            flex: 1;
            background: #fcfdfe;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .diagram-container {
            position: relative;
            width: 500px;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            min-width: 500px;
            min-height: 500px;
        }

        .diagram-center {
            width: 260px !important;
            height: 260px !important;
            min-width: 260px !important;
            min-height: 260px !important;
            max-width: 260px !important;
            max-height: 260px !important;
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            flex-direction: column;
            background: linear-gradient(135deg, #12c2c9, #0aa2a9);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            font-weight: 600;
            color: white;
            z-index: 5;
            font-size: 16px;
            line-height: 1.4;
            flex-shrink: 0 !important;
            box-sizing: border-box;
            border: none;
            padding: 0;
            margin: 0;
            aspect-ratio: 1 / 1;
        }

        .diagram-node {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
        }

        .node-icon {
            width: 54px;
            height: 54px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--primary);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            margin-bottom: 8px;
            border: 2px solid #f8fafc;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 2;
        }

        .node-label {
            font-size: 10px;
            font-weight: 700;
            color: #4A5568;
            text-transform: uppercase;
            text-align: center;
            width: 90px;
            line-height: 1.2;
            letter-spacing: 0.5px;
        }

        .diagram-node:hover .node-icon {
            background: var(--primary);
            color: white;
            transform: scale(1.15) translateY(-5px);
            box-shadow: 0 15px 30px rgba(31, 107, 74, 0.3);
            border-color: var(--primary);
        }

        /* Connecting Lines Visualization */
        .svg-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .diagram-line {
            stroke: var(--primary);
            stroke-width: 1.5;
            stroke-dasharray: 4;
            opacity: 0.15;
            filter: blur(0.5px);
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1.2s ease;
        }

        .image-section:hover img {
            transform: scale(1.1);
        }

        .image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 40px;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            color: white;
            text-align: left;
        }

        .top-heading {
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            color: white;
            text-align: left;
            z-index: 5;
        }

        .image-overlay h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .image-overlay p {
            font-size: 16px;
            opacity: 0.95;
            line-height: 1.6;
            font-weight: 500;
            color: #E6FAFA;
        }

        .status-badge {
            position: absolute;
            top: 30px;
            right: 40px;
            background: rgba(31, 107, 74, 0.2);
            backdrop-filter: blur(12px);
            padding: 10px 22px;
            border-radius: 100px;
            color: white;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 10px #4ade80;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .header {
            margin-bottom: 25px;
            text-align: center;
        }

        .header img {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(31, 107, 74, 0.2);
            margin: 15px 0;
        }

        .header-logo {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            padding: 5px;
            background: white;
            box-shadow: 0 10px 25px rgba(31, 107, 74, 0.3);
            margin: 15px auto;
            display: block;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -1px;
        }
        
        .header p {
            color: var(--text-gray);
            font-size: 15px;
        }
        

        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            font-size: 18px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 16px 16px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            color: var(--text-dark);
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-gray);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(31, 107, 74, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(31, 107, 74, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .footer-links {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: var(--text-gray);
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .visual-slider { display: none; }
            .login-container { max-width: 500px; }
            .form-section { flex: 1; padding: 40px; }
        }

        /* Loading Spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="form-section">
            <div class="logo-container">
                <img src="assets/images/gm_logoo.png" alt="GM Hospital Logo" class="form-logo">
                <h1 class="logo-title">Hospital</h1>
            </div>
            <div class="header">
                <p>Welcome back! Please login to your account.</p>
            </div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="branch">Branch</label>
                    <div class="input-group">
                        <select id="branch" name="branch" required style="cursor: pointer;">
                            <option value="nagarabhavi" selected>Nagarabhavi</option>
                            <option value="basaveshwaranagar">Basaveshwaranagar</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username or ID</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" required placeholder="Enter your ID or username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="showComingSoon(); return false;">Forgot Password?</a>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span>Login to Portal</span>
                    <div class="spinner" id="btnSpinner"></div>
                </button>
            </form>

            <div class="footer-links">
                <p>Protected by SecureGate HMS © 2026</p>
                <p><a href="index.php">Back to Landing Page</a></p>
            </div>
        </div>

        <div class="visual-slider">
            <div class="visual-track" id="visualTrack">
                <!-- Slide 1: Image -->
                <div class="image-section">
                    <img src="assets/images/GM_login.jpg" alt="Hospital Professional" onerror="this.src='https://images.unsplash.com/photo-1631217818242-27ae79145888?auto=format&fit=crop&q=80&w=1000'">
                    
                    <div class="top-heading">
                        <h3 style="font-size: 32px; margin-bottom: 8px; letter-spacing: -0.5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Precision Medicine, Personalized Care.</h3>
                    </div>
                    
                    <div class="image-overlay">
                        <p>Welcome to the next generation of healthcare excellence.<br>Your dedication, powered by our intelligence.</p>
                    </div>
                </div>

                <!-- Slide 2: Diagram -->
                <div class="diagram-section">
                    <div class="diagram-container">
                        <!-- SVG Connecting Lines -->
                        <svg class="svg-lines">
                            <!-- Registration (top) -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="50%" y2="6%"></line>
                            <!-- Help Desk -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="76%" y2="14%"></line>
                            <!-- Billing -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="92%" y2="36%"></line>
                            <!-- Radiology -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="92%" y2="64%"></line>
                            <!-- OPD / IPD -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="76%" y2="86%"></line>
                            <!-- Pathology (bottom) -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="50%" y2="94%"></line>
                            <!-- Appointment -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="24%" y2="86%"></line>
                            <!-- Claims -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="8%" y2="64%"></line>
                            <!-- EMR Record -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="8%" y2="36%"></line>
                            <!-- Consultant -->
                            <line class="diagram-line" x1="50%" y1="50%" x2="24%" y2="14%"></line>
                        </svg>

                        <div class="diagram-center">
                            GM HOSPITAL<br>MANAGEMENT<br>PORTAL
                        </div>
                        
                        <div class="diagram-node" style="top: 6%; left: 50%; transform: translate(-50%, -50%); animation-delay: 0s;">
                            <div class="node-icon"><i class="fa-solid fa-user-plus"></i></div>
                            <div class="node-label">Registration</div>
                        </div>
                        <div class="diagram-node" style="top: 14%; left: 76%; transform: translate(-50%, -50%); animation-delay: 0.4s;">
                            <div class="node-icon"><i class="fa-solid fa-headset"></i></div>
                            <div class="node-label">Help Desk</div>
                        </div>
                        <div class="diagram-node" style="top: 36%; left: 92%; transform: translate(-50%, -50%); animation-delay: 0.8s;">
                            <div class="node-icon"><i class="fa-solid fa-file-invoice"></i></div>
                            <div class="node-label">Billing</div>
                        </div>
                        <div class="diagram-node" style="top: 64%; left: 92%; transform: translate(-50%, -50%); animation-delay: 1.2s;">
                            <div class="node-icon"><i class="fa-solid fa-x-ray"></i></div>
                            <div class="node-label">Radiology</div>
                        </div>
                        <div class="diagram-node" style="top: 86%; left: 76%; transform: translate(-50%, -50%); animation-delay: 1.6s;">
                            <div class="node-icon"><i class="fa-solid fa-bed-pulse"></i></div>
                            <div class="node-label">OPD / IPD</div>
                        </div>
                        <div class="diagram-node" style="top: 94%; left: 50%; transform: translate(-50%, -50%); animation-delay: 2s;">
                            <div class="node-icon"><i class="fa-solid fa-flask-vial"></i></div>
                            <div class="node-label">Pathology</div>
                        </div>
                        <div class="diagram-node" style="top: 86%; left: 24%; transform: translate(-50%, -50%); animation-delay: 2.4s;">
                            <div class="node-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <div class="node-label">Appointment</div>
                        </div>
                        <div class="diagram-node" style="top: 64%; left: 8%; transform: translate(-50%, -50%); animation-delay: 2.8s;">
                            <div class="node-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div class="node-label">Claims</div>
                        </div>
                        <div class="diagram-node" style="top: 36%; left: 8%; transform: translate(-50%, -50%); animation-delay: 3.2s;">
                            <div class="node-icon"><i class="fa-solid fa-file-waveform"></i></div>
                            <div class="node-label">EMR Record</div>
                        </div>
                        <div class="diagram-node" style="top: 14%; left: 24%; transform: translate(-50%, -50%); animation-delay: 3.6s;">
                            <div class="node-icon"><i class="fa-solid fa-user-doctor"></i></div>
                            <div class="node-label">Consultant</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slider Controls -->
            <div class="slide-indicator">
                <div class="dot active" id="dot1"></div>
                <div class="dot" id="dot2"></div>
            </div>

            <div class="slider-control">
                <button class="slider-btn" id="prevBtn" style="visibility:hidden">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-btn" id="nextBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password Toggle Function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Slider Logic
        const track = document.getElementById('visualTrack');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const dot1 = document.getElementById('dot1');
        const dot2 = document.getElementById('dot2');
        let currentSlide = 0;

        function updateSlider() {
            track.style.transform = `translateX(-${currentSlide * 50}%)`;
            if (currentSlide === 0) {
                prevBtn.style.visibility = 'hidden';
                nextBtn.style.visibility = 'visible';
                dot1.classList.add('active');
                dot2.classList.remove('active');
            } else {
                prevBtn.style.visibility = 'visible';
                nextBtn.style.visibility = 'hidden';
                dot1.classList.remove('active');
                dot2.classList.add('active');
            }
        }

        nextBtn.addEventListener('click', () => {
            currentSlide = 1;
            updateSlider();
        });

        prevBtn.addEventListener('click', () => {
            currentSlide = 0;
            updateSlider();
        });

        // Form Submission Logic
        function showComingSoon() {
            Swal.fire({
                icon: 'info',
                title: 'Coming Soon!',
                text: 'Password reset feature will be available soon.',
                confirmButtonColor: '#1f6b4a',
                background: '#fff',
                iconColor: '#1f6b4a'
            });
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('btnSpinner');
            const btnText = btn.querySelector('span');
            
            btn.disabled = true;
            spinner.style.display = 'block';
            btnText.innerText = 'Verifying...';
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
         fetch('api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Hospital-Branch': document.getElementById('branch').value
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Welcome!',
                        text: `Logged in as ${result.role}`,
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#fff',
                        iconColor: '#1f6b4a'
                    }).then(() => {
                        // Store user ID in localStorage for frontend persistence
                        if (result.role === 'Doctor') {
                            localStorage.setItem('doctor_id', result.user.sl_no);
                        } else {
                            localStorage.setItem('staff_id', result.user.sl_no);
                        }

                        window.location.href = result.redirect_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: result.message || 'Invalid credentials',
                        confirmButtonColor: '#1f6b4a'
                    });
                    resetBtn();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Unable to connect to login service.',
                    confirmButtonColor: '#1f6b4a'
                });
                resetBtn();
            });

            function resetBtn() {
                btn.disabled = false;
                spinner.style.display = 'none';
                btnText.innerText = 'Login to Portal';
            }
        });
    </script>
</body>
</html>