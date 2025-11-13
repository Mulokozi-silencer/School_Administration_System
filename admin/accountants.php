<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle accountant operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Add new accountant
            $username = sanitize($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $employee_id = sanitize($_POST['employee_id']);
            $first_name = sanitize($_POST['first_name']);
