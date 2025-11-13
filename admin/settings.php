<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_role') {
        $teacher_id = intval($_POST['teacher_id']);
        $role = sanitize($_POST['role']);

        $stmt = $conn->prepare("UPDATE teachers SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $teacher_id);
        $stmt->execute();

        header('Location: settings.php?msg=role_assigned');
        exit();
    }
}

// Fetch all teachers with their roles
$teachers = $conn->query("SELECT t.*, u.username, u.status FROM teachers t
                         LEFT JOIN users u ON t.user_id = u.id
                         ORDER BY t.role DESC, t.first_name ASC");

// Get role statistics
$role_stats = $conn->query("SELECT role, COUNT(*) as count FROM teachers GROUP BY role")->fetch_all(MYSQLI_ASSOC);
$role_counts = array_column($role_stats, 'count', 'role');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
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

        /* Role Statistics */
        .role-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .role-icon {
            font-size: 48px;
            opacity: 0.9;
        }

        .role-info h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }

        .role-info .count {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        /* Settings Sections */
        .settings-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            font-size: 18px;
            font-weight: 600;
        }

        .section-content {
            padding: 25px;
        }

        /* Role Assignment */
        .role-assignment {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .teacher-role-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }

        .teacher-role-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
        }

        .teacher-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .teacher-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: bold;
        }

        .teacher-info h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .teacher-info p {
            font-size: 14px;
            color: #666;
        }

        .current-role {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .role-teacher {
            color: #28a745;
            background: #d4edda;
        }

        .role-head_of_department {
            color: #fd7e14;
            background: #ffeaa7;
        }

        .role-admin {
            color: #dc3545;
            background: #f8d7da;
        }

        .role-select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .role-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-assign {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-assign:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
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

        @media (max-width: 768px) {
            .role-assignment {
                grid-template-columns: 1fr;
            }

            .role-stats {
                grid-template-columns: 1fr;
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
                <h1 class="page-title">⚙️ Settings</h1>
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
                    echo $_GET['msg'] === 'role_assigned' ? 'Role assigned successfully!' : '';
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Role Statistics -->
        <div class="role-stats">
            <div class="role-card">
                <div class="role-icon">👨‍🏫</div>
                <div class="role-info">
                    <h3>Teachers</h3>
                    <div class="count"><?php echo $role_counts['teacher'] ?? 0; ?></div>
                </div>
            </div>
            <div class="role-card">
                <div class="role-icon">👔</div>
                <div class="role-info">
                    <h3>Heads of Department</h3>
                    <div class="count"><?php echo $role_counts['head_of_department'] ?? 0; ?></div>
                </div>
            </div>
            <div class="role-card">
                <div class="role-icon">👑</div>
                <div class="role-info">
                    <h3>Admins</h3>
                    <div class="count"><?php echo $role_counts['admin'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Role Assignment Section -->
        <div class="settings-section">
            <div class="section-header">
                👥 Role Assignment
            </div>
            <div class="section-content">
                <p style="color: #666; margin-bottom: 20px;">
                    Assign roles to teachers. Heads of Department have additional privileges, while Admins have full system access.
                </p>

                <div class="role-assignment">
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <div class="teacher-role-card">
                        <span class="status-badge status-<?php echo $teacher['status']; ?>">
                            <?php echo ucfirst($teacher['status']); ?>
                        </span>

                        <div class="teacher-header">
                            <div class="teacher-avatar">
                                <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                            </div>
                            <div class="teacher-info">
                                <h4><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h4>
                                <p><?php echo $teacher['employee_id']; ?> • <?php echo $teacher['subject']; ?></p>
                            </div>
                        </div>

                        <div class="current-role role-<?php echo $teacher['role']; ?>">
                            Current: <?php echo ucwords(str_replace('_', ' ', $teacher['role'])); ?>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="assign_role">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">

                            <select name="role" class="role-select" onchange="this.form.submit()">
                                <option value="teacher" <?php echo $teacher['role'] === 'teacher' ? 'selected' : ''; ?>>
                                    👨‍🏫 Teacher
                                </option>
                                <option value="head_of_department" <?php echo $teacher['role'] === 'head_of_department' ? 'selected' : ''; ?>>
                                    👔 Head of Department
                                </option>
                                <option value="admin" <?php echo $teacher['role'] === 'admin' ? 'selected' : ''; ?>>
                                    👑 Admin
                                </option>
                            </select>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
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
