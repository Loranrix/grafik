-- Migration 006: Tables d'audit et logs
-- Date: 2025-11-16

-- Logs des connexions employés
CREATE TABLE IF NOT EXISTS employee_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    qr_code VARCHAR(255),
    pin_entered VARCHAR(10),
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    device_id VARCHAR(255),
    device_info TEXT,
    ip_address VARCHAR(45),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs des actions admin
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT,
    target_type VARCHAR(50),
    target_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs des tentatives PIN échouées
CREATE TABLE IF NOT EXISTS failed_pin_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    qr_code VARCHAR(255),
    pin_entered VARCHAR(10),
    device_id VARCHAR(255),
    ip_address VARCHAR(45),
    attempts_count INT DEFAULT 1,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_device_id (device_id),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

