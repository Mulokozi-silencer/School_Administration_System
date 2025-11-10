<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Get filter parameters
$date_filter = isset($_GET['date_filter']) ? sanitize($_GET['date_filter']) : 'today';
$payment_method = isset($_GET['payment_method']) ? sanitize($_GET['payment_method']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Calculate date range
$today = date('Y-m-d');
switch($date_filter) {
    case 'today':
        $start_date = $today;
        $end_date = $today;
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = $start_date;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = $today;
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = $today;
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : $today;
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : $today;
        break;
    default:
        $start_date = $today;
        $end_date = $today;
}

// Build query
$query = "SELECT f.*, s.roll_number, s.first_name, s.last_name, c.class_name, c.section 
          FROM fees f
          JOIN students s ON f.student_id = s.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE f.status = 'paid' 
          AND f.payment_date BETWEEN '$start_date' AND '$end_date'";

if ($payment_method !== 'all') {
    $query .= " AND f.payment_method = '$payment_method'";
}

if ($search) {
    $query .= " AND (s.roll_number LIKE '%$search%' OR s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR f.transaction_id LIKE '%$search%')";
}

$query .= " ORDER BY f.payment_date DESC, f.id DESC";
$collections = $conn->query($query);

// Get statistics for the period
$stats_query = "SELECT 
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
                FROM fees 
                WHERE status = 'paid' 
                AND payment_date BETWEEN '$start_date' AND '$end_date'";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get payment method breakdown
$method_breakdown = $conn->query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
                                  FROM fees 
                                  WHERE status = 'paid' 
                                  AND payment_date BETWEEN '$start_date' AND '$end_date'
                                  GROUP BY payment_method
                                  ORDER BY total DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections History - Accountant</title>
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
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .back-btn, .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover, .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #27ae60;
        }
        
        .stat-card h3 {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-group select, .form-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-export {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .method-breakdown {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .method-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .method-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .method-info p {
            font-size: 13px;
            color: #666;
        }
        
        .method-amount {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn">
                    <span>←</span> Dashboard
                </a>
                <h1 class="page-title">💰 Collections History</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Collections</h3>
                <div class="value">$<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Transactions</h3>
                <div class="value"><?php echo $stats['total_transactions'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Amount</h3>
                <div class="value">$<?php echo number_format($stats['avg_amount'] ?? 0, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Highest Payment</h3>
                <div class="value">$<?php echo number_format($stats['max_amount'] ?? 0, 2); ?></div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Date Range</label>
                        <select name="date_filter" id="dateFilter" onchange="toggleCustomDates()">
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo $date_filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="custom" <?php echo $date_filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="startDateGroup" style="display: <?php echo $date_filter === 'custom' ? 'flex' : 'none'; ?>;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="form-group" id="endDateGroup" style="display: <?php echo $date_filter === 'custom' ? 'flex' : 'none'; ?>;">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="all">All Methods</option>
                            <option value="Cash" <?php echo $payment_method === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo $payment_method === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Credit Card" <?php echo $payment_method === 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                            <option value="Debit Card" <?php echo $payment_method === 'Debit Card' ? 'selected' : ''; ?>>Debit Card</option>
                            <option value="Mobile Money" <?php echo $payment_method === 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Roll No, Name, Txn ID" value="<?php echo $search; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter">Apply Filters</button>
                    <button type="button" class="btn-export" onclick="exportToExcel()">📊 Export to Excel</button>
                </div>
            </form>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Collections Table -->
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">Payment Records</h3>
                <?php if ($collections->num_rows > 0): ?>
                <table id="collectionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Roll No</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Txn ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $collections->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo $row['roll_number']; ?></td>
                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                            <td><?php echo $row['class_name'] . '-' . $row['section']; ?></td>
                            <td><?php echo $row['fee_type']; ?></td>
                            <td style="font-weight: bold; color: #27ae60;">$<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td><?php echo $row['transaction_id'] ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <p>📭 No collections found for the selected period</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Method Breakdown -->
            <div class="method-breakdown">
                <h3 style="margin-bottom: 20px;">Payment Methods</h3>
                <?php while ($method = $method_breakdown->fetch_assoc()): ?>
                <div class="method-item">
                    <div class="method-info">
                        <h4><?php echo $method['payment_method']; ?></h4>
                        <p><?php echo $method['count']; ?> transactions</p>
                    </div>
                    <div class="method-amount">$<?php echo number_format($method['total'], 2); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCustomDates() {
            const dateFilter = document.getElementById('dateFilter').value;
            const startDateGroup = document.getElementById('startDateGroup');
            const endDateGroup = document.getElementById('endDateGroup');
            
            if (dateFilter === 'custom') {
                startDateGroup.style.display = 'flex';
                endDateGroup.style.display = 'flex';
            } else {
                startDateGroup.style.display = 'none';
                endDateGroup.style.display = 'none';
            }
        }
        
        function exportToExcel() {
            const table = document.getElementById('collectionsTable');
            const uri = 'data:application/vnd.ms-excel;base64,';
            const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Collections</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>';
            
            const base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
            const format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };
            
            const ctx = { table: table.innerHTML };
            const link = document.createElement('a');
            link.download = 'collections_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.xls';
            link.href = uri + base64(format(template, ctx));
            link.click();
        }
    </script>
</body>
</html>