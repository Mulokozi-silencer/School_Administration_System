<?php
require_once 'config.php';

$message = '';
$message_type = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    $conn = getDBConnection();
    
    // Check if user exists based on user type
    $user_type = sanitize($_POST['user_type']);
    
    if ($user_type === 'admin') {
        // Admin doesn't have email in separate table
        $stmt = $conn->prepare("SELECT u.id, u.username FROM users u WHERE u.username = ? AND u.user_type = 'admin'");
        $stmt->bind_param("s", $username);
    } elseif ($user_type === 'teacher') {
        $stmt = $conn->prepare("SELECT u.id, u.username, t.email FROM users u 
                               JOIN teachers t ON u.id = t.user_id 
                               WHERE u.username = ? AND t.email = ? AND u.user_type = 'teacher'");
        $stmt->bind_param("ss", $username, $email);
    } elseif ($user_type === 'student') {
        $stmt = $conn->prepare("SELECT u.id, u.username, s.email FROM users u 
                               JOIN students s ON u.id = s.user_id 
                               WHERE u.username = ? AND s.email = ? AND u.user_type = 'student'");
        $stmt->bind_param("ss", $username, $email);
    } elseif ($user_type === 'accountant') {
        $stmt = $conn->prepare("SELECT u.id, u.username FROM users u WHERE u.username = ? AND u.user_type = 'accountant'");
        $stmt->bind_param("s", $username);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database (you'll need to create this table)
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
        $stmt->bind_param("issss", $user['id'], $token, $expires, $token, $expires);
        $stmt->execute();
        
        // In production, send email with reset link
        // For now, we'll show the reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
        
        $message = "Password reset link generated! <br><br>
                   <strong>Reset Link:</strong> <a href='$reset_link' style='color: #667eea;'>$reset_link</a><br><br>
                   <small>Link expires in 1 hour. In production, this would be sent to your email.</small>";
        $message_type = 'success';
    } else {
        $message = "No account found with those credentials. Please check your username and email.";
        $message_type = 'error';
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
    <title>Forgot Password - School Administration</title>
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
        
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 500px;
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
        
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .reset-header .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .reset-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .reset-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .reset-body {
            padding: 40px;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #0d47a1;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .btn-submit {
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
        
        .btn-submit::before {
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
        
        .btn-submit:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-submit span {
            position: relative;
            z-index: 1;
        }
        
        .help-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .help-text a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .reset-container {
                width: 95%;
            }
            
            .reset-header, .reset-body {
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
        <a href="login.php">
            <span>←</span> Back to Login
        </a>
    </div>
    
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon">🔐</div>
            <h1>Forgot Password?</h1>
            <p>No worries! We'll help you reset it</p>
        </div>
        
        <div class="reset-body">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>📧 Password Reset Process:</strong>
                Enter your username and email address. You'll receive a secure link to reset your password.
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="request_reset" value="1">
                
                <div class="form-group">
                    <label for="user_type">I am a *</label>
                    <div class="input-wrapper">
                        <span class="icon">👤</span>
                        <select name="user_type" id="user_type" required onchange="toggleEmailField()">
                            <option value="">Select Your Role</option>
                            <option value="admin">Administrator</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                            <option value="accountant">Accountant</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <div class="input-wrapper">
                        <span class="icon">👤</span>
                        <input type="text" id="username" name="username" 
                               placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group" id="emailGroup" style="display: none;">
                    <label for="email">Email Address *</label>
                    <div class="input-wrapper">
                        <span class="icon">📧</span>
                        <input type="email" id="email" name="email" 
                               placeholder="Enter your registered email">
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <span>🔑 Request Password Reset</span>
                </button>
            </form>
            
            <div class="help-text">
                Remember your password? <a href="login.php">Login here</a><br>
                <small style="color: #999; margin-top: 10px; display: block;">
                    Need help? Contact admin at support@school.com
                </small>
            </div>
        </div>
    </div>
    
    <script>
        function toggleEmailField() {
            const userType = document.getElementById('user_type').value;
            const emailGroup = document.getElementById('emailGroup');
            const emailInput = document.getElementById('email');
            
            // Require email for teachers and students only
            if (userType === 'teacher' || userType === 'student') {
                emailGroup.style.display = 'block';
                emailInput.required = true;
            } else {
                emailGroup.style.display = 'none';
                emailInput.required = false;
            }
        }
    </script>
</body>
</html>