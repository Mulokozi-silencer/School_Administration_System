<?php
require_once '../config.php';
checkUserType('teacher');

$conn = getDBConnection();

// Get teacher info
$teacher_query = $conn->prepare("SELECT * FROM teachers WHERE user_id = ?");
$teacher_query->bind_param("i", $_SESSION['user_id']);
$teacher_query->execute();
$teacher = $teacher_query->get_result()->fetch_assoc();

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'enroll') {
            // Enroll existing student to class
            $student_id = intval($_POST['student_id']);
            $class_id = intval($_POST['class_id']);
            $roll_number = sanitize($_POST['roll_number']);
            $admission_date = sanitize($_POST['admission_date']);

            // Check if student is already enrolled in another class
            $check_stmt = $conn->prepare("SELECT class_id FROM students WHERE id = ?");
            $check_stmt->bind_param("i", $student_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result()->fetch_assoc();

            if ($result && $result['class_id']) {
                header('Location: students.php?msg=already_enrolled');
                exit();
            }

            // Update student's class and roll number
            $stmt = $conn->prepare("UPDATE students SET class_id = ?, roll_number = ?, admission_date = ? WHERE id = ?");
            $stmt->bind_param("issi", $class_id, $roll_number, $admission_date, $student_id);
            $stmt->execute();

            header('Location: students.php?msg=enrolled');
            exit();
        } elseif ($_POST['action'] === 'delete') {
            $student_id = intval($_POST['student_id']);
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();

            header('Location: students.php?msg=deleted');
            exit();
        }
    }
}

//Fetch students according to the class assigned to the teacher
$students = $conn->query("SELECT s.*, c.class_name, c.section, u.username FROM students s
                          LEFT JOIN classes c ON s.class_id = c.id
                          LEFT JOIN users u ON s.user_id = u.id
                          WHERE c.teacher_id = {$teacher['id']}
                          ORDER BY s.id DESC");

// Fetch classes assigned to the teacher for dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes WHERE teacher_id = {$teacher['id']} ORDER BY class_name");

// Fetch unregistered students (students who have registered but not assigned to any class)
$unregistered_students = $conn->query("SELECT s.*, u.username FROM students s
                                       LEFT JOIN users u ON s.user_id = u.id
                                       WHERE s.class_id IS NULL OR s.class_id = 0
                                       ORDER BY s.first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
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
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-add {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #dc3545;
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
            border-radius: 10px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🎓 Manage Students</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['msg']) {
                    case 'added':
                        echo 'Student added successfully!';
                        break;
                    case 'enrolled':
                        echo 'Student enrolled successfully!';
                        break;
                    case 'deleted':
                        echo 'Student deleted successfully!';
                        break;
                    case 'already_enrolled':
                        echo 'Student is already enrolled in another class!';
                        break;
                    default:
                        echo 'Operation completed successfully!';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h2>Students List</h2>
            <div>
                <button class="btn-add" onclick="openEnrollModal()">+ Enroll Student</button>
                <button class="btn-add" onclick="openModal()" style="margin-left: 10px;">+ Add New Student</button>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['roll_number']; ?></td>
                        <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                        <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Enroll Student Modal -->
    <div id="enrollModal" class="modal">
        <div class="modal-content">
            <h2>Enroll Existing Student</h2>
            <form method="POST">
                <input type="hidden" name="action" value="enroll">

                <div class="form-row">
                    <div class="form-group">
                        <label>Select Student *</label>
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $unregistered_students->data_seek(0);
                            while ($student = $unregistered_students->fetch_assoc()):
                            ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['username'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()):
                            ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo $class['class_name'] . ' - ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Roll Number *</label>
                        <input type="text" name="roll_number" required>
                    </div>
                    <div class="form-group">
                        <label>Admission Date</label>
                        <input type="date" name="admission_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Enroll Student</button>
                <button type="button" class="btn-close" onclick="closeEnrollModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Student</h2>
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

                <div class="form-row">
                    <div class="form-group">
                        <label>Roll Number *</label>
                        <input type="text" name="roll_number" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Class *</label>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            <?php
                            $classes->data_seek(0);
                            while ($class = $classes->fetch_assoc()):
                            ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo $class['class_name'] . ' - ' . $class['section']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admission Date</label>
                        <input type="date" name="admission_date">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-submit">Add Student</button>
                <button type="button" class="btn-close" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openEnrollModal() {
            document.getElementById('enrollModal').style.display = 'block';
        }

        function closeEnrollModal() {
            document.getElementById('enrollModal').style.display = 'none';
        }

        function openModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const enrollModal = document.getElementById('enrollModal');
            const addModal = document.getElementById('addModal');
            if (event.target === enrollModal) {
                closeEnrollModal();
            }
            if (event.target === addModal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>