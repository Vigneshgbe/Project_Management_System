-- Padak CRM Database Schema
-- Run this once to set up the database

CREATE DATABASE IF NOT EXISTS projects_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projects_management;

-- Users & Roles
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','member') DEFAULT 'member',
    avatar VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- CRM Contacts/Clients
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    company VARCHAR(150) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    type ENUM('client','lead','partner','vendor') DEFAULT 'lead',
    status ENUM('active','inactive','prospect') DEFAULT 'prospect',
    notes TEXT DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Projects
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    status ENUM('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    budget DECIMAL(12,2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'LKR',
    progress INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Project Members
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tasks
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(250) NOT NULL,
    description TEXT DEFAULT NULL,
    project_id INT DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    created_by INT NOT NULL,
    status ENUM('todo','in_progress','review','done') DEFAULT 'todo',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    due_date DATE DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Task Comments
CREATE TABLE task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Documents
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(250) NOT NULL,
    description TEXT DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size BIGINT DEFAULT 0,
    file_type VARCHAR(100) DEFAULT NULL,
    project_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'General',
    access ENUM('all','admin','manager') DEFAULT 'all',
    uploaded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Activity Log
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed: Default admin user (password: Admin@123)
INSERT INTO users (name, email, password, role, department) VALUES
('Padak Admin', 'admin@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Management'),
('Thiki', 'thiki@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Leadership'),
('Vignesh', 'vignesh@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Development');

-- Note: Default password for all seeded users is: password
-- Change immediately after first login!