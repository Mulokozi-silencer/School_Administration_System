<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle fee operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add Fee
        if ($_POST['action'] === 'add') {
            $student_id = intval($_POST['student_id']);
            $fee_type = sanitize($_POST['fee_type']);
            $amount = floatval($_POST['amount']);
            $due_date = sanitize($_POST['due_date']);
            $admin_id = $_SESSION['user_id'];

            $stmt = $conn->prepare("INSERT INTO fees (student_id, fee_type, amount, due_date, added_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsi", $student_id, $fee_type, $amount, $due_date, $admin_id);
            $stmt->execute();

            header('Location: fees.php?msg=added');
            exit();
        }

        // Edit Fee
        elseif ($_POST['action'] === 'edit') {
            $fee_id = intval($_POST['fee_id_edit']);
            $fee_type = sanitize($_POST['fee_type_edit']);
            $amount = floatval($_POST['amount_edit']);
            $due_date = sanitize($_POST['due_date_edit']);

            $stmt = $conn->prepare("UPDATE fees SET fee_type=?, amount=?, due_date=? WHERE id=?");
            $stmt->bind_param("sdsi", $fee_type, $amount, $due_date, $fee_id);
            $stmt->execute();

            header('Location: fees.php?msg=updated');
            exit();
        }

        // Delete Fee
        elseif ($_POST['action'] === 'delete') {
            $fee_id = intval($_POST['fee_id']);
            $conn->query("DELETE FROM fees WHERE id = $fee_id");
            header('Location: fees.php?msg=deleted');
            exit();
        }

        // Mark Paid
        elseif ($_POST['action'] === 'mark_paid') {
            $fee_id = intval($_POST['fee_id']);
            $payment_method = sanitize($_POST['payment_method']);
            $transaction_id = sanitize($_POST['transaction_id']);
            $payment_date = date('Y-m-d');

            $stmt = $conn->prepare("UPDATE fees SET status='paid', payment_date=?, payment_method=?, transaction_id=? WHERE id=?");
            $stmt->bind_param("sssi", $payment_date, $payment_method, $transaction_id, $fee_id);
            $stmt->execute();

            header('Location: fees.php?msg=paid');
            exit();
        }
    }
}

// Filters
$where = [];
if (!empty($_GET['filter_class'])) $where[] = "s.class_id=" . intval($_GET['filter_class']);
if (!empty($_GET['filter_status'])) $where[] = "f.status='" . sanitize($_GET['filter_status']) . "'";
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get all fees with student info
$fees = $conn->query("SELECT f.*, s.first_name, s.last_name, s.roll_number, c.class_name, c.section, u.username as added_by
                      FROM fees f
                      JOIN students s ON f.student_id = s.id
                      LEFT JOIN classes c ON s.class_id = c.id
                      LEFT JOIN users u ON f.added_by = u.id
                      $whereSql
                      ORDER BY f.due_date DESC");

// Get students for dropdown
$students = $conn->query("SELECT s.id, s.first_name, s.last_name, s.roll_number, c.class_name, c.section
                          FROM students s
                          LEFT JOIN classes c ON s.class_id = c.id
                          ORDER BY s.roll_number");

// Get classes for filter
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");

// Get statistics
$total_fees = $conn->query("SELECT SUM(amount) as total FROM fees")->fetch_assoc()['total'];
$paid_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status='paid'")->fetch_assoc()['total'];
$pending_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status='pending'")->fetch_assoc()['total'];
?>

<style>
/* Reset and basic styling */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }

/* Navbar */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.navbar a {
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    margin-left: 10px;
}

/* Container */
.container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    background: #d4edda;
    color: #155724;
}

/* Statistics cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.stat-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
.stat-card .value { font-size: 32px; font-weight: bold; color: #667eea; }

/* Header and buttons */
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.btn-add { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }

/* Table styling */
.table-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
}
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; font-weight: 600; }

/* Status badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-paid { background: #d4edda; color: #155724; }
.status-overdue { background: #f8d7da; color: #721c24; }

/* Admin badge */
.admin-badge {
    background: #17a2b8;
    color: white;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}

/* Action buttons */
.btn-action {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    color: white;
    background: #28a745;
    margin-right: 5px;
}
.btn-action[style*="background:#dc3545"] { background: #dc3545; }

/* Modal styling */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}
.modal-content {
    background: white;
    width: 90%; max-width: 500px;
    margin: 50px auto;
    padding: 30px;
    border-radius: 10px;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
.form-group input, .form-group select {
    width: 100%; padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

/* Modal buttons */
.btn-submit {
    background: #667eea;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    margin-top: 5px;
}
.btn-close {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
}
</style>
</head>
<body>
<nav class="navbar">
    <h1>💰 Fees Management (Admin)</h1>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <?php
                echo $_GET['msg'] === 'added' ? 'Fee added successfully!' :
                     ($_GET['msg'] === 'updated' ? 'Fee updated successfully!' :
                     ($_GET['msg'] === 'paid' ? 'Payment marked successfully!' :
                     ($_GET['msg'] === 'deleted' ? 'Fee deleted successfully!' : '')));
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card"><h3>Total Fees</h3><div class="value">$<?php echo number_format($total_fees,2); ?></div></div>
        <div class="stat-card"><h3>Paid Fees</h3><div class="value">$<?php echo number_format($paid_fees,2); ?></div></div>
        <div class="stat-card"><h3>Pending Fees</h3><div class="value">$<?php echo number_format($pending_fees,2); ?></div></div>
    </div>

    <!-- Filters -->
    <form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
        <select name="filter_class">
            <option value="">All Classes</option>
            <?php while($cls = $classes->fetch_assoc()): ?>
                <option value="<?php echo $cls['id']; ?>" <?php if(!empty($_GET['filter_class']) && $_GET['filter_class']==$cls['id']) echo 'selected'; ?>>
                    <?php echo $cls['class_name']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="filter_status">
            <option value="">All Status</option>
            <option value="paid" <?php if(!empty($_GET['filter_status']) && $_GET['filter_status']=='paid') echo 'selected'; ?>>Paid</option>
            <option value="pending" <?php if(!empty($_GET['filter_status']) && $_GET['filter_status']=='pending') echo 'selected'; ?>>Pending</option>
        </select>
        <button type="submit" class="btn-add">Filter</button>
    </form>

    <div class="header">
        <h2>Fee Records</h2>
        <button class="btn-add" onclick="openAddModal()">+ Add New Fee</button>
    </div>

    <!-- Fee Table -->
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
                    <th>Added By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fee = $fees->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $fee['roll_number']; ?></td>
                    <td><?php echo $fee['first_name'].' '.$fee['last_name']; ?></td>
                    <td><?php echo $fee['class_name'].'-'.$fee['section']; ?></td>
                    <td><?php echo $fee['fee_type']; ?></td>
                    <td>$<?php echo number_format($fee['amount'],2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $fee['status']; ?>">
                            <?php echo ucfirst($fee['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $fee['added_by']; ?></td>
                    <td>
                        <?php if($fee['status']=='pending'): ?>
                            <button class="btn-action" onclick="openPaymentModal(<?php echo $fee['id']; ?>)">Mark Paid</button>
                        <?php else: ?>
                            <span style="color:#28a745;">✓ Paid</span>
                        <?php endif; ?>
                        <button class="btn-action" onclick="openEditModal(<?php echo $fee['id']; ?>)">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="fee_id" value="<?php echo $fee['id']; ?>">
                            <button type="submit" class="btn-action" style="background:#dc3545;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Fee Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h2>Add New Fee</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Select Student *</label>
                <select name="student_id" required>
                    <option value="">Choose student...</option>
                    <?php $students->data_seek(0); while($s=$students->fetch_assoc()): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['roll_number'].' - '.$s['first_name'].' '.$s['last_name'].' ('.$s['class_name'].'-'.$s['section'].')'; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fee Type *</label>
                <select name="fee_type" required>
                    <option value="">Choose type...</option>
                    <option value="Tuition Fee">Tuition Fee</option>
                    <option value="Library Fee">Library Fee</option>
                    <option value="Lab Fee">Lab Fee</option>
                    <option value="Sports Fee">Sports Fee</option>
                    <option value="Examination Fee">Examination Fee</option>
                    <option value="Transport Fee">Transport Fee</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="amount" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Due Date *</label>
                <input type="date" name="due_date" required>
            </div>
            <button type="submit" class="btn-submit">Add Fee</button>
            <button type="button" class="btn-close" onclick="closeAddModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <h2>Mark Payment</h2>
        <form method="POST">
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="fee_id" id="payment_fee_id">
            <div class="form-group">
                <label>Payment Method *</label>
                <select name="payment_method" required>
                    <option value="">Choose method...</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="Online Payment">Online Payment</option>
                </select>
            </div>
            <div class="form-group">
                <label>Transaction ID</label>
                <input type="text" name="transaction_id">
            </div>
            <button type="submit" class="btn-submit">Confirm Payment</button>
            <button type="button" class="btn-close" onclick="closePaymentModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2>Edit Fee</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="fee_id_edit" id="edit_fee_id">
            <div class="form-group">
                <label>Fee Type *</label>
                <select name="fee_type_edit" id="edit_fee_type" required>
                    <option value="Tuition Fee">Tuition Fee</option>
                    <option value="Library Fee">Library Fee</option>
                    <option value="Lab Fee">Lab Fee</option>
                    <option value="Sports Fee">Sports Fee</option>
                    <option value="Examination Fee">Examination Fee</option>
                    <option value="Transport Fee">Transport Fee</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="amount_edit" step="0.01" min="0" id="edit_amount" required>
            </div>
            <div class="form-group">
                <label>Due Date *</label>
                <input type="date" name="due_date_edit" id="edit_due_date" required>
            </div>
            <button type="submit" class="btn-submit">Update Fee</button>
            <button type="button" class="btn-close" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openAddModal(){document.getElementById('addModal').style.display='block'}
function closeAddModal(){document.getElementById('addModal').style.display='none'}
function openPaymentModal(feeId){document.getElementById('payment_fee_id').value=feeId;document.getElementById('paymentModal').style.display='block'}
function closePaymentModal(){document.getElementById('paymentModal').style.display='none'}
function openEditModal(feeId){
    fetch('get_fee.php?id='+feeId).then(res=>res.json()).then(fee=>{
        document.getElementById('edit_fee_id').value=fee.id;
        document.getElementById('edit_fee_type').value=fee.fee_type;
        document.getElementById('edit_amount').value=fee.amount;
        document.getElementById('edit_due_date').value=fee.due_date;
        document.getElementById('editModal').style.display='block';
    });
}
function closeEditModal(){document.getElementById('editModal').style.display='none'}
window.onclick=function(event){
    if(event.target==document.getElementById('addModal'))closeAddModal();
    if(event.target==document.getElementById('paymentModal'))closePaymentModal();
    if(event.target==document.getElementById('editModal'))closeEditModal();
}
</script>
</body>
</html>
<?php $conn->close(); ?>
