<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Generate report if requested
$report_data = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = sanitize($_POST['report_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    
    switch($report_type) {
        case 'summary':
            $report_data = [
                'type' => 'Financial Summary',
                'total_collected' => $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'paid' AND payment_date BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['total'] ?? 0,
                'total_pending' => $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending' AND created_at BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['total'] ?? 0,
                'by_type' => $conn->query("SELECT fee_type, SUM(amount) as total, COUNT(*) as count FROM fees WHERE payment_date BETWEEN '$start_date' AND '$end_date' AND status = 'paid' GROUP BY fee_type ORDER BY total DESC"),
                'by_method' => $conn->query("SELECT payment_method, SUM(amount) as total, COUNT(*) as count FROM fees WHERE payment_date BETWEEN '$start_date' AND '$end_date' AND status = 'paid' GROUP BY payment_method ORDER BY total DESC"),
            ];
            break;
        case 'daily':
            $report_data = [
                'type' => 'Daily Collection Report',
                'collections' => $conn->query("SELECT DATE(payment_date) as date, SUM(amount) as total, COUNT(*) as count FROM fees WHERE status = 'paid' AND payment_date BETWEEN '$start_date' AND '$end_date' GROUP BY DATE(payment_date) ORDER BY date DESC")
            ];
            break;
        case 'class':
            $report_data = [
                'type' => 'Class-wise Report',
                'by_class' => $conn->query("SELECT c.class_name, c.section, SUM(f.amount) as total, COUNT(f.id) as count, SUM(CASE WHEN f.status = 'paid' THEN f.amount ELSE 0 END) as collected, SUM(CASE WHEN f.status = 'pending' THEN f.amount ELSE 0 END) as pending FROM fees f JOIN students s ON f.student_id = s.id JOIN classes c ON s.class_id = c.id WHERE f.created_at BETWEEN '$start_date' AND '$end_date' GROUP BY c.id ORDER BY collected DESC")
            ];
            break;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports</title>
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa;}
        .top-nav {background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
        .top-nav-content {display: flex; justify-content: space-between; align-items: center; height: 70px;}
        .nav-left {display: flex; align-items: center; gap: 20px;}
        .back-btn, .btn-logout {background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px;}
        .container {max-width: 1200px; margin: 30px auto; padding: 0 20px;}
        .report-generator {background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;}
        .form-grid {display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; margin-top: 20px;}
        .form-group label {display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;}
        .form-group select, .form-group input {width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;}
        .btn-generate {padding: 12px 25px; background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; align-self: end;}
        .report-view {background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
        .report-header {display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;}
        table {width: 100%; border-collapse: collapse; margin: 20px 0;}
        th, td {padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0;}
        th {background: #f8f9fa; font-weight: 600;}
        .summary-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;}
        .summary-card {padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #27ae60;}
        .summary-card h4 {font-size: 14px; color: #666; margin-bottom: 10px;}
        .summary-card .value {font-size: 28px; font-weight: bold; color: #27ae60;}
        .btn-export {padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer;}
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn"><span>←</span> Dashboard</a>
                <h1>📈 Financial Reports</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="report-generator">
            <h2 style="margin-bottom: 10px;">Generate Report</h2>
            <p style="color: #666; margin-bottom: 20px;">Select report type and date range to generate financial reports</p>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="report_type" required>
                            <option value="summary">Financial Summary</option>
                            <option value="daily">Daily Collections</option>
                            <option value="class">Class-wise Report</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn-generate">📊 Generate</button>
                </div>
            </form>
        </div>
        
        <?php if ($report_data): ?>
        <div class="report-view">
            <div class="report-header">
                <div>
                    <h2><?php echo $report_data['type']; ?></h2>
                    <p style="color: #666; margin-top: 5px;">
                        <?php echo date('M d, Y', strtotime($_POST['start_date'])) . ' - ' . date('M d, Y', strtotime($_POST['end_date'])); ?>
                    </p>
                </div>
                <button class="btn-export" onclick="window.print()">🖨️ Print Report</button>
            </div>
            
            <?php if ($report_data['type'] === 'Financial Summary'): ?>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Total Collected</h4>
                        <div class="value">$<?php echo number_format($report_data['total_collected'], 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h4>Total Pending</h4>
                        <div class="value" style="color: #f39c12;">$<?php echo number_format($report_data['total_pending'], 2); ?></div>
                    </div>
                </div>
                
                <h3 style="margin: 30px 0 15px 0;">Collection by Fee Type</h3>
                <table>
                    <tr><th>Fee Type</th><th>Transactions</th><th>Amount</th></tr>
                    <?php while ($row = $report_data['by_type']->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['fee_type']; ?></td>
                        <td><?php echo $row['count']; ?></td>
                        <td style="font-weight: bold; color: #27ae60;">$<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                
                <h3 style="margin: 30px 0 15px 0;">Collection by Payment Method</h3>
                <table>
                    <tr><th>Payment Method</th><th>Transactions</th><th>Amount</th></tr>
                    <?php while ($row = $report_data['by_method']->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['payment_method']; ?></td>
                        <td><?php echo $row['count']; ?></td>
                        <td style="font-weight: bold; color: #27ae60;">$<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            
            <?php elseif ($report_data['type'] === 'Daily Collection Report'): ?>
                <table>
                    <tr><th>Date</th><th>Transactions</th><th>Amount Collected</th></tr>
                    <?php while ($row = $report_data['collections']->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y (l)', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['count']; ?></td>
                        <td style="font-weight: bold; color: #27ae60;">$<?php echo number_format($row['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            
            <?php elseif ($report_data['type'] === 'Class-wise Report'): ?>
                <table>
                    <tr><th>Class</th><th>Total Fees</th><th>Collected</th><th>Pending</th><th>Collection %</th></tr>
                    <?php while ($row = $report_data['by_class']->fetch_assoc()): 
                        $rate = $row['total'] > 0 ? round(($row['collected'] / $row['total']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                        <td>$<?php echo number_format($row['total'], 2); ?></td>
                        <td style="color: #27ae60; font-weight: bold;">$<?php echo number_format($row['collected'], 2); ?></td>
                        <td style="color: #f39c12;">$<?php echo number_format($row['pending'], 2); ?></td>
                        <td><?php echo $rate; ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>