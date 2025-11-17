<?php
require_once '../config.php';
checkUserType('teacher');

$conn = getDBConnection();

// Get teacher info
$teacher_query = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_query->bind_param("i", $_SESSION['user_id']);
$teacher_query->execute();
$teacher = $teacher_query->get_result()->fetch_assoc();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_date'])) {
    $attendance_date = sanitize($_POST['attendance_date']);
    $attendance_data = $_POST['attendance'] ?? [];

    $success_count = 0;
    $error_count = 0;

    foreach ($attendance_data as $student_id => $status) {
        // Check if attendance already exists for this student on this date
        $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?");
        $check_stmt->bind_param("is", $student_id, $attendance_date);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows === 0) {
            // Insert new attendance record
            $insert_stmt = $conn->prepare("INSERT INTO attendance (student_id, teacher_id, attendance_date, status) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("iiss", $student_id, $teacher['id'], $attendance_date, $status);
            if ($insert_stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }

    $message = $success_count > 0 ? "Attendance marked successfully for $success_count students!" : "";
    if ($error_count > 0) {
        $message .= ($message ? " " : "") . "$error_count records failed (already marked or error).";
    }
}

// Get assigned classes with students
$classes_query = $conn->prepare("
    SELECT c.id, c.class_name, c.section, c.academic_year,
           COUNT(s.id) as student_count
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.class_name, c.section
");
$classes_query->bind_param("i", $teacher['id']);
$classes_query->execute();
$classes = $classes_query->get_result();

// Get students for each class
$class_students = [];
while ($class = $classes->fetch_assoc()) {
    $students_query = $conn->prepare("
        SELECT s.id, s.first_name, s.last_name, s.roll_number
        FROM students s
        WHERE s.class_id = ?
        ORDER BY s.roll_number
    ");
    $students_query->bind_param("i", $class['id']);
    $students_query->execute();
    $students = $students_query->get_result();

    $class_students[$class['id']] = [
        'class_info' => $class,
        'students' => $students
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher</title>
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

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }

        .date-selector {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .date-selector h2 {
            color: #2ecc71;
            margin-bottom: 20px;
        }

        .date-input-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .date-input-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }

        .date-input-group input:focus {
            outline: none;
            border-color: #2ecc71;
        }

        .class-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .class-title {
            font-size: 20px;
            color: #2ecc71;
            font-weight: 600;
        }

        .class-info {
            color: #666;
            font-size: 14px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .attendance-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .student-details {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #333;
        }

        .student-roll {
            color: #666;
            font-size: 14px;
        }

        .status-options {
            display: flex;
            gap: 10px;
        }

        .status-radio {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-radio input[type="radio"] {
            margin: 0;
        }

        .status-radio label {
            font-size: 14px;
            color: #666;
            cursor: pointer;
        }

        .status-present label {
            color: #28a745;
        }

        .status-absent label {
            color: #dc3545;
        }

        .status-late label {
            color: #ffc107;
        }

        .submit-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46, 204, 113, 0.3);
        }

        .no-classes {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-classes .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .date-input-group {
                flex-direction: column;
                align-items: stretch;
            }

            .attendance-table {
                font-size: 14px;
            }

            .status-options {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🏫 Teacher Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></span>
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="date-selector">
            <h2>📅 Mark Attendance</h2>
            <form method="POST" id="attendanceForm">
                <div class="date-input-group">
                    <label for="attendance_date">Select Date:</label>
                    <input type="date" id="attendance_date" name="attendance_date"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </form>
        </div>

        <?php if (empty($class_students)): ?>
            <div class="no-classes">
                <div class="icon">📚</div>
                <h3>No Classes Assigned</h3>
                <p>You don't have any classes assigned yet. Please contact your administrator.</p>
            </div>
        <?php else: ?>
            <?php foreach ($class_students as $class_id => $class_data): ?>
                <div class="class-section">
                    <div class="class-header">
                        <div>
                            <h3 class="class-title"><?php echo $class_data['class_info']['class_name'] . ' - Section ' . $class_data['class_info']['section']; ?></h3>
                            <div class="class-info">Academic Year: <?php echo $class_data['class_info']['academic_year']; ?> | Students: <?php echo $class_data['class_info']['student_count']; ?></div>
                        </div>
                    </div>

                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $class_data['students']->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                            </div>
                                            <div class="student-details">
                                                <div class="student-name"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                                <div class="student-roll">Roll No: <?php echo $student['roll_number']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="status-options">
                                            <div class="status-radio status-present">
                                                <input type="radio" id="present_<?php echo $student['id']; ?>"
                                                       name="attendance[<?php echo $student['id']; ?>]" value="present" checked>
                                                <label for="present_<?php echo $student['id']; ?>">Present</label>
                                            </div>
                                            <div class="status-radio status-absent">
                                                <input type="radio" id="absent_<?php echo $student['id']; ?>"
                                                       name="attendance[<?php echo $student['id']; ?>]" value="absent">
                                                <label for="absent_<?php echo $student['id']; ?>">Absent</label>
                                            </div>
                                            <div class="status-radio status-late">
                                                <input type="radio" id="late_<?php echo $student['id']; ?>"
                                                       name="attendance[<?php echo $student['id']; ?>]" value="late">
                                                <label for="late_<?php echo $student['id']; ?>">Late</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <div class="submit-section">
                <button type="submit" form="attendanceForm" class="btn-submit">
                    📝 Submit Attendance
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when date changes
        document.getElementById('attendance_date').addEventListener('change', function() {
            // This will refresh the page with the new date, allowing teachers to see if attendance is already marked
            this.form.submit();
        });

        // Confirm before submitting
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const date = document.getElementById('attendance_date').value;
            const today = new Date().toISOString().split('T')[0];

            if (date > today) {
                alert('Cannot mark attendance for future dates!');
                e.preventDefault();
                return false;
            }

            return confirm('Are you sure you want to submit attendance for ' + new Date(date).toLocaleDateString() + '?');
        });
    </script>
</body>
</html>
