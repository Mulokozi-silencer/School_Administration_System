<?php
require_once 'config.php';

$message = '';
$message_type = '';
$valid_token = false;
$user_id = null;

// Check if token is valid
if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    $conn = getDBConnection();
    
    // Verify token and check if not expired
    $stmt = $conn->prepare("SELECT pr.user_id, u.username, u.user_type 
                           FROM password_resets pr
                           JOIN users u ON pr.user_id = u.id
                           WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $valid_token = true;
        $user_data = $result->fetch_assoc();
        $user_id = $user_data['user_id'];
    } else {
        $message = "Invalid or expired reset link. Please request a new password reset.";
        $message_type = 'error';
    }
    
    $stmt->close();
    $conn->close();
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = sanitize($_POST['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long!";
        $message_type = 'error';
    } else {
        $conn = getDBConnection();
        
        // Verify token again
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = "Password reset successful! You can now login with your new password.";
            $message_type = 'success';
            $valid_token = false; // Hide form after successful reset
        } else {
            $message = "Invalid or expired token!";
            $message_type = 'error';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - School Administration</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .password-strength {
            margin-top: 10px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: #e74c3c;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #f39c12;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: #27ae60;
        }
        
        .password-hint {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
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
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .help-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .reset-container {
                width: 95%;
            }
            
            .reset-header, .reset-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="icon">🔑</div>
            <h1>Set New Password</h1>
            <p>Create a strong and secure password</p>
        </div>
        
        <div class="reset-body">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <span><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></span>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <div class="input-wrapper">
                            <span class="icon">🔒</span>
                            <input type="password" id="new_password" name="new_password" 
                                   placeholder="Enter new password" required minlength="6"
                                   onkeyup="checkPasswordStrength()">
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-hint">
                            Password must be at least 6 characters long
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="input-wrapper">
                            <span class="icon">🔒</span>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Re-enter new password" required minlength="6"
                                   onkeyup="checkPasswordMatch()">
                        </div>
                        <div id="matchMessage" style="font-size: 13px; margin-top: 8px;"></div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <span>🔐 Reset Password</span>
                    </button>
                </form>
            <?php elseif ($message_type === 'success'): ?>
                <a href="login.php" class="btn-login">
                    🚀 Go to Login
                </a>
            <?php else: ?>
                <div style="text-align: center;">
                    <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                        ← Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="help-text">
                <small>Need help? Contact admin at support@school.com</small>
            </div>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const message = document.getElementById('matchMessage');
            
            if (confirm === '') {
                message.textContent = '';
                message.style.color = '';
            } else if (password === confirm) {
                message.textContent = '✓ Passwords match';
                message.style.color = '#27ae60';
            } else {
                message.textContent = '✗ Passwords do not match';
                message.style.color = '#e74c3c';
            }
        }
    </script>
</body>
</html>