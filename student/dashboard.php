<?php
require_once '../config.php';
checkUserType('student');

$conn = getDBConnection();

// Get student info
$student_query = $conn->prepare("SELECT s.*, c.class_name, c.section, c.academic_year 
                                FROM students s 
                                LEFT JOIN classes c ON s.class_id = c.id 
                                WHERE s.user_id = ?");
$student_query->bind_param("i", $_SESSION['user_id']);
$student_query->execute();
$student = $student_query->get_result()->fetch_assoc();

// Get attendance statistics
$attendance_stats = $conn->query("SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance WHERE student_id = {$student['id']}")->fetch_assoc();

$attendance_percentage = $attendance_stats['total_days'] > 0 
    ? round(($attendance_stats['present_days'] / $attendance_stats['total_days']) * 100, 2) 
    : 0;

// Get pending fees
$pending_fees = $conn->query("SELECT * FROM fees 
                              WHERE student_id = {$student['id']} 
                              AND status = 'pending' 
                              ORDER BY due_date ASC");

// Get recent attendance
$recent_attendance = $conn->query("SELECT * FROM attendance
                                  WHERE student_id = {$student['id']}
                                  ORDER BY attendance_date DESC LIMIT 10");

// Get assignments for student's class
$assignments = $conn->query("SELECT a.*, t.first_name, t.last_name
                            FROM assignments a
                            LEFT JOIN teachers t ON a.teacher_id = t.id
                            WHERE a.class_id = {$student['class_id']}
                            ORDER BY a.due_date DESC");

// Get results for student
$results = $conn->query("SELECT * FROM results
                        WHERE student_id = {$student['id']}
                        ORDER BY created_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
        
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .profile-card h2 {
            color: #3498db;
            margin-bottom: 20px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .profile-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .profile-item label {
            color: #666;
            font-weight: 500;
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #3498db;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .download-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .download-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🎓 Student Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $student['first_name']; ?></span>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="profile-card">
            <h2>My Profile</h2>
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Name:</label>
                    <span><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></span>
                </div>
                <div class="profile-item">
                    <label>Roll Number:</label>
                    <span><?php echo $student['roll_number']; ?></span>
                </div>
                <div class="profile-item">
                    <label>Class:</label>
                    <span><?php echo $student['class_name'] . ' - ' . $student['section']; ?></span>
                </div>
                <div class="profile-item">
                    <label>Academic Year:</label>
                    <span><?php echo $student['academic_year']; ?></span>
                </div>
                <div class="profile-item">
                    <label>Email:</label>
                    <span><?php echo $student['email']; ?></span>
                </div>
                <div class="profile-item">
                    <label>Phone:</label>
                    <span><?php echo $student['phone']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Attendance Rate</h3>
                <div class="value"><?php echo $attendance_percentage; ?>%</div>
            </div>
            
            <div class="stat-card">
                <h3>Present Days</h3>
                <div class="value"><?php echo $attendance_stats['present_days']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Absent Days</h3>
                <div class="value"><?php echo $attendance_stats['absent_days']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Days</h3>
                <div class="value"><?php echo $attendance_stats['total_days']; ?></div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h3>📋 Recent Attendance</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($att = $recent_attendance->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($att['attendance_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $att['status']; ?>">
                                    <?php echo ucfirst($att['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>💰 Pending Fees</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_fees->num_rows > 0): ?>
                            <?php while ($fee = $pending_fees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fee['fee_type']; ?></td>
                                <td>$<?php echo number_format($fee['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    No pending fees
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>📚 Assignments</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Teacher</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($assignments->num_rows > 0): ?>
                            <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                <td><?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'No due date'; ?></td>
                                <td>
                                    <?php if ($assignment['file_path']): ?>
                                        <a href="../<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="download-link">Download</a>
                                    <?php else: ?>
                                        <span style="color: #666;">No file</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    No assignments available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3>📊 Results</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Marks</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results->num_rows > 0): ?>
                            <?php while ($result = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                <td><?php echo htmlspecialchars($result['exam_type']); ?></td>
                                <td><?php echo $result['marks_obtained'] . '/' . $result['total_marks']; ?></td>
                                <td><?php echo htmlspecialchars($result['grade']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    No results available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
