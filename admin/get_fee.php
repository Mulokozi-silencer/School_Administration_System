<?php
require_once '../config.php';
checkUserType('admin');
$conn = getDBConnection();
$id = intval($_GET['id']);
$fee = $conn->query("SELECT * FROM fees WHERE id=$id")->fetch_assoc();
echo json_encode($fee);
$conn->close();
?>
