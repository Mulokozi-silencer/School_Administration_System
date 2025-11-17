<?php
require_once '../config.php';
checkUserType('teacher');

$conn = getDBConnection();

if (isset($_GET['class_id'])) {
    $class_id = sanitize($_GET['class_id']);

    // Verify that the teacher teaches this class
    $teacher_query = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $teacher_query->bind_param("i", $_SESSION['user_id']);
    $teacher_query->execute();
    $teacher = $teacher_query->get_result()->fetch_assoc();

    $class_check = $conn->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $class_check->bind_param("ii", $class_id, $teacher['id']);
    $class_check->execute();

    if ($class_check->get_result()->num_rows > 0) {
        $students_query = $conn->prepare("SELECT id, first_name, last_name, roll_number FROM students WHERE class_id = ? ORDER BY first_name");
        $students_query->bind_param("i", $class_id);
        $students_query->execute();
        $students = $students_query->get_result();

        $student_list = [];
        while ($student = $students->fetch_assoc()) {
            $student_list[] = $student;
        }

        header('Content-Type: application/json');
        echo json_encode($student_list);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized access to this class']);
    }

    $class_check->close();
    $teacher_query->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Class ID not provided']);
}

$conn->close();
?>
