-- Migration 005: Paramètres de sécurité
-- Date: 2025-11-16

CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('boolean', 'integer', 'string', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres par défaut
INSERT INTO security_settings (setting_key, setting_value, setting_type, description) VALUES
('device_restriction_enabled', 'false', 'boolean', 'Activer la restriction par appareil'),
('gps_verification_enabled', 'false', 'boolean', 'Activer la vérification GPS'),
('gps_latitude', '56.9496', 'string', 'Latitude du restaurant'),
('gps_longitude', '24.1052', 'string', 'Longitude du restaurant'),
('gps_radius_meters', '50', 'integer', 'Rayon autorisé en mètres'),
('multi_device_enabled', 'true', 'boolean', 'Autoriser plusieurs appareils par employé'),
('max_pin_attempts', '3', 'integer', 'Nombre maximum de tentatives PIN'),
('pin_attempt_lockout_minutes', '15', 'integer', 'Durée du verrouillage après échecs (minutes)'),
('early_punch_tolerance_minutes', '15', 'integer', 'Tolérance arrivée anticipée (minutes)'),
('late_punch_tolerance_minutes', '30', 'integer', 'Tolérance retard (minutes)'),
('notifications_enabled', 'false', 'boolean', 'Activer les notifications'),
('admin_notification_email', '', 'string', 'Email admin pour notifications');

