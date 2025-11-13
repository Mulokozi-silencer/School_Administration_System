<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle teacher operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new teacher
            $username = sanitize($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $employee_id = sanitize($_POST['employee_id']);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $subject = sanitize($_POST['subject']);
            $qualification = sanitize($_POST['qualification']);
            $hire_date = sanitize($_POST['hire_date']);
            
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, password, user_type) VALUES (?, ?, 'teacher')");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $user_id = $conn->insert_id;
            
            // Insert into teachers table
            $stmt = $conn->prepare("INSERT INTO teachers (user_id, employee_id, first_name, last_name, email, phone, subject, qualification, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $user_id, $employee_id, $first_name, $last_name, $email, $phone, $subject, $qualification, $hire_date);
            $stmt->execute();
            
            header('Location: teachers.php?msg=added');
            exit();
        } 
        elseif ($_POST['action'] === 'edit') {
            // Edit teacher
            $teacher_id = intval($_POST['teacher_id']);
            $employee_id = sanitize($_POST['employee_id']);
            $first_name = sanitize($_POST['first_name']);
            $last_name = sanitize($_POST['last_name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $subject = sanitize($_POST['subject']);
            $qualification = sanitize($_POST['qualification']);
            $hire_date = sanitize($_POST['hire_date']);
            
            $stmt = $conn->prepare("UPDATE teachers SET employee_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, subject = ?, qualification = ?, hire_date = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $employee_id, $first_name, $last_name, $email, $phone, $subject, $qualification, $hire_date, $teacher_id);
            $stmt->execute();
            
            header('Location: teachers.php?msg=updated');
            exit();
        }
        elseif ($_POST['action'] === 'delete') {
            $teacher_id = intval($_POST['teacher_id']);
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            
            header('Location: teachers.php?msg=deleted');
            exit();
        }
        elseif ($_POST['action'] === 'reset_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $user_id);
            $stmt->execute();
            
            header('Location: teachers.php?msg=password_reset');
            exit();
        }
        elseif ($_POST['action'] === 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            $status = sanitize($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $user_id);
            $stmt->execute();
            
            header('Location: teachers.php?msg=status_updated');
            exit();
        }
    }
}

// Fetch all teachers with user info and class count
$teachers = $conn->query("SELECT t.*, u.username, u.status,
                         (SELECT COUNT(*) FROM classes WHERE teacher_id = t.id) as class_count
                         FROM teachers t
                         LEFT JOIN users u ON t.user_id = u.id
                         ORDER BY t.role DESC, t.first_name ASC");

// Get statistics
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$active_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers t 
                                JOIN users u ON t.user_id = u.id 
                                WHERE u.status = 'active'")->fetch_assoc()['count'];
$inactive_teachers = $total_teachers - $active_teachers;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management - Admin</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
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
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        /* Statistics Cards */
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
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            font-size: 48px;
            opacity: 0.9;
        }
        
        .stat-info h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .stat-info .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .search-box {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            min-width: 300px;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Teachers Grid */
        .teachers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .teacher-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
        }
        
        .teacher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .teacher-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .teacher-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .teacher-info {
            flex: 1;
        }
        
        .teacher-name {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .teacher-id {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .teacher-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .detail-row .icon {
            font-size: 18px;
            width: 25px;
        }
        
        .teacher-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-item .number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-item .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .teacher-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .btn-action {
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit:hover {
            background: #1976d2;
            color: white;
        }
        
        .btn-password {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-password:hover {
            background: #856404;
            color: white;
        }
        
        .btn-toggle {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-toggle:hover {
            background: #28a745;
            color: white;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        
        .btn-delete:hover {
            background: #c62828;
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .close-btn {
            font-size: 28px;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-cancel {
            width: 100%;
            padding: 15px;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .teachers-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn">
                    <span>←</span> Dashboard
                </a>
                <h1 class="page-title">👨‍🏫 Teacher Management</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span>
                    <?php 
                    echo $_GET['msg'] === 'added' ? 'Teacher added successfully!' : 
                         ($_GET['msg'] === 'updated' ? 'Teacher updated successfully!' : 
                         ($_GET['msg'] === 'deleted' ? 'Teacher deleted successfully!' : 
                         ($_GET['msg'] === 'password_reset' ? 'Password reset successfully!' : 
                         ($_GET['msg'] === 'status_updated' ? 'Status updated successfully!' : ''))));
                    ?>
                </span>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-info">
                    <h3>Total Teachers</h3>
                    <div class="value"><?php echo $total_teachers; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Active Teachers</h3>
                    <div class="value"><?php echo $active_teachers; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏸️</div>
                <div class="stat-info">
                    <h3>Inactive Teachers</h3>
                    <div class="value"><?php echo $inactive_teachers; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Header -->
        <div class="header">
            <h2>All Teachers</h2>
            <div class="header-actions">
                <input type="text" class="search-box" placeholder="🔍 Search teachers..." id="searchBox" onkeyup="searchTeachers()">
                <button class="btn-add" onclick="openAddModal()">
                    <span>+</span> Add New Teacher
                </button>
            </div>
        </div>
        
        <!-- Teachers Grid -->
        <div class="teachers-grid" id="teachersGrid">
            <?php while ($teacher = $teachers->fetch_assoc()): ?>
            <div class="teacher-card" data-teacher="<?php echo strtolower($teacher['first_name'] . ' ' . $teacher['last_name'] . ' ' . $teacher['employee_id']); ?>">
                <div class="teacher-header">
                    <div class="teacher-avatar">
                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                    </div>
                    <div class="teacher-info">
                        <div class="teacher-name"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></div>
                        <div class="teacher-id">ID: <?php echo $teacher['employee_id']; ?></div>
                        <span class="status-badge status-<?php echo $teacher['status']; ?>">
                            <?php echo ucfirst($teacher['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="teacher-details">
                    <div class="detail-row">
                        <span class="icon">📧</span>
                        <span><?php echo $teacher['email'] ?: 'Not provided'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="icon">📱</span>
                        <span><?php echo $teacher['phone'] ?: 'Not provided'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="icon">📚</span>
                        <span><?php echo $teacher['subject']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="icon">🎓</span>
                        <span><?php echo $teacher['qualification']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="icon">👤</span>
                        <span>Username: <?php echo $teacher['username']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="icon">🏷️</span>
                        <span>Role: <?php echo ucwords(str_replace('_', ' ', $teacher['role'])); ?></span>
                    </div>
                </div>
                
                <div class="teacher-stats">
                    <div class="stat-item">
                        <div class="number"><?php echo $teacher['class_count']; ?></div>
                        <div class="label">Classes</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo date('Y') - date('Y', strtotime($teacher['hire_date'])); ?></div>
                        <div class="label">Years</div>
                    </div>
                </div>
                
                <div class="teacher-actions">
                    <button class="btn-action btn-email"
    data-id="<?php echo htmlspecialchars($teacher['user_id']); ?>"
    data-email="<?php echo htmlspecialchars($teacher['email']); ?>">
    <span>✏️</span> Edit
</button>

                    <button class="btn-action btn-password" onclick="openPasswordModal(<?php echo $teacher['user_id']; ?>, '<?php echo $teacher['first_name']; ?>')">
                        <span>🔑</span> Reset
                    </button>
                    <button class="btn-action btn-toggle" onclick="toggleStatus(<?php echo $teacher['user_id']; ?>, '<?php echo $teacher['status']; ?>')">
                        <span><?php echo $teacher['status'] === 'active' ? '⏸️' : '✅'; ?></span> 
                        <?php echo $teacher['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteTeacher(<?php echo $teacher['id']; ?>, '<?php echo $teacher['first_name']; ?>')">
                        <span>🗑️</span> Delete
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Add Teacher Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Teacher</h2>
                <span class="close-btn" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Employee ID *</label>
                    <input type="text" name="employee_id" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="edit_phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" name="subject" id="edit_subject" required>
                    </div>
                    <div class="form-group">
                        <label>Hire Date</label>
                        <input type="date" name="hire_date" id="edit_hire_date">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Qualification</label>
                    <input type="text" name="qualification" id="edit_qualification">
                </div>
                
                <button type="submit" class="btn-submit">Add Teacher</button>
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🔑 Reset Password</h2>
                <span class="close-btn" onclick="closePasswordModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="password_user_id">
                
                <div class="form-group">
                    <label>Teacher Name</label>
                    <input type="text" id="password_teacher_name" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="confirm_password" required placeholder="Re-enter password">
                </div>
                
                <div id="password_error" style="color: #c62828; font-size: 14px; margin-bottom: 15px; display: none;">
                    Passwords do not match!
                </div>
                
                <button type="submit" class="btn-submit" onclick="return validatePassword()">Reset Password</button>
                <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Toggle Status Form (Hidden) -->
    <form id="toggleStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="user_id" id="toggle_user_id">
        <input type="hidden" name="status" id="toggle_status">
    </form>
    
    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="teacher_id" id="delete_teacher_id">
    </form>
    
    <script>
        // Add Teacher Modal
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        // Edit Teacher Modal
        function openEditModal(teacher) {
            document.getElementById('edit_teacher_id').value = teacher.id;
            document.getElementById('edit_first_name').value = teacher.first_name;
            document.getElementById('edit_last_name').value = teacher.last_name;
            document.getElementById('edit_employee_id').value = teacher.employee_id;
            document.getElementById('edit_email').value = teacher.email || '';
            document.getElementById('edit_phone').value = teacher.phone || '';
            document.getElementById('edit_subject').value = teacher.subject;
            document.getElementById('edit_qualification').value = teacher.qualification || '';
            document.getElementById('edit_hire_date').value = teacher.hire_date;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openEditModal(userId, email) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editEmail').value = email;
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Reset Password Modal
        function openPasswordModal(userId, teacherName) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_teacher_name').value = teacherName;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('password_error').style.display = 'none';
            
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }
        
        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('password_error');
            
            if (newPassword !== confirmPassword) {
                errorDiv.style.display = 'block';
                return false;
            }
            
            if (newPassword.length < 6) {
                errorDiv.textContent = 'Password must be at least 6 characters long!';
                errorDiv.style.display = 'block';
                return false;
            }
            
            return confirm('Are you sure you want to reset the password for this teacher?');
        }
        
        // Toggle Status
        function toggleStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${action} this teacher?`)) {
                document.getElementById('toggle_user_id').value = userId;
                document.getElementById('toggle_status').value = newStatus;
                document.getElementById('toggleStatusForm').submit();
            }
        }
        
        // Delete Teacher
        function deleteTeacher(teacherId, teacherName) {
            if (confirm(`Are you sure you want to delete ${teacherName}? This action cannot be undone and will remove all associated data.`)) {
                document.getElementById('delete_teacher_id').value = teacherId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Search Teachers
        function searchTeachers() {
            const searchText = document.getElementById('searchBox').value.toLowerCase();
            const teacherCards = document.querySelectorAll('.teacher-card');
            
            teacherCards.forEach(card => {
                const teacherData = card.getAttribute('data-teacher');
                if (teacherData.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            const passwordModal = document.getElementById('passwordModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === passwordModal) {
                closePasswordModal();
            }
        }
        
        // Real-time password match validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const errorDiv = document.getElementById('password_error');
            
            if (confirmPassword && newPassword !== confirmPassword) {
                errorDiv.style.display = 'block';
                this.style.borderColor = '#c62828';
            } else {
                errorDiv.style.display = 'none';
                this.style.borderColor = '#e0e0e0';
            }
        });

        document.querySelectorAll('.btn-email').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.dataset.id;
        const email = button.dataset.email;
        openEditModal(id, email);
    });
});

        
        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>
