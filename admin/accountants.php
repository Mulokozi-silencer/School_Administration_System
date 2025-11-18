<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Set admin id from session (change key if your session uses a different name)
$admin_id = $_SESSION['user_id']; // or $_SESSION['admin_id'] if you store admin_id separately

// Count unread notifications (use prepared statement to be safe)
$stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE receiver_id = ? AND status = 'unread'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$countData = $result->fetch_assoc();
$unreadCount = $countData['unread_count'] ?? 0;
$stmt->close();

// Fetch latest 5 notifications
$stmt2 = $conn->prepare("SELECT n.id, n.message, n.status, n.created_at, u.username AS sender_name
                         FROM notifications n
                         LEFT JOIN users u ON n.sender_id = u.id
                         WHERE n.receiver_id = ?
                         ORDER BY n.created_at DESC
                         LIMIT 5");
$stmt2->bind_param("i", $admin_id);
$stmt2->execute();
$notifResult = $stmt2->get_result();

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'];
$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
$total_subjects = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];

// Financial statistics
$total_fees = $conn->query("SELECT SUM(amount) as total FROM fees")->fetch_assoc()['total'] ?? 0;
$paid_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pending_fees = $conn->query("SELECT SUM(amount) as total FROM fees WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

// Attendance statistics for today
$today = date('Y-m-d');
$today_attendance = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance WHERE attendance_date = '$today'")->fetch_assoc();

// Recent activities
$recent_students = $conn->query("SELECT s.*, c.class_name, c.section FROM students s 
                                LEFT JOIN classes c ON s.class_id = c.id 
                                ORDER BY s.created_at DESC LIMIT 5");

$recent_fees = $conn->query("SELECT f.*, s.first_name, s.last_name, s.roll_number 
                            FROM fees f 
                            JOIN students s ON f.student_id = s.id 
                            WHERE f.status = 'pending'
                            ORDER BY f.due_date ASC LIMIT 5");

$conn->close();

// Handle password reset
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
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
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND user_type != 'admin'");
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            $message = "Password reset successfully!";
            $message_type = 'success';
        } else {
            $message = "Error resetting password!";
            $message_type = 'error';
        }
        $stmt->close();
        $conn->close();
    }
}

// Fetch users for reset
$conn = getDBConnection();
$users_query = "SELECT u.id, u.username, u.user_type,
CASE u.user_type
WHEN 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
WHEN 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
WHEN 'accountant' THEN CONCAT(a.first_name, ' ', a.last_name)
END AS full_name
FROM users u
LEFT JOIN students s ON u.id = s.user_id AND u.user_type = 'student'
LEFT JOIN teachers t ON u.id = t.user_id AND u.user_type = 'teacher'
LEFT JOIN accountants a ON u.id = a.user_id AND u.user_type = 'accountant'
WHERE u.user_type != 'admin'
ORDER BY full_name";
$users = $conn->query($users_query);
$conn->close();

$attendance_rate = $today_attendance['total'] > 0
    ? round(($today_attendance['present'] / $today_attendance['total']) * 100, 1)
    : 0;

$collection_rate = $total_fees > 0
    ? round(($paid_fees / $total_fees) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Administration</title>
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
        
        .notification-icon {
            position: relative;
            cursor: pointer;
            font-size: 24px;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.3s;
        }
        
        .notification-icon:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
            color: #667eea;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #28a745;
        }
        
        .stat-footer.negative {
            color: #dc3545;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .action-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .action-card h4 {
            font-size: 15px;
            font-weight: 600;
        }
        
        /* Recent Activities */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-list {
            list-style: none;
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
        
        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
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
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
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
                <span class="icon">🎓</span>
                <div class="logo-text">
                    <h1>EduManage</h1>
                    <p>School Administration System</p>
                </div>
            </div>
            
    <div class="user-section">
    <div class="notification-icon" onclick="toggleNotifications()">
        🔔
        <span class="notification-badge"><?php echo $unreadCount; ?></span>
    </div>

    <div id="notification-box" class="notification-box" style="display:none;">
        <?php if (mysqli_num_rows($notifResult) > 0): ?>
            <ul>
                <?php while($row = mysqli_fetch_assoc($notifResult)): ?>
                    <li>
                        <strong><?php echo $row['message']; ?></strong><br>
                        <small><?php echo $row['created_at']; ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No new notifications.</p>
        <?php endif; ?>
    </div>

    <div class="user-info">
        <div class="user-avatar">A</div>
        <div class="user-details">
            <div class="user-name"><?php echo $_SESSION['username']; ?></div>
            <div class="user-role">Administrator</div>
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
                    <a href="students.php">
                        <span class="menu-icon">👨‍🎓</span>
                        <span>Students</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="teachers.php">
                        <span class="menu-icon">👨‍🏫</span>
                        <span>Teachers</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="accountants.php">
                        <span class="menu-icon">👨‍🏫</span>
                        <span>Accountants</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="classes.php">
                        <span class="menu-icon">📚</span>
                        <span>Classes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="subjects.php">
                        <span class="menu-icon">📖</span>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="attendance.php">
                        <span class="menu-icon">📋</span>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="fees.php">
                        <span class="menu-icon">💰</span>
                        <span>Fees Management</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reports.php">
                        <span class="menu-icon">📈</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="settings.php">
                        <span class="menu-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2>Welcome back, Admin! 👋</h2>
                <p>Here's what's happening in your school today</p>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>" style="background: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid <?php echo $message_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
                    <strong><?php echo $message_type === 'success' ? '✓' : '⚠️'; ?></strong> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value"><?php echo $total_students; ?></div>
                            <div class="stat-footer">
                                <span>↑ 5%</span> from last month
                            </div>
                        </div>
                        <div class="stat-icon">👨‍🎓</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Teachers</div>
                            <div class="stat-value"><?php echo $total_teachers; ?></div>
                            <div class="stat-footer">
                                <span>→ 0%</span> no change
                            </div>
                        </div>
                        <div class="stat-icon">👨‍🏫</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Classes</div>
                            <div class="stat-value"><?php echo $total_classes; ?></div>
                            <div class="stat-footer">
                                <span>↑ 2</span> new classes
                            </div>
                        </div>
                        <div class="stat-icon">📚</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Subjects</div>
                            <div class="stat-value"><?php echo $total_subjects; ?></div>
                            <div class="stat-footer">
                                <span>→ 0%</span> no change
                            </div>
                        </div>
                        <div class="stat-icon">📖</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Today's Attendance</div>
                            <div class="stat-value"><?php echo $attendance_rate; ?>%</div>
                            <div class="stat-footer">
                                <?php echo $today_attendance['present']; ?> present, <?php echo $today_attendance['absent']; ?> absent
                            </div>
                        </div>
                        <div class="stat-icon">📋</div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $attendance_rate; ?>%"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Fee Collection</div>
                            <div class="stat-value"><?php echo $collection_rate; ?>%</div>
                            <div class="stat-footer">
                                $<?php echo number_format($paid_fees, 0); ?> / $<?php echo number_format($total_fees, 0); ?>
                            </div>
                        </div>
                        <div class="stat-icon">💰</div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $collection_rate; ?>%"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Pending Fees</div>
                            <div class="stat-value">$<?php echo number_format($pending_fees, 0); ?></div>
                            <div class="stat-footer negative">
                                <span>↓</span> needs attention
                            </div>
                        </div>
                        <div class="stat-icon">⚠️</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">$<?php echo number_format($paid_fees, 0); ?></div>
                            <div class="stat-footer">
                                <span>↑ 12%</span> from last month
                            </div>
                        </div>
                        <div class="stat-icon">💵</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                <div class="actions-grid">
                    <a href="students.php" class="action-card">
                        <div class="action-icon">➕</div>
                        <h4>Add Student</h4>
                    </a>
                    <a href="teachers.php" class="action-card">
                        <div class="action-icon">👥</div>
                        <h4>Add Teacher</h4>
                    </a>
                    <a href="classes.php" class="action-card">
                        <div class="action-icon">🏫</div>
                        <h4>Create Class</h4>
                    </a>
                    <a href="attendance.php" class="action-card">
                        <div class="action-icon">✓</div>
                        <h4>Mark Attendance</h4>
                    </a>
                    <a href="fees.php" class="action-card">
                        <div class="action-icon">💳</div>
                        <h4>Collect Fee</h4>
                    </a>
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">📄</div>
                        <h4>Generate Report</h4>
                    </a>
                    <div class="action-card" onclick="openResetModal()">
                        <div class="action-icon">🔑</div>
                        <h4>Reset Password</h4>
                    </div>
                </div>
            </div>

            <!-- Password Reset Modal -->
            <div id="resetModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
                <div class="modal-content" style="background: white; width: 90%; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 15px; max-height: 90vh; overflow-y: auto;">
                    <h2 style="margin-bottom: 20px; color: #333;">Reset User Password</h2>

                    <div style="margin-bottom: 20px;">
                        <input type="text" id="searchUser" placeholder="Search user by name..." style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px;" onkeyup="filterUsers()">
                    </div>

                    <div id="userList" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                        <?php $users->data_seek(0); while ($user = $users->fetch_assoc()): ?>
                            <div class="user-item" data-name="<?php echo strtolower($user['full_name']); ?>" style="padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 0.3s;" onclick="selectUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo $user['user_type']; ?>')">
                                <div style="font-weight: 600; color: #333;"><?php echo $user['full_name']; ?></div>
                                <div style="font-size: 13px; color: #666;"><?php echo ucfirst($user['user_type']); ?> - <?php echo $user['username']; ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <form method="POST" id="resetForm" style="display: none;">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="user_id" id="selectedUserId">

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Selected User:</label>
                            <div id="selectedUserInfo" style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-weight: 600;"></div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="new_password" style="display: block; margin-bottom: 5px; font-weight: 500;">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label for="confirm_password" style="display: block; margin-bottom: 5px; font-weight: 500;">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px;">
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer;">Reset Password</button>
                            <button type="button" onclick="closeResetModal()" style="padding: 12px 20px; background: #6c757d; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer;">Cancel</button>
                        </div>
                    </form>

                    <div id="userSelectionPrompt" style="text-align: center; color: #666; padding: 20px;">
                        Please select a user from the list above to reset their password.
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="dashboard-grid">
                <div class="activity-card">
                    <div class="section-header">
                        <h3>🆕 Recent Students</h3>
                        <a href="students.php" style="color: #667eea; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <ul class="activity-list">
                        <?php while ($student = $recent_students->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-title">
                                <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                            </div>
                            <div class="activity-meta">
                                <span><?php echo $student['roll_number']; ?></span>
                                <span><?php echo $student['class_name'] . ' - ' . $student['section']; ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                
                <div class="activity-card">
                    <div class="section-header">
                        <h3>💰 Pending Fees</h3>
                        <a href="fees.php" style="color: #667eea; text-decoration: none; font-size: 14px;">View All →</a>
                    </div>
                    <ul class="activity-list">
                        <?php while ($fee = $recent_fees->fetch_assoc()): ?>
                        <li class="activity-item">
                            <div class="activity-title">
                                <?php echo $fee['first_name'] . ' ' . $fee['last_name']; ?>
                            </div>
                            <div class="activity-meta">
                                <span>$<?php echo number_format($fee['amount'], 2); ?></span>
                                <span class="status-badge status-pending">Due: <?php echo date('M d', strtotime($fee['due_date'])); ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script>
function toggleNotifications() {
    const box = document.getElementById('notification-box');
    box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
}

function openResetModal() {
    document.getElementById('resetModal').style.display = 'block';
    document.getElementById('resetForm').style.display = 'none';
    document.getElementById('userSelectionPrompt').style.display = 'block';
}

function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
    document.getElementById('searchUser').value = '';
    filterUsers();
}

function filterUsers() {
    const searchTerm = document.getElementById('searchUser').value.toLowerCase();
    const userItems = document.querySelectorAll('.user-item');

    userItems.forEach(item => {
        const name = item.getAttribute('data-name');
        if (name.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function selectUser(userId, fullName, userType) {
    document.getElementById('selectedUserId').value = userId;
    document.getElementById('selectedUserInfo').innerHTML = fullName + ' (' + userType + ')';
    document.getElementById('resetForm').style.display = 'block';
    document.getElementById('userSelectionPrompt').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('resetModal');
    if (event.target == modal) {
        closeResetModal();
    }
}
</script>
</body>
</html>
