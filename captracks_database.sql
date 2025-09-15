-- CapTracks Database Schema
-- Complete SQL script for MySQL/MariaDB
-- Generated from Laravel migrations

-- Create database
CREATE DATABASE IF NOT EXISTS captracks;
USE captracks;

-- Drop tables if they exist (in reverse dependency order)
DROP TABLE IF EXISTS group_milestone_tasks;
DROP TABLE IF EXISTS group_milestones;
DROP TABLE IF EXISTS defense_panels;
DROP TABLE IF EXISTS defense_schedules;
DROP TABLE IF EXISTS defense_requests;
DROP TABLE IF EXISTS project_submissions;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS offering_student;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS groups;
DROP TABLE IF EXISTS milestone_tasks;
DROP TABLE IF EXISTS milestone_templates;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS offering_student;
DROP TABLE IF EXISTS offerings;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS academic_terms;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS class_models;

-- 1. Users table (Faculty/Staff)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'Faculty/Staff ID for login',
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    birthday DATE NULL,
    department VARCHAR(255) NULL COMMENT 'Department instead of course',
    position VARCHAR(255) NULL COMMENT 'Position instead of year',
    role ENUM('chairperson', 'coordinator', 'adviser', 'panelist') NOT NULL,
    must_change_password BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Password reset tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- 3. Sessions table
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Students table
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    semester VARCHAR(10) NOT NULL,
    course VARCHAR(50) NOT NULL,
    password VARCHAR(255) NULL COMMENT 'For student authentication',
    must_change_password BOOLEAN DEFAULT TRUE COMMENT 'Force password change on first login',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 5. Roles table
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 6. User roles table
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('chairperson', 'coordinator', 'adviser', 'panelist') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY user_roles_user_id_role_unique (user_id, role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Academic terms table
CREATE TABLE academic_terms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(255) NOT NULL,
    semester ENUM('First Semester', 'Second Semester', 'Summer') NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 8. Offerings table
CREATE TABLE offerings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_title VARCHAR(255) NOT NULL COMMENT 'Subject Title',
    subject_code VARCHAR(255) NOT NULL COMMENT 'Offer Code',
    teacher_name VARCHAR(255) NOT NULL COMMENT 'Teacher Name',
    teacher_id BIGINT UNSIGNED NULL,
    academic_term_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (academic_term_id) REFERENCES academic_terms(id) ON DELETE CASCADE
);

-- 9. Offering-Student pivot table
CREATE TABLE offering_student (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offering_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY offering_student_offering_id_student_id_unique (offering_id, student_id),
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 10. Enrollments table
CREATE TABLE enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    semester VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY enrollments_student_id_semester_unique (student_id, semester),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 11. Groups table
CREATE TABLE groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    adviser_id BIGINT UNSIGNED NULL,
    offering_id BIGINT UNSIGNED NULL,
    academic_term_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (offering_id) REFERENCES offerings(id) ON DELETE SET NULL,
    FOREIGN KEY (academic_term_id) REFERENCES academic_terms(id) ON DELETE SET NULL
);

-- 12. Group members table
CREATE TABLE group_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    role ENUM('leader', 'member') DEFAULT 'member',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY group_members_group_id_student_id_unique (group_id, student_id),
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 13. Milestone templates table
CREATE TABLE milestone_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 14. Milestone tasks table
CREATE TABLE milestone_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    milestone_template_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    order INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    assigned_to VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (milestone_template_id) REFERENCES milestone_templates(id) ON DELETE CASCADE
);

-- 15. Group milestones table
CREATE TABLE group_milestones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    milestone_template_id BIGINT UNSIGNED NOT NULL,
    progress_percentage INT DEFAULT 0,
    start_date DATE NULL,
    target_date DATE NULL,
    completed_date DATE NULL,
    status ENUM('not_started', 'in_progress', 'almost_done', 'completed') DEFAULT 'not_started',
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (milestone_template_id) REFERENCES milestone_templates(id) ON DELETE CASCADE
);

-- 16. Group milestone tasks table
CREATE TABLE group_milestone_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_milestone_id BIGINT UNSIGNED NOT NULL,
    milestone_task_id BIGINT UNSIGNED NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    completed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    deadline TIMESTAMP NULL,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (group_milestone_id) REFERENCES group_milestones(id) ON DELETE CASCADE,
    FOREIGN KEY (milestone_task_id) REFERENCES milestone_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES students(id) ON DELETE SET NULL
);

-- 17. Defense requests table
CREATE TABLE defense_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    defense_type ENUM('proposal', '60_percent', '100_percent') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'scheduled') DEFAULT 'pending',
    student_message TEXT NULL,
    coordinator_notes TEXT NULL,
    requested_at TIMESTAMP NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

-- 18. Defense schedules table
CREATE TABLE defense_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    stage ENUM('proposal', '60', '100') DEFAULT 'proposal',
    academic_term_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    room VARCHAR(255) NOT NULL,
    remarks TEXT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_term_id) REFERENCES academic_terms(id) ON DELETE CASCADE
);

-- 19. Defense panels table
CREATE TABLE defense_panels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    defense_schedule_id BIGINT UNSIGNED NOT NULL,
    faculty_id BIGINT UNSIGNED NOT NULL,
    role ENUM('chair', 'member', 'adviser', 'coordinator') DEFAULT 'member',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY defense_panels_defense_schedule_id_faculty_id_unique (defense_schedule_id, faculty_id),
    FOREIGN KEY (defense_schedule_id) REFERENCES defense_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 20. Project submissions table
CREATE TABLE project_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    type ENUM('proposal', 'final', 'other') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    teacher_comment TEXT NULL,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 21. Notifications table
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    role VARCHAR(255) NULL COMMENT 'like coordinator, student, etc.',
    redirect_url VARCHAR(255) NULL COMMENT 'URL to redirect when notification is clicked',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Whether the notification has been read',
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 22. Jobs table (Laravel Queue)
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX jobs_queue_index (queue)
);

-- 23. Job batches table
CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
);

-- 24. Failed jobs table
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 25. Cache table
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);

-- 26. Cache locks table
CREATE TABLE cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);

-- 27. Class models table (empty table from migration)
CREATE TABLE class_models (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Insert sample data for testing

-- Academic Terms
INSERT INTO academic_terms (school_year, semester, is_active, is_archived, created_at, updated_at) VALUES
('2024-2025', 'First Semester', TRUE, FALSE, NOW(), NOW()),
('2024-2025', 'Second Semester', FALSE, FALSE, NOW(), NOW()),
('2023-2024', 'Second Semester', FALSE, TRUE, NOW(), NOW());

-- Roles
INSERT INTO roles (name, created_at, updated_at) VALUES
('Chairperson', NOW(), NOW()),
('Coordinator', NOW(), NOW()),
('Adviser', NOW(), NOW()),
('Panelist', NOW(), NOW()),
('Teacher', NOW(), NOW());

-- Sample Users (Faculty/Staff)
INSERT INTO users (school_id, name, email, birthday, department, position, role, must_change_password, password, created_at, updated_at) VALUES
('FAC001', 'Dr. Maria Santos', 'maria.santos@university.edu', '1975-03-15', 'Computer Science', 'Department Chair', 'chairperson', FALSE, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('FAC002', 'Prof. Juan Dela Cruz', 'juan.delacruz@university.edu', '1980-07-22', 'Computer Science', 'Professor', 'coordinator', FALSE, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('FAC003', 'Engr. Ana Rodriguez', 'ana.rodriguez@university.edu', '1985-11-08', 'Computer Science', 'Assistant Professor', 'adviser', FALSE, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
('FAC004', 'Dr. Carlos Mendoza', 'carlos.mendoza@university.edu', '1978-05-30', 'Computer Science', 'Professor', 'panelist', FALSE, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Sample Students
INSERT INTO students (student_id, name, email, semester, course, password, must_change_password, created_at, updated_at) VALUES
('2021-12345', 'John Michael Smith', 'john.smith@student.university.edu', '1st', 'Bachelor of Science in Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, NOW(), NOW()),
('2021-12346', 'Sarah Johnson', 'sarah.johnson@student.university.edu', '1st', 'Bachelor of Science in Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, NOW(), NOW()),
('2021-12347', 'Michael Brown', 'michael.brown@student.university.edu', '1st', 'Bachelor of Science in Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, NOW(), NOW()),
('2021-12348', 'Emily Davis', 'emily.davis@student.university.edu', '1st', 'Bachelor of Science in Computer Science', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, NOW(), NOW());

-- Sample Offerings
INSERT INTO offerings (subject_title, subject_code, teacher_name, teacher_id, academic_term_id, created_at, updated_at) VALUES
('Capstone Project 1', 'CS 401', 'Prof. Juan Dela Cruz', 2, 1, NOW(), NOW()),
('Capstone Project 2', 'CS 402', 'Engr. Ana Rodriguez', 3, 1, NOW(), NOW()),
('Software Engineering', 'CS 301', 'Dr. Carlos Mendoza', 4, 1, NOW(), NOW());

-- Sample Groups
INSERT INTO groups (name, description, adviser_id, offering_id, academic_term_id, created_at, updated_at) VALUES
('Team Alpha', 'Mobile Application Development', 3, 1, 1, NOW(), NOW()),
('Team Beta', 'Web-based Management System', 3, 1, 1, NOW(), NOW()),
('Team Gamma', 'IoT Smart Home System', 3, 2, 1, NOW(), NOW());

-- Sample Group Members
INSERT INTO group_members (group_id, student_id, role, created_at, updated_at) VALUES
(1, 1, 'leader', NOW(), NOW()),
(1, 2, 'member', NOW(), NOW()),
(2, 3, 'leader', NOW(), NOW()),
(2, 4, 'member', NOW(), NOW());

-- Sample Milestone Templates
INSERT INTO milestone_templates (name, description, status, created_at, updated_at) VALUES
('Capstone Project Milestones', 'Standard milestones for capstone projects', 'active', NOW(), NOW()),
('Software Development Lifecycle', 'SDLC milestones for software projects', 'active', NOW(), NOW());

-- Sample Milestone Tasks
INSERT INTO milestone_tasks (milestone_template_id, name, description, order, is_completed, created_at, updated_at) VALUES
(1, 'Project Proposal', 'Submit initial project proposal', 1, FALSE, NOW(), NOW()),
(1, 'Requirements Analysis', 'Complete requirements gathering and analysis', 2, FALSE, NOW(), NOW()),
(1, 'System Design', 'Create system architecture and design documents', 3, FALSE, NOW(), NOW()),
(1, 'Implementation', 'Develop the core system functionality', 4, FALSE, NOW(), NOW()),
(1, 'Testing', 'Conduct comprehensive testing', 5, FALSE, NOW(), NOW()),
(1, 'Documentation', 'Complete project documentation', 6, FALSE, NOW(), NOW());

-- Sample Group Milestones
INSERT INTO group_milestones (group_id, milestone_template_id, progress_percentage, start_date, target_date, status, created_at, updated_at) VALUES
(1, 1, 25, '2024-09-01', '2024-12-15', 'in_progress', NOW(), NOW()),
(2, 1, 15, '2024-09-01', '2024-12-15', 'in_progress', NOW(), NOW()),
(3, 1, 10, '2024-09-01', '2024-12-15', 'not_started', NOW(), NOW());

-- Sample Notifications
INSERT INTO notifications (title, description, role, redirect_url, is_read, user_id, created_at, updated_at) VALUES
('New Defense Request', 'Team Alpha has submitted a defense request for Proposal Defense', 'coordinator', '/defense-requests', FALSE, 2, NOW(), NOW()),
('Milestone Update', 'Team Beta has updated their project milestone progress', 'adviser', '/groups/2/milestones', FALSE, 3, NOW(), NOW()),
('Project Submission', 'John Michael Smith has submitted a project file', 'adviser', '/submissions', FALSE, 3, NOW(), NOW());

-- Sample Defense Requests
INSERT INTO defense_requests (group_id, defense_type, status, student_message, requested_at, created_at, updated_at) VALUES
(1, 'proposal', 'pending', 'We are ready for our proposal defense. Please schedule us for next week.', NOW(), NOW(), NOW()),
(2, 'proposal', 'approved', 'Requesting proposal defense scheduling.', NOW(), NOW(), NOW());

-- Sample Defense Schedules
INSERT INTO defense_schedules (group_id, stage, academic_term_id, start_at, end_at, room, remarks, status, created_at, updated_at) VALUES
(2, 'proposal', 1, '2024-10-15 09:00:00', '2024-10-15 11:00:00', 'Room 101', 'Proposal defense for Team Beta', 'scheduled', NOW(), NOW());

-- Sample Defense Panels
INSERT INTO defense_panels (defense_schedule_id, faculty_id, role, created_at, updated_at) VALUES
(1, 1, 'chair', NOW(), NOW()),
(1, 2, 'coordinator', NOW(), NOW()),
(1, 4, 'member', NOW(), NOW());

-- Sample Project Submissions
INSERT INTO project_submissions (student_id, file_path, type, status, submitted_at, created_at, updated_at) VALUES
(1, 'uploads/proposals/team_alpha_proposal.pdf', 'proposal', 'pending', NOW(), NOW(), NOW()),
(3, 'uploads/proposals/team_beta_proposal.pdf', 'proposal', 'approved', NOW(), NOW(), NOW());

-- Sample Enrollments
INSERT INTO enrollments (student_id, semester, created_at, updated_at) VALUES
(1, '1st Semester 2024-2025', NOW(), NOW()),
(2, '1st Semester 2024-2025', NOW(), NOW()),
(3, '1st Semester 2024-2025', NOW(), NOW()),
(4, '1st Semester 2024-2025', NOW(), NOW());

-- Sample Offering-Student relationships
INSERT INTO offering_student (offering_id, student_id, created_at, updated_at) VALUES
(1, 1, NOW(), NOW()),
(1, 2, NOW(), NOW()),
(1, 3, NOW(), NOW()),
(1, 4, NOW(), NOW());

-- Sample User Roles
INSERT INTO user_roles (user_id, role, created_at, updated_at) VALUES
(1, 'chairperson', NOW(), NOW()),
(2, 'coordinator', NOW(), NOW()),
(3, 'adviser', NOW(), NOW()),
(4, 'panelist', NOW(), NOW());

-- Create indexes for better performance
CREATE INDEX idx_users_school_id ON users(school_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_students_student_id ON students(student_id);
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_groups_adviser_id ON groups(adviser_id);
CREATE INDEX idx_defense_requests_group_id ON defense_requests(group_id);
CREATE INDEX idx_defense_requests_status ON defense_requests(status);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);

-- Show completion message
SELECT 'CapTracks database schema created successfully!' AS message;
SELECT 'Sample data inserted for testing purposes.' AS message;
SELECT 'Total tables created: 27' AS message;
