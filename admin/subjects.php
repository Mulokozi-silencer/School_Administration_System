<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle subject operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // Add Subject
        if ($_POST['action'] === 'add') {
            $subject_name = sanitize($_POST['subject_name']);
            $class_id = intval($_POST['class_id']);
            $teacher_id = intval($_POST['teacher_id']);
            $admin_id = $_SESSION['user_id']; // current admin

            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, class_id, teacher_id, added_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siii", $subject_name, $class_id, $teacher_id, $admin_id);
            $stmt->execute();

            header('Location: subjects.php?msg=added');
            exit();
        }

        // Edit Subject
        elseif ($_POST['action'] === 'edit') {
            $subject_id = intval($_POST['subject_id_edit']);
            $subject_name = sanitize($_POST['subject_name_edit']);
            $class_id = intval($_POST['class_id_edit']);
            $teacher_id = intval($_POST['teacher_id_edit']);

            $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, class_id=?, teacher_id=? WHERE id=?");
            $stmt->bind_param("siii", $subject_name, $class_id, $teacher_id, $subject_id);
            $stmt->execute();

            header('Location: subjects.php?msg=updated');
            exit();
        }

        // Delete Subject
        elseif ($_POST['action'] === 'delete') {
            $subject_id = intval($_POST['subject_id']);
            $conn->query("DELETE FROM subjects WHERE id = $subject_id");
            header('Location: subjects.php?msg=deleted');
            exit();
        }
    }
}

// Get subjects with admin info
$subjects = $conn->query("
    SELECT s.*, c.class_name, t.first_name AS teacher_first, t.last_name AS teacher_last, u.username AS added_by
    FROM subjects s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN teachers t ON s.teacher_id = t.id
    LEFT JOIN users u ON s.added_by = u.id
    ORDER BY s.id DESC
");

// Get classes and teachers for dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
$teachers = $conn->query("SELECT * FROM teachers ORDER BY first_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subjects Management - Admin</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5; margin:0; padding:0; }
.navbar { background:#667eea; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
.navbar a { color:white; text-decoration:none; margin-left:10px; background:rgba(255,255,255,0.2); padding:8px 16px; border-radius:5px; }
.container { max-width:1400px; margin:30px auto; padding:0 20px; }
.alert { padding:15px; border-radius:5px; margin-bottom:20px; background:#d4edda; color:#155724; }
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.btn-add { background:#667eea; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; }
.table-container { background:white; border-radius:10px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.1); overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; text-align:left; border-bottom:1px solid #ddd; }
th { background:#f8f9fa; font-weight:600; }
.status-badge { padding:5px 10px; border-radius:15px; font-size:12px; font-weight:600; }
.status-active { background:#d4edda; color:#155724; }
.status-inactive { background:#f8d7da; color:#721c24; }
.btn-action { padding:5px 10px; border:none; border-radius:3px; cursor:pointer; font-size:12px; color:white; background:#28a745; margin-right:5px; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
.modal-content { background:white; width:90%; max-width:500px; margin:50px auto; padding:30px; border-radius:10px; }
.form-group { margin-bottom:15px; }
.form-group label { display:block; margin-bottom:5px; font-weight:500; }
.form-group input, .form-group select { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; }
.btn-submit { background:#667eea; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; width:100%; }
.btn-close { background:#6c757d; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; width:100%; margin-top:10px; }
</style>
</head>
<body>
<nav class="navbar">
    <h1>📚 Subjects Management (Admin)</h1>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
<?php if(isset($_GET['msg'])): ?>
    <div class="alert">
        <?php
            echo $_GET['msg'] === 'added' ? 'Subject added successfully!' :
                 ($_GET['msg']==='updated' ? 'Subject updated successfully!' :
                 ($_GET['msg']==='deleted' ? 'Subject deleted successfully!' : ''));
        ?>
    </div>
<?php endif; ?>

<div class="header">
    <h2>Subjects List</h2>
    <button class="btn-add" onclick="openAddModal()">+ Add New Subject</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th>
<th>Subject Name</th>
<th>Class</th>
<th>Teacher</th>
<th>Added By</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($sub=$subjects->fetch_assoc()): ?>
<tr>
<td><?php echo $sub['id']; ?></td>
<td><?php echo $sub['subject_name']; ?></td>
<td><?php echo $sub['class_name']; ?></td>
<td><?php echo $sub['teacher_first'].' '.$sub['teacher_last']; ?></td>
<td><?php echo $sub['added_by']; ?></td>
<td>
<button class="btn-action" onclick="openEditModal(<?php echo $sub['id']; ?>)">Edit</button>
<form method="POST" style="display:inline;">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="subject_id" value="<?php echo $sub['id']; ?>">
<button type="submit" class="btn-action" style="background:#dc3545;">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Add Subject Modal -->
<div id="addModal" class="modal">
<div class="modal-content">
<h2>Add Subject</h2>
<form method="POST">
<input type="hidden" name="action" value="add">
<div class="form-group">
<label>Subject Name *</label>
<input type="text" name="subject_name" required>
</div>
<div class="form-group">
<label>Class *</label>
<select name="class_id" required>
<option value="">Choose class...</option>
<?php while($cls=$classes->fetch_assoc()): ?>
<option value="<?php echo $cls['id']; ?>"><?php echo $cls['class_name']; ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group">
<label>Teacher *</label>
<select name="teacher_id" required>
<option value="">Choose teacher...</option>
<?php while($t=$teachers->fetch_assoc()): ?>
<option value="<?php echo $t['id']; ?>"><?php echo $t['first_name'].' '.$t['last_name']; ?></option>
<?php endwhile; ?>
</select>
</div>
<button type="submit" class="btn-submit">Add Subject</button>
<button type="button" class="btn-close" onclick="closeAddModal()">Cancel</button>
</form>
</div>
</div>

<!-- Edit Subject Modal -->
<div id="editModal" class="modal">
<div class="modal-content">
<h2>Edit Subject</h2>
<form method="POST">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="subject_id_edit" id="edit_subject_id">
<div class="form-group">
<label>Subject Name *</label>
<input type="text" name="subject_name_edit" id="edit_subject_name" required>
</div>
<div class="form-group">
<label>Class *</label>
<select name="class_id_edit" id="edit_class_id" required>
<option value="">Choose class...</option>
<?php $classes->data_seek(0); while($cls=$classes->fetch_assoc()): ?>
<option value="<?php echo $cls['id']; ?>"><?php echo $cls['class_name']; ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group">
<label>Teacher *</label>
<select name="teacher_id_edit" id="edit_teacher_id" required>
<option value="">Choose teacher...</option>
<?php $teachers->data_seek(0); while($t=$teachers->fetch_assoc()): ?>
<option value="<?php echo $t['id']; ?>"><?php echo $t['first_name'].' '.$t['last_name']; ?></option>
<?php endwhile; ?>
</select>
</div>
<button type="submit" class="btn-submit">Update Subject</button>
<button type="button" class="btn-close" onclick="closeEditModal()">Cancel</button>
</form>
</div>
</div>

<script>
function openAddModal(){document.getElementById('addModal').style.display='block'}
function closeAddModal(){document.getElementById('addModal').style.display='none'}
function openEditModal(id){
fetch('get_subject.php?id='+id).then(res=>res.json()).then(sub=>{
document.getElementById('edit_subject_id').value=sub.id;
document.getElementById('edit_subject_name').value=sub.subject_name;
document.getElementById('edit_class_id').value=sub.class_id;
document.getElementById('edit_teacher_id').value=sub.teacher_id;
document.getElementById('editModal').style.display='block';
});
}
function closeEditModal(){document.getElementById('editModal').style.display='none'}
window.onclick=function(event){
if(event.target==document.getElementById('addModal')) closeAddModal();
if(event.target==document.getElementById('editModal')) closeEditModal();
}
</script>
</body>
</html>
<?php $conn->close(); ?>
