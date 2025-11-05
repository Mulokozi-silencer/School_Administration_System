<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, user_type FROM users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            redirectToDashboard($user['user_type']);
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Invalid username or password';
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Administration System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
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
        
        .back-button {
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 10;
        }
        
        .back-button a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-button a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 450px;
            max-width: 90%;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .login-header .logo {
            font-size: 64px;
            margin-bottom: 10px;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .tab-container {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 30px;
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }
        
        .tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: #764ba2;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login span {
            position: relative;
            z-index: 1;
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #999;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .demo-credentials {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 10px;
            font-size: 13px;
        }
        
        .demo-credentials h3 {
            margin-bottom: 15px;
            color: #667eea;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .credential-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .credential-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .credential-item:last-child {
            margin-bottom: 0;
        }
        
        .credential-role {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .credential-role .icon {
            font-size: 24px;
        }
        
        .credential-info {
            text-align: right;
            font-size: 12px;
            color: #666;
        }
        
        .credential-info .username {
            font-weight: 600;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .login-container {
                width: 95%;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .back-button {
                top: 15px;
                left: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="back-button">
        <a href="index.php">
            <span>←</span> Back
        </a>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🎓</div>
            <h1>Welcome Back!</h1>
            <p>Sign in to access your dashboard</p>
        </div>
        
        <div class="login-body">
            <div class="tab-container">
                <div class="tab active" onclick="selectTab(this, 'login')">
                    <span>Login</span>
                </div>
                <div class="tab" onclick="selectTab(this, 'demo')">
                    <span>Demo Access</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <span>⚠️</span>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <div id="loginForm">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <span class="icon">👤</span>
                            <input type="text" id="username" name="username" 
                                   placeholder="Enter your username" required 
                                   autocomplete="username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <span class="icon">🔒</span>
                            <input type="password" id="password" name="password" 
                                   placeholder="Enter your password" required 
                                   autocomplete="current-password">
                        </div>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="forgot_password.php" onclick="alert('Please contact administrator'); return false;">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <span>Sign In</span>
                    </button>
                </form>
            </div>
            
            <div class="divider">
                <span>OR TRY DEMO</span>
            </div>
            
            <div class="demo-credentials">
                <h3>
                    <span>🎯</span>
                    <span>Quick Demo Access</span>
                </h3>
                
                <div class="credential-item" onclick="fillCredentials('admin', 'password')">
                    <div class="credential-role">
                        <span class="icon">👨‍💼</span>
                        <span>Administrator</span>
                    </div>
                    <div class="credential-info">
                        <div class="username">admin</div>
                        <div>Full System Access</div>
                    </div>
                </div>
                
                <div class="credential-item" onclick="fillCredentials('john.smith', 'password')">
                    <div class="credential-role">
                        <span class="icon">👨‍🏫</span>
                        <span>Teacher</span>
                    </div>
                    <div class="credential-info">
                        <div class="username">john.smith</div>
                        <div>Class Management</div>
                    </div>
                </div>
                
                <div class="credential-item" onclick="fillCredentials('alice.williams', 'password')">
                    <div class="credential-role">
                        <span class="icon">👨‍🎓</span>
                        <span>Student</span>
                    </div>
                    <div class="credential-info">
                        <div class="username">alice.williams</div>
                        <div>View Records</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectTab(element, tabName) {
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked tab
            element.classList.add('active');
            
            // Optional: You can add different content for demo tab if needed
        }
        
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Add a visual feedback
            const loginForm = document.getElementById('loginForm');
            loginForm.style.animation = 'none';
            setTimeout(() => {
                loginForm.style.animation = 'slideIn 0.3s ease-out';
            }, 10);
            
            // Optional: Auto-submit after a short delay
            setTimeout(() => {
                if (confirm('Login with ' + username + '?')) {
                    document.querySelector('form').submit();
                }
            }, 300);
        }
        
        // Add enter key support for credential items
        document.querySelectorAll('.credential-item').forEach(item => {
            item.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.click();
                }
            });
            item.setAttribute('tabindex', '0');
        });
        
        // Password visibility toggle (optional enhancement)
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('dblclick', function() {
            this.type = this.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>