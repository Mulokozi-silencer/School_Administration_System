<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle class operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $class_name = sanitize($_POST['class_name']);
            $section = sanitize($_POST['section']);
            $teacher_id = intval($_POST['teacher_id']);
            $academic_year = sanitize($_POST['academic_year']);
            
            $stmt = $conn->prepare("INSERT INTO classes (class_name, section, teacher_id, academic_year) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $class_name, $section, $teacher_id, $academic_year);
            $stmt->execute();
            
            header('Location: classes.php?msg=added');
            exit();
        } elseif ($_POST['action'] === 'delete') {
            $class_id = intval($_POST['class_id']);
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            
            header('Location: classes.php?msg=deleted');
            exit();
        }
    }
}

// Get all classes with teacher info
$classes = $conn->query("SELECT c.*, t.first_name, t.last_name, t.employee_id,
                        (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count
                        FROM classes c
                        LEFT JOIN teachers t ON c.teacher_id = t.id
                        ORDER BY c.class_name, c.section");

// Get teachers for dropdown
$teachers = $conn->query("SELECT id, first_name, last_name, employee_id FROM teachers ORDER BY first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
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
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
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
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .class-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
        }
        
        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }
        
        .class-header h3 {
            color: #667eea;
            font-size: 22px;
        }
        
        .class-section {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .class-info {
            margin-bottom: 10px;
            color: #666;
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .class-info:last-of-type {
            border-bottom: none;
        }
        
        .class-info label {
            font-weight: 500;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-top: 15px;
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
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
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
        <h1>📚 Class Management</h1>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert">
                <?php 
                echo $_GET['msg'] === 'added' ? 'Class added successfully!' : 
                     ($_GET['msg'] === 'deleted' ? 'Class deleted successfully!' : '');
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h2>Classes Overview</h2>
            <button class="btn-add" onclick="openModal()">+ Add New Class</button>
        </div>
        
        <div class="classes-grid">
            <?php while ($class = $classes->fetch_assoc()): ?>
            <div class="class-card">
                <div class="class-header">
                    <h3><?php echo $class['class_name']; ?></h3>
                    <span class="class-section">Section <?php echo $class['section']; ?></span>
                </div>
                
                <div class="class-info">
                    <label>Class Teacher:</label>
                    <span><?php echo $class['first_name'] ? $class['first_name'] . ' ' . $class['last_name'] : 'Not Assigned'; ?></span>
                </div>
                
                <div class="class-info">
                    <label>Academic Year:</label>
                    <span><?php echo $class['academic_year']; ?></span>
                </div>
                
                <div class="class-info">
                    <label>Total Students:</label>
                    <span><?php echo $class['student_count']; ?></span>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this class?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                    <button type="submit" class="btn-delete">Delete Class</button>
                </form>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Add Class Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Class</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Class Name *</label>
                        <input type="text" name="class_name" placeholder="e.g., Grade 9" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Section *</label>
                        <input type="text" name="section" placeholder="e.g., A" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Class Teacher *</label>
                    <select name="teacher_id" required>
                        <option value="">Select Teacher...</option>
                        <?php 
                        $teachers->data_seek(0);
                        while ($teacher = $teachers->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $teacher['id']; ?>">
                                <?php echo $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['employee_id'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Academic Year *</label>
                    <input type="text" name="academic_year" placeholder="e.g., 2024-2025" required>
                </div>
                
                <button type="submit" class="btn-submit">Add Class</button>
                <button type="button" class="btn-close" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>