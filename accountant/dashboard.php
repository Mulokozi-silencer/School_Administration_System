<?php
require_once '../config.php';

// Add accountant user type check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Financial Statistics
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_year = date('Y');

// Today's collections
$today_collections = $conn->query("SELECT SUM(amount) as total FROM fees 
                                   WHERE status = 'paid' AND payment_date = '$today'")->fetch_assoc()['total'] ?? 0;

// This month's collections
$month_collections = $conn->query("SELECT SUM(amount) as total FROM fees 
                                   WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'")->fetch_assoc()['total'] ?? 0;

// This year's collections
$year_collections = $conn->query("SELECT SUM(amount) as total FROM fees 
                                  WHERE status = 'paid' AND YEAR(payment_date) = '$current_year'")->fetch_assoc()['total'] ?? 0;

// Pending fees
$pending_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

// Overdue fees
$overdue_fees = $conn->query("SELECT SUM(amount) as total FROM fees 
                              WHERE status = 'pending' AND due_date < CURDATE()")->fetch_assoc()['total'] ?? 0;

// Total outstanding
$total_outstanding = $pending_fees + $overdue_fees;

// Recent payments (last 10)
$recent_payments = $conn->query("SELECT f.*, s.first_name, s.last_name, s.roll_number, c.class_name, c.section 
                                FROM fees f
                                JOIN students s ON f.student_id = s.id
                                LEFT JOIN classes c ON s.class_id = c.id
                                WHERE f.status = 'paid'
                                ORDER BY f.payment_date DESC
                                LIMIT 10");

// Pending payments (urgent - due soon)
$pending_payments = $conn->query("SELECT f.*, s.first_name, s.last_name, s.roll_number, c.class_name, c.section 
                                 FROM fees f
                                 JOIN students s ON f.student_id = s.id
                                 LEFT JOIN classes c ON s.class_id = c.id
                                 WHERE f.status = 'pending'
                                 ORDER BY f.due_date ASC
                                 LIMIT 10");

// Fee collection by type (current month)
$fee_by_type = $conn->query("SELECT fee_type, SUM(amount) as total, COUNT(*) as count 
                             FROM fees 
                             WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'
                             GROUP BY fee_type
                             ORDER BY total DESC");

// Monthly collection trend (last 6 months)
$monthly_trend = $conn->query("SELECT 
                               DATE_FORMAT(payment_date, '%Y-%m') as month,
                               SUM(amount) as total
                               FROM fees
                               WHERE status = 'paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY month
                               ORDER BY month ASC");

// Payment methods breakdown (current month)
$payment_methods = $conn->query("SELECT payment_method, SUM(amount) as total, COUNT(*) as count 
                                FROM fees 
                                WHERE status = 'paid' AND DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'
                                GROUP BY payment_method");

// Students with pending fees
$students_pending = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM fees WHERE status = 'pending'")->fetch_assoc()['count'];

// Collection rate
$total_fees = $conn->query("SELECT SUM(amount) as total FROM fees")->fetch_assoc()['total'] ?? 1;
$paid_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$collection_rate = round(($paid_fees / $total_fees) * 100, 1);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - School Administration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Top Navigation */
        .top-nav {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-section .icon {
            font-size: 36px;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .logo-text p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #27ae60;
            font-weight: bold;
        }
        
        .user-details {
            line-height: 1.4;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Main Layout */
        .main-layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 30px 0;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .menu-item {
            margin: 5px 15px;
        }
        
        .menu-item a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: #666;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .menu-item a:hover, .menu-item a.active {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .menu-icon {
            font-size: 24px;
            width: 30px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.3);
        }
        
        .welcome-banner h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .welcome-banner p {
            opacity: 0.95;
            font-size: 15px;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.green::before {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .stat-card.blue::before {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        
        .stat-card.orange::before {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .stat-card.red::before {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            font-size: 40px;
            opacity: 0.2;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-footer {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #27ae60;
        }
        
        .stat-footer.negative {
            color: #e74c3c;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h3 {
            font-size: 20px;
            color: #333;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .action-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.3);
        }
        
        .action-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .action-card h4 {
            font-size: 15px;
            font-weight: 600;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 5px;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
        }
        
        .activity-amount {
            font-weight: bold;
            color: #27ae60;
            font-size: 16px;
        }
        
        .activity-meta {
            font-size: 13px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .fee-type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        .fee-type-item:hover {
            background: #f8f9fa;
        }
        
        .fee-type-info {
            flex: 1;
        }
        
        .fee-type-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .fee-type-count {
            font-size: 13px;
            color: #666;
        }
        
        .fee-type-amount {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="logo-section">
                <span class="icon">💰</span>
                <div class="logo-text">
                    <h1>Finance Portal</h1>
                    <p>School Administration System</p>
                </div>
            </div>
            
            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div class="user-details">
                        <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                        <div class="user-role">Accountant</div>
                    </div>
                </div>
                
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php" class="active">
                        <span class="menu-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="fees.php">
                        <span class="menu-icon">💳</span>
                        <span>Fee Management</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="collections.php">
                        <span class="menu-icon">💰</span>
                        <span>Collections</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="pending.php">
                        <span class="menu-icon">⏳</span>
                        <span>Pending Payments</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reports.php">
                        <span class="menu-icon">📈</span>
                        <span>Financial Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="receipts.php">
                        <span class="menu-icon">🧾</span>
                        <span>Receipts</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="expenses.php">
                        <span class="menu-icon">📤</span>
                        <span>Expenses</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="statements.php">
                        <span class="menu-icon">📄</span>
                        <span>Statements</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2>Welcome back, Accountant! 💼</h2>
                <p>Here's your financial overview for <?php echo date('F d, Y'); ?></p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Today's Collections</div>
                            <div class="stat-value">$<?php echo number_format($today_collections, 2); ?></div>
                            <div class="stat-footer">
                                <span>📅</span> <?php echo date('l'); ?>
                            </div>
                        </div>
                        <div class="stat-icon">💵</div>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">This Month</div>
                            <div class="stat-value">$<?php echo number_format($month_collections, 2); ?></div>
                            <div class="stat-footer">
                                <span>📆</span> <?php echo date('F Y'); ?>
                            </div>
                        </div>
                        <div class="stat-icon">💰</div>
                    </div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Year to Date</div>
                            <div class="stat-value">$<?php echo number_format($year_collections, 2); ?></div>
                            <div class="stat-footer">
                                <span>📊</span> <?php echo date('Y'); ?>
                            </div>
                        </div>
                        <div class="stat-icon">💎</div>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Pending Fees</div>
                            <div class="stat-value">$<?php echo number_format($pending_fees, 2); ?></div>
                            <div class="stat-footer">
                                <span>⏳</span> <?php echo $students_pending; ?> students
                            </div>
                        </div>
                        <div class="stat-icon">⏰</div>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Overdue Fees</div>
                            <div class="stat-value">$<?php echo number_format($overdue_fees, 2); ?></div>
                            <div class="stat-footer negative">
                                <span>⚠️</span> Needs attention
                            </div>
                        </div>
                        <div class="stat-icon">🚨</div>
                    </div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Collection Rate</div>
                            <div class="stat-value"><?php echo $collection_rate; ?>%</div>
                            <div class="stat-footer">
                                <span>📈</span> Overall performance
                            </div>
                        </div>
                        <div class="stat-icon">🎯</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                <div class="actions-grid">
                    <a href="fees.php?action=collect" class="action-card">
                        <div class="action-icon">💳</div>
                        <h4>Collect Fee</h4>
                    </a>
                    <a href="receipts.php?action=generate" class="action-card">
                        <div class="action-icon">🧾</div>
                        <h4>Generate Receipt</h4>
                    </a>
                    <a href="pending.php" class="action-card">
                        <div class="action-icon">📋</div>
                        <h4>View Pending</h4>
                    </a>
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h4>Financial Report</h4>
                    </a>
                    <a href="expenses.php?action=add" class="action-card">
                        <div class="action-icon">📤</div>
                        <h4>Record Expense</h4>
                    </a>
                    <a href="statements.php" class="action-card">
                        <div class="action-icon">📄</div>
                        <h4>Generate Statement</h4>
                    </a>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="activity-card">
                    <div class="section-header">
                        <h3>💳 Recent Payments</h3>
                        <a href="collections.php" style="color: #27ae60; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <ul class="activity-list">
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-header">
                                <div class="activity-title">
                                    <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?>
                                </div>
                                <div class="activity-amount">$<?php echo number_format($payment['amount'], 2); ?></div>
                            </div>
                            <div class="activity-meta">
                                <span><?php echo $payment['roll_number']; ?> - <?php echo $payment['class_name'] . '-' . $payment['section']; ?></span>
                                <span><?php echo $payment['fee_type']; ?></span>
                            </div>
                            <div class="activity-meta">
                                <span>📅 <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></span>
                                <span>💳 <?php echo $payment['payment_method']; ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                
                <div class="activity-card">
                    <div class="section-header">
                        <h3>⏳ Urgent Pending</h3>
                        <a href="pending.php" style="color: #27ae60; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <ul class="activity-list">
                        <?php while ($pending = $pending_payments->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-header">
                                <div class="activity-title">
                                    <?php echo $pending['first_name'] . ' ' . $pending['last_name']; ?>
                                </div>
                                <div class="activity-amount" style="color: #e74c3c;">$<?php echo number_format($pending['amount'], 2); ?></div>
                            </div>
                            <div class="activity-meta">
                                <span><?php echo $pending['roll_number']; ?></span>
                                <span class="status-badge <?php echo strtotime($pending['due_date']) < time() ? 'status-overdue' : 'status-pending'; ?>">
                                    Due: <?php echo date('M d', strtotime($pending['due_date'])); ?>
                                </span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Fee Types Breakdown -->
            <div class="chart-container">
                <div class="section-header">
                    <h3>📊 Fee Collection by Type (This Month)</h3>
                </div>
                <div>
                    <?php while ($fee_type = $fee_by_type->fetch_assoc()): ?>
                    <div class="fee-type-item">
                        <div class="fee-type-info">
                            <div class="fee-type-name"><?php echo $fee_type['fee_type']; ?></div>
                            <div class="fee-type-count"><?php echo $fee_type['count']; ?> transactions</div>
                        </div>
                        <div class="fee-type-amount">$<?php echo number_format($fee_type['total'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>