<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No subject ID provided']);
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($subject = $result->fetch_assoc()) {
    echo json_encode($subject);
} else {
    echo json_encode(['error' => 'Subject not found']);
}

$conn->close();
