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

// Handle assignment upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_assignment'])) {
    $class_id = sanitize($_POST['class_id']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $due_date = sanitize($_POST['due_date']);

    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] == 0) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['assignment_file']['type'];
        $file_size = $_FILES['assignment_file']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 10 * 1024 * 1024) { // 10MB limit
            $file_name = time() . '_' . basename($_FILES['assignment_file']['name']);
            $file_path = 'uploads/assignments/' . $file_name;

            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], '../' . $file_path)) {
                // File uploaded successfully
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type or size. Only PDF and DOC files up to 10MB are allowed.";
        }
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO assignments (class_id, teacher_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $class_id, $teacher['id'], $title, $description, $due_date, $file_path);
        if ($stmt->execute()) {
            $success = "Assignment uploaded successfully!";
        } else {
            $error = "Failed to save assignment.";
        }
        $stmt->close();
    }
}

// Handle results posting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_result'])) {
    $student_id = sanitize($_POST['student_id']);
    $subject = sanitize($_POST['subject']);
    $exam_type = sanitize($_POST['exam_type']);
    $marks_obtained = sanitize($_POST['marks_obtained']);
    $total_marks = sanitize($_POST['total_marks']);
    $grade = sanitize($_POST['grade']);
    $remarks = sanitize($_POST['remarks']);

    $stmt = $conn->prepare("INSERT INTO results (student_id, subject, exam_type, marks_obtained, total_marks, grade, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issddss", $student_id, $subject, $exam_type, $marks_obtained, $total_marks, $grade, $remarks);
    if ($stmt->execute()) {
        $success = "Result posted successfully!";
    } else {
        $error = "Failed to post result.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes</title>
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            font-size: 16px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            background: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #27ae60;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🏫 My Classes</h1>
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

        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📚 Upload Assignment</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="upload_assignment" value="1">
                    <div class="form-group">
                        <label for="class_id">Select Class:</label>
                        <select name="class_id" id="class_id" required>
                            <option value="">Choose a class</option>
                            <?php
                            // Reset classes pointer
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo $class['class_name'] . ' - Section ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="title">Assignment Title:</label>
                        <input type="text" name="title" id="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="date" name="due_date" id="due_date">
                    </div>
                    <div class="form-group">
                        <label for="assignment_file">Upload File (PDF/DOC, max 10MB):</label>
                        <input type="file" name="assignment_file" id="assignment_file" accept=".pdf,.doc,.docx">
                    </div>
                    <button type="submit" class="btn">Upload Assignment</button>
                </form>
            </div>

            <div class="stat-card">
                <h3>📊 Post Results</h3>
                <form method="POST">
                    <input type="hidden" name="post_result" value="1">
                    <div class="form-group">
                        <label for="result_class_id">Select Class:</label>
                        <select name="result_class_id" id="result_class_id" onchange="loadStudents(this.value)" required>
                            <option value="">Choose a class</option>
                            <?php
                            // Reset classes pointer again
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo $class['class_name'] . ' - Section ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Select Student:</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">Choose a student</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" name="subject" id="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="exam_type">Exam Type:</label>
                        <select name="exam_type" id="exam_type" required>
                            <option value="">Select exam type</option>
                            <option value="Mid-term">Mid-term</option>
                            <option value="Final">Final</option>
                            <option value="Quiz">Quiz</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Project">Project</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marks_obtained">Marks Obtained:</label>
                        <input type="number" name="marks_obtained" id="marks_obtained" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="total_marks">Total Marks:</label>
                        <input type="number" name="total_marks" id="total_marks" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="grade">Grade:</label>
                        <input type="text" name="grade" id="grade" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label for="remarks">Remarks:</label>
                        <textarea name="remarks" id="remarks"></textarea>
                    </div>
                    <button type="submit" class="btn">Post Result</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function loadStudents(classId) {
            if (classId === '') {
                document.getElementById('student_id').innerHTML = '<option value="">Choose a student</option>';
                return;
            }

            fetch('get_students.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">Choose a student</option>';
                    data.forEach(student => {
                        options += `<option value="${student.id}">${student.first_name} ${student.last_name} (${student.roll_number})</option>`;
                    });
                    document.getElementById('student_id').innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    document.getElementById('student_id').innerHTML = '<option value="">Error loading students</option>';
                });
        }
    </script>
</body>
</html>
