<?php
session_start();

// Redirect to appropriate dashboard if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    switch($_SESSION['user_type']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Administration System - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .logo span {
            font-size: 32px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 50px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0); }
            100% { transform: translateY(-100px); }
        }
        
        .hero-content {
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-text h1 {
            font-size: 56px;
            color: white;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-text p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
            padding: 15px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 15px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid white;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }
        
        .hero-image {
            position: relative;
        }
        
        .hero-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-card h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-item .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-item .label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Features Section */
        .features {
            padding: 100px 20px;
            background: #f8f9fa;
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 42px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .section-header p {
            font-size: 18px;
            color: #666;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* User Roles Section */
        .roles {
            padding: 100px 20px;
            background: white;
        }
        
        .roles-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        
        .role-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        }
        
        .role-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .role-card h3 {
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .role-card ul {
            list-style: none;
            margin-top: 20px;
        }
        
        .role-card li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .role-card li:last-child {
            border-bottom: none;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 20px 30px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section li {
            padding: 8px 0;
        }
        
        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-text h1 {
                font-size: 36px;
            }
            
            .hero-buttons {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <span>🎓</span>
                <span>EduManage</span>
            </div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#roles">User Roles</a>
                <a href="#about">About</a>
                <a href="login.php" class="btn-login">Login</a>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Transform Your School Management</h1>
                <p>A comprehensive platform to streamline administration, enhance communication, and improve educational outcomes.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary">Get Started</a>
                    <a href="#features" class="btn-secondary">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-card">
                    <h3>Quick Access</h3>
                    <p style="color: #666; margin-bottom: 20px;">Manage your institution efficiently</p>
                    <div class="stats">
                        <div class="stat-item">
                            <div class="number">1000+</div>
                            <div class="label">Students</div>
                        </div>
                        <div class="stat-item">
                            <div class="number">50+</div>
                            <div class="label">Teachers</div>
                        </div>
                        <div class="stat-item">
                            <div class="number">30+</div>
                            <div class="label">Classes</div>
                        </div>
                        <div class="stat-item">
                            <div class="number">24/7</div>
                            <div class="label">Access</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <h2>Powerful Features</h2>
                <p>Everything you need to run your school smoothly</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👨‍🎓</div>
                    <h3>Student Management</h3>
                    <p>Comprehensive student profiles, enrollment tracking, and academic records management.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3>Attendance System</h3>
                    <p>Real-time attendance tracking with detailed reports and analytics for parents and teachers.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Fee Management</h3>
                    <p>Automated fee collection, payment tracking, and financial reporting system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>Teacher Portal</h3>
                    <p>Dedicated teacher interface for class management, grading, and student communication.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Class Management</h3>
                    <p>Organize classes, subjects, and schedules efficiently with our intuitive system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics Dashboard</h3>
                    <p>Comprehensive insights and reports to make data-driven decisions.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- User Roles Section -->
    <section class="roles" id="roles">
        <div class="roles-container">
            <div class="section-header">
                <h2>Built For Everyone</h2>
                <p>Tailored experiences for each user type</p>
            </div>
            <div class="roles-grid">
                <div class="role-card">
                    <div class="role-icon">👨‍💼</div>
                    <h3>Administrators</h3>
                    <ul>
                        <li>✓ Complete System Control</li>
                        <li>✓ User Management</li>
                        <li>✓ Financial Overview</li>
                        <li>✓ Reports & Analytics</li>
                        <li>✓ System Configuration</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-icon">👨‍🏫</div>
                    <h3>Teachers</h3>
                    <ul>
                        <li>✓ Class Management</li>
                        <li>✓ Mark Attendance</li>
                        <li>✓ Grade Students</li>
                        <li>✓ View Class Records</li>
                        <li>✓ Communication Tools</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-icon">👨‍🎓</div>
                    <h3>Students</h3>
                    <ul>
                        <li>✓ View Profile</li>
                        <li>✓ Check Attendance</li>
                        <li>✓ Fee Status</li>
                        <li>✓ Academic Records</li>
                        <li>✓ Access Resources</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="footer-container">
            <div class="footer-section">
                <h3>🎓 EduManage</h3>
                <p style="color: rgba(255,255,255,0.7); line-height: 1.6;">
                    Comprehensive school administration system designed to simplify educational management and enhance learning outcomes.
                </p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#roles">User Roles</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li>📧 support@edumanage.com</li>
                    <li>📞 +1 (555) 123-4567</li>
                    <li>📍 123 Education St, City</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 EduManage School Administration System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
