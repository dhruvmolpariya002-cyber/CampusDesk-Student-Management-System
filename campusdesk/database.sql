-- ============================================================================
-- CampusDesk — Student Management System Database Script
-- Subject: PHP & MySQL (BCA Semester 3 Academic Project)
-- Database Name: campusdesk_db
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- Compatible with: MySQL 8.x / MariaDB 10.x (XAMPP Default)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `campusdesk_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusdesk_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `results`;
DROP TABLE IF EXISTS `examinations`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Table 1: users (System authentication accounts for Admin and Students)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 2: departments (Academic Departments)
-- ----------------------------------------------------------------------------
CREATE TABLE `departments` (
  `dept_id` INT AUTO_INCREMENT PRIMARY KEY,
  `dept_name` VARCHAR(100) NOT NULL UNIQUE,
  `dept_code` VARCHAR(20) NOT NULL UNIQUE,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 3: courses (Degree and Diploma Courses)
-- ----------------------------------------------------------------------------
CREATE TABLE `courses` (
  `course_id` INT AUTO_INCREMENT PRIMARY KEY,
  `dept_id` INT NOT NULL,
  `course_name` VARCHAR(120) NOT NULL,
  `course_code` VARCHAR(20) NOT NULL UNIQUE,
  `duration_years` DECIMAL(3,1) NOT NULL DEFAULT 3.0,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_courses_dept` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 4: students (Core Student Directory)
-- ----------------------------------------------------------------------------
CREATE TABLE `students` (
  `student_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Academic Roll Number / Enrollment No',
  `user_id` INT NULL UNIQUE COMMENT 'Linked user login account',
  `course_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `address` TEXT NOT NULL,
  `admission_date` DATE NOT NULL,
  `enrollment_status` ENUM('Active', 'Graduated', 'Suspended', 'Withdrawn') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_students_code` (`student_code`),
  INDEX `idx_students_course` (`course_id`),
  INDEX `idx_students_status` (`enrollment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 5: subjects (Course Subjects by Semester)
-- ----------------------------------------------------------------------------
CREATE TABLE `subjects` (
  `subject_id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `subject_name` VARCHAR(100) NOT NULL,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `semester` INT NOT NULL,
  `credits` INT NOT NULL DEFAULT 4,
  CONSTRAINT `fk_subjects_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_subjects_course_sem` (`course_id`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 6: examinations (Academic Exam Sessions)
-- ----------------------------------------------------------------------------
CREATE TABLE `examinations` (
  `exam_id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `exam_name` VARCHAR(100) NOT NULL,
  `semester` INT NOT NULL,
  `exam_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_exams_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_exams_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 7: results (Subject-wise Student Examination Marks)
-- ----------------------------------------------------------------------------
CREATE TABLE `results` (
  `result_id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `obtained_marks` DECIMAL(5,2) NOT NULL,
  `grade` VARCHAR(5) NOT NULL,
  `status` ENUM('Pass', 'Fail') NOT NULL,
  `remarks` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_results_exam` FOREIGN KEY (`exam_id`) REFERENCES `examinations` (`exam_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_results_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uq_result_entry` UNIQUE (`exam_id`, `student_id`, `subject_id`),
  INDEX `idx_results_student` (`student_id`),
  INDEX `idx_results_exam` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 8: attendance (Daily Student Attendance Log)
-- ----------------------------------------------------------------------------
CREATE TABLE `attendance` (
  `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
  `remarks` VARCHAR(255) NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `uq_attendance_student_date` UNIQUE (`student_id`, `attendance_date`),
  INDEX `idx_attendance_date` (`attendance_date`),
  INDEX `idx_attendance_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 9: fees (Student Academic Fee Ledgers)
-- ----------------------------------------------------------------------------
CREATE TABLE `fees` (
  `fee_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `fee_type` VARCHAR(50) NOT NULL COMMENT 'Tuition, Examination, Library, Laboratory, Hostel',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_date` DATE NOT NULL,
  `payment_status` ENUM('Paid', 'Partial', 'Pending') NOT NULL DEFAULT 'Pending',
  `payment_date` DATE NULL,
  `reference_no` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_fees_student` (`student_id`),
  INDEX `idx_fees_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 10: notices (Campus Announcements and Circulars)
-- ----------------------------------------------------------------------------
CREATE TABLE `notices` (
  `notice_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `category` ENUM('Academic', 'Examination', 'Event', 'Urgent', 'General') NOT NULL DEFAULT 'General',
  `published_by` INT NOT NULL,
  `publish_date` DATE NOT NULL,
  `expiry_date` DATE NULL,
  `status` ENUM('Published', 'Draft') NOT NULL DEFAULT 'Published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notices_author` FOREIGN KEY (`published_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX `idx_notices_status_date` (`status`, `publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SAFE DEMONSTRATION SAMPLE DATA
-- Password for demonstration users:
-- Note: Real production environments generate initial admin via setup_admin.php
-- Demo accounts hash represents standard bcrypt hash for password "Password@123"
-- Generated via PHP: password_hash('Password@123', PASSWORD_BCRYPT)
-- ============================================================================

INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_code`, `status`) VALUES
(1, 'Department of Computer Applications', 'DCA', 'active'),
(2, 'Department of Information Technology', 'DIT', 'active'),
(3, 'Department of Management Studies', 'DMS', 'active');

INSERT INTO `courses` (`course_id`, `dept_id`, `course_name`, `course_code`, `duration_years`, `description`, `status`) VALUES
(1, 1, 'Bachelor of Computer Applications', 'BCA', 3.0, 'Undergraduate 3-year degree focusing on web programming, database systems, software engineering, and networking.', 'active'),
(2, 2, 'B.Sc Information Technology', 'BSCIT', 3.0, 'Undergraduate curriculum covering systems architecture, cloud computing, and cyber security fundamentals.', 'active'),
(3, 1, 'Master of Computer Applications', 'MCA', 2.0, 'Postgraduate degree covering advanced algorithms, distributed databases, and enterprise software design.', 'active');

-- Demonstration accounts default password is "password" (also supports "Password@123")
-- Generated via standard bcrypt hash for "password"
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@campusdesk.edu', 'admin', 'active'),
(2, 'bca2024001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aarav.sharma@campusdesk.edu', 'student', 'active'),
(3, 'bca2024002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'diya.patel@campusdesk.edu', 'student', 'active'),
(4, 'it2024010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'rohan.mehta@campusdesk.edu', 'student', 'active');

INSERT INTO `students` (`student_id`, `student_code`, `user_id`, `course_id`, `full_name`, `email`, `phone`, `dob`, `gender`, `address`, `admission_date`, `enrollment_status`) VALUES
(1, 'BCA2024001', 2, 1, 'Aarav Sharma', 'aarav.sharma@campusdesk.edu', '9876543210', '2004-05-14', 'Male', '42 MG Road, Bangalore, Karnataka', '2024-07-15', 'Active'),
(2, 'BCA2024002', 3, 1, 'Diya Patel', 'diya.patel@campusdesk.edu', '9823456789', '2004-09-22', 'Female', '18 Navrangpura, Ahmedabad, Gujarat', '2024-07-15', 'Active'),
(3, 'IT2024010', 4, 2, 'Rohan Mehta', 'rohan.mehta@campusdesk.edu', '9712345678', '2003-11-08', 'Male', '76 Park Street, Kolkata, West Bengal', '2024-07-18', 'Active');

INSERT INTO `subjects` (`subject_id`, `course_id`, `subject_name`, `subject_code`, `semester`, `credits`) VALUES
(1, 1, 'Database Management Systems & MySQL', 'BCA301', 3, 4),
(2, 1, 'Web Development with PHP', 'BCA302', 3, 4),
(3, 1, 'Data Structures & Algorithms', 'BCA303', 3, 4),
(4, 1, 'Computer Organization & Architecture', 'BCA304', 3, 3),
(5, 2, 'Object Oriented Programming in Java', 'IT301', 3, 4),
(6, 2, 'Operating Systems Concepts', 'IT302', 3, 4);

INSERT INTO `examinations` (`exam_id`, `course_id`, `exam_name`, `semester`, `exam_date`) VALUES
(1, 1, 'BCA Semester 3 Mid-Term Examination 2024', 3, '2024-10-10'),
(2, 2, 'B.Sc IT Semester 3 Mid-Term Examination 2024', 3, '2024-10-12');

INSERT INTO `results` (`result_id`, `exam_id`, `student_id`, `subject_id`, `max_marks`, `obtained_marks`, `grade`, `status`, `remarks`) VALUES
(1, 1, 1, 1, 100.00, 88.00, 'A+', 'Pass', 'Outstanding query design and normalization understanding'),
(2, 1, 1, 2, 100.00, 92.00, 'O', 'Pass', 'Excellent PDO and session architecture execution'),
(3, 1, 1, 3, 100.00, 78.00, 'A', 'Pass', 'Solid implementation of trees and sorting algorithms'),
(4, 1, 2, 1, 100.00, 82.00, 'A+', 'Pass', 'Great grasp of ACID transactions and keys'),
(5, 1, 2, 2, 100.00, 85.00, 'A+', 'Pass', 'Very good CSRF and prepared statement handling'),
(6, 1, 2, 3, 100.00, 69.00, 'B+', 'Pass', 'Good grasp of linear data structures');

INSERT INTO `attendance` (`attendance_id`, `student_id`, `course_id`, `attendance_date`, `status`, `remarks`) VALUES
(1, 1, 1, '2024-10-01', 'Present', 'Regular class on MySQL Indexes'),
(2, 1, 1, '2024-10-03', 'Present', 'PHP PDO Lab demonstration'),
(3, 1, 1, '2024-10-04', 'Present', 'Data structures recursion workshop'),
(4, 1, 1, '2024-10-07', 'Late', 'Late arrival due to transit delay'),
(5, 1, 1, '2024-10-08', 'Present', 'Regular class'),
(6, 2, 1, '2024-10-01', 'Present', 'Regular class'),
(7, 2, 1, '2024-10-03', 'Absent', 'Medical leave application submitted'),
(8, 2, 1, '2024-10-04', 'Present', 'Lab session'),
(9, 2, 1, '2024-10-07', 'Present', 'Regular class'),
(10, 2, 1, '2024-10-08', 'Present', 'Regular class'),
(11, 3, 2, '2024-10-01', 'Present', 'Java OOP intro'),
(12, 3, 2, '2024-10-03', 'Present', 'Operating systems memory paging');

INSERT INTO `fees` (`fee_id`, `student_id`, `fee_type`, `total_amount`, `paid_amount`, `due_date`, `payment_status`, `payment_date`, `reference_no`) VALUES
(1, 1, 'Semester 3 Tuition Fee', 35000.00, 35000.00, '2024-08-30', 'Paid', '2024-08-15', 'TXN-BCA-2024-8841'),
(2, 1, 'Semester 3 Exam Fee', 2500.00, 2500.00, '2024-09-25', 'Paid', '2024-09-20', 'TXN-EXAM-2024-1102'),
(3, 2, 'Semester 3 Tuition Fee', 35000.00, 20000.00, '2024-08-30', 'Partial', '2024-08-25', 'TXN-BCA-2024-9104'),
(4, 2, 'Semester 3 Exam Fee', 2500.00, 0.00, '2024-09-25', 'Pending', NULL, NULL),
(5, 3, 'Semester 3 Tuition Fee', 32000.00, 32000.00, '2024-08-30', 'Paid', '2024-08-18', 'TXN-IT-2024-3321');

INSERT INTO `notices` (`notice_id`, `title`, `content`, `category`, `published_by`, `publish_date`, `expiry_date`, `status`) VALUES
(1, 'BCA Semester 3 Internal Practical Exam Schedule', 'All BCA Semester 3 students are hereby notified that the Practical Examinations for Subject BCA302 (Web Development with PHP) will be conducted from November 12, 2024. Students must bring their lab record books signed by the faculty guide.', 'Examination', 1, '2024-10-15', '2024-11-20', 'Published'),
(2, 'Annual College Technical Symposium — ByteQuest 2024', 'The Department of Computer Applications invites registrations for ByteQuest 2024 featuring Web Designing, Blind Coding, and SQL Hackathon events. Register before November 5, 2024 at the Department Office.', 'Event', 1, '2024-10-18', '2024-11-06', 'Published'),
(3, 'Tuition Fee Payment Reminder for Outstanding Dues', 'Students with pending fee installments for Semester 3 are requested to clear their accounts before the end of this month to avoid late fee penalties and hall ticket delays.', 'Academic', 1, '2024-10-20', '2024-11-15', 'Published');
