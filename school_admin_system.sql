-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 06:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_admin_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `accountants`
--

CREATE TABLE `accountants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `qualification` varchar(100) NOT NULL,
  `hire_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accountants`
--

INSERT INTO `accountants` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `qualification`, `hire_date`, `created_at`) VALUES
(0, 11, 'PT/IT/0098', 'urassa', 'urassa', 'urassa@gmail.com', '12345678', 'Bachelor Degree in education', '2025-10-27', '2025-10-28 12:30:18'),
(1, 11, 'PT/IT/0098', 'urassa', 'urassa', 'urassa@gmail.com', '12345678', 'Bachelor Degree in education', '2025-10-27', '2025-10-28 12:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `class_id`, `teacher_id`, `title`, `description`, `due_date`, `file_path`, `created_at`) VALUES
(1, 1, 1, 'Theory of Computation', 'Do this quiz in my session.', '2025-11-06', 'uploads/assignments/1762307804_Cover_Letter.pdf', '2025-11-05 01:56:44'),
(2, 1, 1, 'Quiz', 'DO it now', '2025-11-14', NULL, '2025-11-10 12:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`, `teacher_id`, `academic_year`, `created_at`, `department_id`) VALUES
(1, 'Grade 9', 'A', 1, '2024-2025', '2025-10-26 01:20:39', NULL),
(3, 'Grade 10', 'A', 3, '2024-2025', '2025-10-26 01:20:39', NULL),
(4, 'Standard 7', 'A', 3, '2024-2025', '2025-10-26 02:09:25', NULL),
(5, 'Library', 'LPII-FF', NULL, '2024-2025', '2025-10-27 12:44:24', NULL),
(6, 'Chemistry Labaratory', 'A', NULL, '2024-2025', '2025-11-02 13:37:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 2),
(3, 1, 3, 3),
(7, 3, 1, 1),
(8, 3, 2, 2),
(9, 3, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`) VALUES
(1, 'Science', 'Science and Technology Department'),
(2, 'Arts', 'Arts and Humanities Department'),
(3, 'Business', 'Commerce and Accounting Department');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `added_by` int(11) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `fee_type`, `amount`, `due_date`, `status`, `added_by`, `payment_date`, `payment_method`, `transaction_id`, `remarks`, `created_at`) VALUES
(4, 4, 'Tuition Fee', 1500.00, '2024-10-31', 'paid', 0, '2025-10-28', 'Online Payment', '009', NULL, '2025-10-26 01:20:39'),
(5, 4, 'Development Fee', 25000.00, '2025-03-01', 'paid', 0, '2025-11-05', 'Cash', '009', 'Incomplete fees', '2025-11-05 01:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 12, '8b8f01e2556e0ed350324efe0b54e1f4b3db7e1e08cdb3d320849c2eb973fa3a', '2025-11-10 13:52:35', 0, '2025-11-10 11:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `roll_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `roll_number`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `address`, `class_id`, `admission_date`, `created_at`) VALUES
(4, 8, 'STD004', 'Diana', 'Miller', 'diana.m@email.com', '555-0204', '2007-11-30', '321 Elm St, City', 3, '2023-08-01', '2025-10-26 01:20:39'),
(6, 12, '006', 'Mulokozi', 'Geofrey', 'mulokoziwillium@gmail.com', '0655478996', '2003-06-01', 'Mbeya University of Science and Technology,(MUST)', 6, '2025-11-05', '2025-11-05 01:14:03');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `added_by`, `subject_name`, `teacher_id`, `class_id`, `subject_code`, `description`, `created_at`) VALUES
(1, 0, 'Mathematics', NULL, 0, 'MATH-101', 'Basic and Advanced Mathematics', '2025-10-26 01:20:39'),
(2, 0, 'English', NULL, 0, 'ENG101', 'English Language and Literature', '2025-10-26 01:20:39'),
(3, 0, 'Science', 1, 3, 'SCI101', 'General Science', '2025-10-26 01:20:39'),
(4, 0, 'History', NULL, 0, 'HIST101', 'World History', '2025-10-26 01:20:39'),
(5, 0, 'Computer Science', 2, 5, 'CS101', 'Introduction to Computing', '2025-10-26 01:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('teacher','head_of_department','admin') DEFAULT 'teacher'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `subject`, `qualification`, `hire_date`, `created_at`, `role`) VALUES
(1, 2, 'T001', 'John', 'Smith', 'john.smith@school.com', '555-0101', 'Mathematics', 'M.Sc. Mathematics', '2020-08-15', '2025-10-26 01:20:39', 'teacher'),
(2, 3, 'T002', 'Sarah', 'Jones', 'sarah.jones@school.com', '555-0102', 'English', 'M.A. English Literature', '2019-07-01', '2025-10-26 01:20:39', 'teacher'),
(3, 4, 'T003', 'Michael', 'Brown', 'michael.brown@school.com', '555-0103', 'Science', 'M.Sc. Physics', '2021-06-10', '2025-10-26 01:20:39', 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','teacher','accountant','student') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `user_type`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2025-10-26 01:20:38'),
(2, 'john.smith', '$2y$10$guh6fhp/Vn9QfVKhZRJDFOoiZiiZ98bN3B2bsgWf5WuPOqrGDeH76', 'teacher', 'active', '2025-10-26 01:20:39'),
(3, 'sarah.jones', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active', '2025-10-26 01:20:39'),
(4, 'michael.brown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active', '2025-10-26 01:20:39'),
(5, 'alice.williams', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', '2025-10-26 01:20:39'),
(6, 'bob.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', '2025-10-26 01:20:39'),
(7, 'charlie.davis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', '2025-10-26 01:20:39'),
(8, 'diana.miller', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active', '2025-10-26 01:20:39'),
(9, 'test123', '$2y$10$x1pIXZn6Re4nBUftVr8jqehEYAJOUmmrX.MTSMQuF2XQqPBZu1b.6', 'student', 'active', '2025-10-26 02:08:01'),
(10, 'mr. urassa', '$2y$10$cFGhyQD6UWbG8ms4AH7n7uCf1RLggntTGkjG2l31i4LuU8VwoBpMa', 'teacher', 'active', '2025-10-27 12:27:53'),
(11, 'urassa', '$2y$10$/peQhM7/CAe2nL7oxS0lAOnDet6C2ztVu0EsqS8qgGHiLHIhim.7G', 'accountant', 'active', '2025-10-28 12:30:18'),
(12, 'mulokozi', '$2y$10$dDY5wgjDQJC6Yfw3vTob0OKYOpKubFYpPHr6L9HftgIn2FExlO.H6', 'student', 'active', '2025-11-05 01:14:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accountants`
--
ALTER TABLE `accountants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`,`user_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `fk_department` (`department_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `unique_active_token` (`user_id`,`used`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_number` (`roll_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
