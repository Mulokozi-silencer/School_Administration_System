<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Get pending payments with overdue status
$pending_payments = $conn->query("SELECT f.*, s.roll_number, s.first_name, s.last_name, s.phone, s.email,
                                  c.class_name, c.section,
                                  DATEDIFF(CURDATE(), f.due_date) as days_overdue
                                  FROM fees f
                                  JOIN students s ON f.student_id = s.id
                                  LEFT JOIN classes c ON s.class_id = c.id
                                  WHERE f.status = 'pending'
                                  ORDER BY f.due_date ASC");

// Statistics
$total_pending = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$overdue_amount = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending' AND due_date < CURDATE()")->fetch_assoc()['total'] ?? 0;
$due_this_week = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;
$students_pending = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM fees WHERE status = 'pending'")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Payments - Accountant</title>
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa;}
        .top-nav {background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100;}
        .top-nav-content {display: flex; justify-content: space-between; align-items: center; height: 70px;}
        .nav-left {display: flex; align-items: center; gap: 20px;}
        .back-btn, .btn-logout {background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px;}
        .page-title {font-size: 24px; font-weight: 600;}
        .container {max-width: 1400px; margin: 30px auto; padding: 0 20px;}
        .stats-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;}
        .stat-card {background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
        .stat-card h3 {font-size: 14px; color: #666; margin-bottom: 10px;}
        .stat-card .value {font-size: 32px; font-weight: bold;}
        .stat-card.warning .value {color: #f39c12;}
        .stat-card.danger .value {color: #e74c3c;}
        .stat-card.info .value {color: #3498db;}
        .pending-grid {display: grid; gap: 15px;}
        .pending-card {background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #f39c12; display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center;}
        .pending-card.overdue {border-left-color: #e74c3c;}
        .pending-info h3 {color: #333; margin-bottom: 8px;}
        .pending-meta {display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px; color: #666;}
        .pending-meta span {display: flex; align-items: center; gap: 5px;}
        .pending-actions {text-align: right;}
        .fee-amount {font-size: 28px; font-weight: bold; color: #e74c3c; margin-bottom: 5px;}
        .due-badge {padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;}
        .due-overdue {background: #f8d7da; color: #721c24;}
        .due-soon {background: #fff3cd; color: #856404;}
        .due-upcoming {background: #d1ecf1; color: #0c5460;}
        .btn-send-reminder {padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 10px; width: 100%;}
        .btn-contact {padding: 8px 16px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;}
        @media (max-width: 768px) {.pending-card {grid-template-columns: 1fr;}}
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn"><span>←</span> Dashboard</a>
                <h1 class="page-title">⏳ Pending Payments</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card warning">
                <h3>Total Pending</h3>
                <div class="value">$<?php echo number_format($total_pending, 2); ?></div>
            </div>
            <div class="stat-card danger">
                <h3>Overdue Amount</h3>
                <div class="value">$<?php echo number_format($overdue_amount, 2); ?></div>
            </div>
            <div class="stat-card info">
                <h3>Due This Week</h3>
                <div class="value">$<?php echo number_format($due_this_week, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Students with Pending</h3>
                <div class="value" style="color: #27ae60;"><?php echo $students_pending; ?></div>
            </div>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;">
            <h2 style="margin-bottom: 20px;">All Pending Payments</h2>
            <div class="pending-grid">
                <?php while ($payment = $pending_payments->fetch_assoc()): 
                    $is_overdue = $payment['days_overdue'] > 0;
                    $due_status = $is_overdue ? 'overdue' : ($payment['days_overdue'] > -7 ? 'soon' : 'upcoming');
                ?>
                <div class="pending-card <?php echo $is_overdue ? 'overdue' : ''; ?>">
                    <div class="pending-info">
                        <h3><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></h3>
                        <div class="pending-meta">
                            <span>📋 <?php echo $payment['roll_number']; ?></span>
                            <span>🏫 <?php echo $payment['class_name'] . '-' . $payment['section']; ?></span>
                            <span>📚 <?php echo $payment['fee_type']; ?></span>
                            <span>📅 Due: <?php echo date('M d, Y', strtotime($payment['due_date'])); ?></span>
                        </div>
                        <div style="margin-top: 10px;">
                            <span class="due-badge due-<?php echo $due_status; ?>">
                                <?php 
                                if ($is_overdue) {
                                    echo "⚠️ Overdue by " . $payment['days_overdue'] . " days";
                                } else if ($payment['days_overdue'] > -7) {
                                    echo "⏰ Due in " . abs($payment['days_overdue']) . " days";
                                } else {
                                    echo "📅 Upcoming";
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="pending-actions">
                        <div class="fee-amount">$<?php echo number_format($payment['amount'], 2); ?></div>
                        <button class="btn-send-reminder" onclick="sendReminder('<?php echo $payment['email']; ?>', '<?php echo $payment['first_name']; ?>')">
                            📧 Send Reminder
                        </button>
                        <?php if ($payment['phone']): ?>
                        <button class="btn-contact" onclick="contactStudent('<?php echo $payment['phone']; ?>')">
                            📞 <?php echo $payment['phone']; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <script>
        function sendReminder(email, name) {
            if (confirm(`Send payment reminder to ${name} at ${email}?`)) {
                alert('Email reminder sent successfully!');
                // In production, this would call an API to send actual email
            }
        }
        
        function contactStudent(phone) {
            window.location.href = 'tel:' + phone;
        }
    </script>
</body>
</html>