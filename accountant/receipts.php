<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Get receipt if ID provided
$receipt = null;
if (isset($_GET['id'])) {
    $fee_id = intval($_GET['id']);
    $receipt = $conn->query("SELECT f.*, s.roll_number, s.first_name, s.last_name, s.email, s.phone, s.address,
                            c.class_name, c.section
                            FROM fees f
                            JOIN students s ON f.student_id = s.id
                            LEFT JOIN classes c ON s.class_id = c.id
                            WHERE f.id = $fee_id AND f.status = 'paid'")->fetch_assoc();
}

// Get recent receipts
$recent_receipts = $conn->query("SELECT f.id, f.payment_date, f.amount, f.fee_type, f.transaction_id,
                                 s.roll_number, s.first_name, s.last_name
                                 FROM fees f
                                 JOIN students s ON f.student_id = s.id
                                 WHERE f.status = 'paid'
                                 ORDER BY f.payment_date DESC
                                 LIMIT 20");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Generator</title>
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa;}
        .top-nav {background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
        .top-nav-content {display: flex; justify-content: space-between; align-items: center; height: 70px;}
        .nav-left {display: flex; align-items: center; gap: 20px;}
        .back-btn, .btn-logout {background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px;}
        .container {max-width: 1200px; margin: 30px auto; padding: 0 20px;}
        .content-grid {display: grid; grid-template-columns: 1fr 1fr; gap: 20px;}
        .receipt-list {background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
        .receipt-item {padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.3s;}
        .receipt-item:hover {background: #f8f9fa;}
        .receipt-preview {background: white; padding: 40px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 20px;}
        .receipt-header {text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #27ae60;}
        .receipt-header h1 {color: #27ae60; font-size: 32px; margin-bottom: 5px;}
        .receipt-header p {color: #666;}
        .receipt-number {background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px;}
        .info-grid {display: grid; gap: 15px; margin-bottom: 30px;}
        .info-row {display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0;}
        .info-label {color: #666; font-weight: 500;}
        .info-value {color: #333; font-weight: 600;}
        .amount-section {background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; padding: 25px; border-radius: 10px; text-align: center; margin: 30px 0;}
        .amount-section h3 {margin-bottom: 10px; opacity: 0.9;}
        .amount-value {font-size: 42px; font-weight: bold;}
        .receipt-footer {text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0; color: #666; font-size: 14px;}
        .btn-print {width: 100%; padding: 15px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; margin-top: 20px;}
        @media print {
            .top-nav, .receipt-list, .btn-print {display: none !important;}
            .content-grid {grid-template-columns: 1fr;}
            body {background: white;}
        }
        @media (max-width: 1024px) {
            .content-grid {grid-template-columns: 1fr;}
            .receipt-preview {position: static;}
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn"><span>←</span> Dashboard</a>
                <h1>🧾 Receipt Generator</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="content-grid">
            <div class="receipt-list">
                <h3 style="margin-bottom: 20px;">Recent Payments</h3>
                <?php while ($item = $recent_receipts->fetch_assoc()): ?>
                <div class="receipt-item" onclick="window.location.href='receipts.php?id=<?php echo $item['id']; ?>'">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <strong><?php echo $item['first_name'] . ' ' . $item['last_name']; ?></strong>
                        <strong style="color: #27ae60;">$<?php echo number_format($item['amount'], 2); ?></strong>
                    </div>
                    <div style="font-size: 14px; color: #666;">
                        <?php echo $item['roll_number']; ?> • <?php echo $item['fee_type']; ?> • 
                        <?php echo date('M d, Y', strtotime($item['payment_date'])); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="receipt-preview">
                <?php if ($receipt): ?>
                    <div class="receipt-header">
                        <h1>📋 PAYMENT RECEIPT</h1>
                        <p>School Administration System</p>
                    </div>
                    
                    <div class="receipt-number">
                        <strong>Receipt No:</strong> #RCP-<?php echo str_pad($receipt['id'], 6, '0', STR_PAD_LEFT); ?><br>
                        <strong>Date:</strong> <?php echo date('F d, Y', strtotime($receipt['payment_date'])); ?>
                    </div>
                    
                    <div class="info-grid">
                        <div style="margin-bottom: 20px;">
                            <h4 style="color: #27ae60; margin-bottom: 10px;">STUDENT INFORMATION</h4>
                            <div class="info-row">
                                <span class="info-label">Student Name:</span>
                                <span class="info-value"><?php echo $receipt['first_name'] . ' ' . $receipt['last_name']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Roll Number:</span>
                                <span class="info-value"><?php echo $receipt['roll_number']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Class:</span>
                                <span class="info-value"><?php echo $receipt['class_name'] . ' - ' . $receipt['section']; ?></span>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="color: #27ae60; margin-bottom: 10px;">PAYMENT DETAILS</h4>
                            <div class="info-row">
                                <span class="info-label">Fee Type:</span>
                                <span class="info-value"><?php echo $receipt['fee_type']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Method:</span>
                                <span class="info-value"><?php echo $receipt['payment_method']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Transaction ID:</span>
                                <span class="info-value"><?php echo $receipt['transaction_id'] ?: '-'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="amount-section">
                        <h3>AMOUNT PAID</h3>
                        <div class="amount-value">$<?php echo number_format($receipt['amount'], 2); ?></div>
                    </div>
                    
                    <div class="receipt-footer">
                        <p><strong>Thank you for your payment!</strong></p>
                        <p style="margin-top: 10px;">This is a computer-generated receipt and does not require a signature.</p>
                        <p style="margin-top: 5px;">For queries, contact: accounts@school.com | +1 (555) 123-4567</p>
                    </div>
                    
                    <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: #999;">
                        <div style="font-size: 64px; margin-bottom: 20px;">🧾</div>
                        <h3>Select a Payment</h3>
                        <p style="margin-top: 10px;">Click on any payment from the list to generate receipt</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>