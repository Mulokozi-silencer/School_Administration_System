<?php
// expenses.php - Expense Tracking
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Expense Management</title>
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa;}
        .top-nav {background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
        .top-nav-content {display: flex; justify-content: space-between; align-items: center; height: 70px;}
        .back-btn {background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none;}
        .container {max-width: 1200px; margin: 30px auto; padding: 0 20px;}
        .feature-card {background: white; padding: 60px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;}
        .feature-icon {font-size: 72px; margin-bottom: 20px;}
        h2 {color: #333; margin-bottom: 15px;}
        p {color: #666; line-height: 1.6;}
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <a href="dashboard.php" class="back-btn">← Dashboard</a>
            <h1>📤 Expense Management</h1>
            <a href="../logout.php" class="back-btn">Logout</a>
        </div>
    </nav>
    <div class="container">
        <div class="feature-card">
            <div class="feature-icon">📤</div>
            <h2>Expense Tracking Module</h2>
            <p>Track school expenses, vendor payments, salary disbursements, and operational costs. Generate expense reports and maintain detailed financial records for audit purposes.</p>
            <p style="margin-top: 20px; color: #27ae60; font-weight: 600;">Feature Coming Soon</p>
        </div>
    </div>
</body>
</html>

