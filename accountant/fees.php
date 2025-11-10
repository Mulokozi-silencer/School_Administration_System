<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'accountant') {
    header('Location: ../login.php');
    exit();
}

$conn = getDBConnection();

// Handle fee collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_fee'])) {
    $fee_id = intval($_POST['fee_id']);
    $payment_method = sanitize($_POST['payment_method']);
    $transaction_id = sanitize($_POST['transaction_id']);
    $payment_date = date('Y-m-d');
    
    $stmt = $conn->prepare("UPDATE fees SET status = 'paid', payment_date = ?, payment_method = ?, transaction_id = ? WHERE id = ?");
    $stmt->bind_param("sssi", $payment_date, $payment_method, $transaction_id, $fee_id);
    $stmt->execute();
    
    header('Location: fees.php?msg=collected');
    exit();
}

// Handle adding new fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $student_id = intval($_POST['student_id']);
    $fee_type = sanitize($_POST['fee_type']);
    $amount = floatval($_POST['amount']);
    $due_date = sanitize($_POST['due_date']);
    $remarks = sanitize($_POST['remarks']);
    
    $stmt = $conn->prepare("INSERT INTO fees (student_id, fee_type, amount, due_date, remarks) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $student_id, $fee_type, $amount, $due_date, $remarks);
    $stmt->execute();
    
    header('Location: fees.php?msg=added');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT f.*, s.roll_number, s.first_name, s.last_name, c.class_name, c.section 
          FROM fees f
          JOIN students s ON f.student_id = s.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND f.status = '$status_filter'";
}

if ($class_filter > 0) {
    $query .= " AND s.class_id = $class_filter";
}

if ($search) {
    $query .= " AND (s.roll_number LIKE '%$search%' OR s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%')";
}

$query .= " ORDER BY f.due_date DESC";
$fees = $conn->query($query);

// Get classes for filter
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name");

// Get students for add fee form
$students = $conn->query("SELECT s.id, s.roll_number, s.first_name, s.last_name, c.class_name, c.section 
                         FROM students s 
                         LEFT JOIN classes c ON s.class_id = c.id 
                         ORDER BY s.roll_number");

// Get statistics
$total_fees = $conn->query("SELECT SUM(amount) as total FROM fees")->fetch_assoc()['total'] ?? 0;
$paid_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pending_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - Accountant</title>
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
        
        .back-btn {
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
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
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
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #d4edda;
            color: #155724;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
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
        
        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
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
        
        .btn-add {
            padding: 10px 20px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
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
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            background: #27ae60;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .close-btn {
            font-size: 28px;
            color: #999;
            cursor: pointer;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .filter-row {
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
                <h1 class="page-title">💳 Fee Management</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert">
                <span>✓</span>
                <span><?php echo $_GET['msg'] === 'collected' ? 'Payment collected successfully!' : 'Fee added successfully!'; ?></span>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Fees</h3>
                <div class="value">$<?php echo number_format($total_fees, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Collected</h3>
                <div class="value">$<?php echo number_format($paid_fees, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value">$<?php echo number_format($pending_fees, 2); ?></div>
            </div>
        </div>
        
        <div class="filter-card">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="0">All Classes</option>
                            <?php 
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo $class['class_name'] . ' - ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Roll No or Name" value="<?php echo $search; ?>">
                    </div>
                    
                    <button type="submit" class="btn-filter">Filter</button>
                    <button type="button" class="btn-add" onclick="openAddModal()">+ Add Fee</button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fee = $fees->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $fee['roll_number']; ?></td>
                        <td><?php echo $fee['first_name'] . ' ' . $fee['last_name']; ?></td>
                        <td><?php echo $fee['class_name'] . ' - ' . $fee['section']; ?></td>
                        <td><?php echo $fee['fee_type']; ?></td>
                        <td>$<?php echo number_format($fee['amount'], 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $fee['status']; ?>">
                                <?php echo ucfirst($fee['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($fee['status'] === 'pending'): ?>
                                <button class="btn-action" onclick='collectFee(<?php echo json_encode($fee); ?>)'>
                                    Collect
                                </button>
                            <?php else: ?>
                                <span style="color: #27ae60;">✓ Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Collect Fee Modal -->
    <div id="collectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💳 Collect Fee Payment</h2>
                <span class="close-btn" onclick="closeCollectModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="collect_fee" value="1">
                <input type="hidden" name="fee_id" id="collect_fee_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Student</label>
                    <input type="text" id="collect_student" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Fee Type</label>
                    <input type="text" id="collect_fee_type" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Amount</label>
                    <input type="text" id="collect_amount" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Payment Method *</label>
                    <select name="payment_method" required>
                        <option value="">Select Method...</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Transaction ID / Reference</label>
                    <input type="text" name="transaction_id" placeholder="Optional">
                </div>
                
                <button type="submit" class="btn-submit">Confirm Payment</button>
            </form>
        </div>
    </div>
    
    <!-- Add Fee Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Fee</h2>
                <span class="close-btn" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="add_fee" value="1">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Select Student *</label>
                    <select name="student_id" required>
                        <option value="">Choose student...</option>
                        <?php 
                        $students->data_seek(0);
                        while ($student = $students->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo $student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . 
                                     ' (' . $student['class_name'] . '-' . $student['section'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Fee Type *</label>
                    <select name="fee_type" required>
                        <option value="">Choose type...</option>
                        <option value="Tuition Fee">Tuition Fee</option>
                        <option value="Library Fee">Library Fee</option>
                        <option value="Lab Fee">Lab Fee</option>
                        <option value="Sports Fee">Sports Fee</option>
                        <option value="Examination Fee">Examination Fee</option>
                        <option value="Transport Fee">Transport Fee</option>
                        <option value="Development Fee">Development Fee</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Remarks</label>
                    <input type="text" name="remarks" placeholder="Optional notes">
                </div>
                
                <button type="submit" class="btn-submit">Add Fee</button>
            </form>
        </div>
    </div>
    
    <script>
        function collectFee(fee) {
            document.getElementById('collect_fee_id').value = fee.id;
            document.getElementById('collect_student').value = fee.first_name + ' ' + fee.last_name + ' (' + fee.roll_number + ')';
            document.getElementById('collect_fee_type').value = fee.fee_type;
            document.getElementById('collect_amount').value = '$' + parseFloat(fee.amount).toFixed(2);
            document.getElementById('collectModal').style.display = 'block';
        }
        
        function closeCollectModal() {
            document.getElementById('collectModal').style.display = 'none';
        }
        
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const collectModal = document.getElementById('collectModal');
            const addModal = document.getElementById('addModal');
            if (event.target === collectModal) {
                closeCollectModal();
            }
            if (event.target === addModal) {
                closeAddModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>