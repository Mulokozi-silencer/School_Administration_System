<?php
require_once '../config.php';
checkUserType('teacher');

$conn = getDBConnection();

// Get teacher info
$teacher_query = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_query->bind_param("i", $_SESSION['user_id']);
$teacher_query->execute();
$teacher = $teacher_query->get_result()->fetch_assoc();

// Get assigned classes
$classes = $conn->query("SELECT c.*, COUNT(s.id) as student_count 
                        FROM classes c 
                        LEFT JOIN students s ON c.id = s.class_id 
                        WHERE c.teacher_id = {$teacher['id']} 
                        GROUP BY c.id");

// Get today's attendance count
$today = date('Y-m-d');
$attendance_count = $conn->query("SELECT COUNT(*) as count FROM attendance 
                                  WHERE teacher_id = {$teacher['id']} 
                                  AND attendance_date = '$today'")->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .navbar a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-card h2 {
            color: #2ecc71;
            margin-bottom: 10px;
        }
        
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
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2ecc71;
        }
        
        .classes-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .class-card {
            border: 2px solid #e0e0e0;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .class-card:hover {
            border-color: #2ecc71;
            transform: translateY(-3px);
        }
        
        .class-card h3 {
            color: #2ecc71;
            margin-bottom: 15px;
        }
        
        .class-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .menu-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h3 {
            font-size: 18px;
            color: #2ecc71;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🏫 Teacher Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Welcome back, <?php echo $teacher['first_name']; ?>! 👋</h2>
            <p>Subject: <?php echo $teacher['subject']; ?> | Employee ID: <?php echo $teacher['employee_id']; ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Assigned Classes</h3>
                <div class="value"><?php echo $classes->num_rows; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Attendance Marked Today</h3>
                <div class="value"><?php echo $attendance_count; ?></div>
            </div>
        </div>
        
        <div class="classes-section">
            <h2>My Classes</h2>
            <div class="classes-grid">
                <?php while ($class = $classes->fetch_assoc()): ?>
                <div class="class-card">
                    <h3><?php echo $class['class_name'] . ' - Section ' . $class['section']; ?></h3>
                    <div class="class-info">
                        <span>Academic Year:</span>
                        <strong><?php echo $class['academic_year']; ?></strong>
                    </div>
                    <div class="class-info">
                        <span>Total Students:</span>
                        <strong><?php echo $class['student_count']; ?></strong>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="menu-grid">
            <a href="attendance.php" class="menu-card">
                <div class="icon">📋</div>
                <h3>Mark Attendance</h3>
            </a>
            
            <a href="students.php" class="menu-card">
                <div class="icon">👨‍🎓</div>
                <h3>View Students</h3>
            </a>
            
            <a href="my_classes.php" class="menu-card">
                <div class="icon">📚</div>
                <h3>My Classes</h3>
            </a>
        </div>
    </div>
</body>
</html>