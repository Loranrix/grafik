<?php
/**
 * GRAFIK - Classe AuditLog
 * Gestion des logs d'audit et de sécurité
 */

class AuditLog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Logger une tentative de connexion employé
     */
    public function logEmployeeLogin($employee_id, $data) {
        $this->db->query(
            "INSERT INTO employee_login_logs 
             (employee_id, qr_code, pin_entered, success, failure_reason, device_id, device_info, ip_address, gps_latitude, gps_longitude) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $employee_id,
                $data['qr_code'] ?? null,
                $data['pin_entered'] ?? null,
                $data['success'] ?? false,
                $data['failure_reason'] ?? null,
                $data['device_id'] ?? null,
                $data['device_info'] ?? null,
                $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $data['gps_latitude'] ?? null,
                $data['gps_longitude'] ?? null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Logger une action admin
     */
    public function logAdminAction($admin_id, $action_type, $description, $target_type = null, $target_id = null, $old_values = null, $new_values = null) {
        $this->db->query(
            "INSERT INTO admin_action_logs 
             (admin_id, action_type, action_description, target_type, target_id, old_values, new_values, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $admin_id,
                $action_type,
                $description,
                $target_type,
                $target_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Enregistrer une tentative PIN échouée
     */
    public function logFailedPinAttempt($employee_id, $qr_code, $pin_entered, $device_id) {
        // Vérifier s'il existe déjà un enregistrement récent
        $existing = $this->db->fetchOne(
            "SELECT * FROM failed_pin_attempts 
             WHERE employee_id = ? AND device_id = ? 
             AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY created_at DESC LIMIT 1",
            [$employee_id, $device_id]
        );

        if ($existing) {
            // Incrémenter le compteur
            $newCount = $existing['attempts_count'] + 1;
            $lockedUntil = null;
            
            // Si on atteint le maximum, verrouiller
            $settings = new SecuritySettings();
            $maxAttempts = $settings->getMaxPinAttempts();
            $lockoutMinutes = $settings->getPinLockoutMinutes();
            
            if ($newCount >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
            }
            
            $this->db->query(
                "UPDATE failed_pin_attempts 
                 SET attempts_count = ?, locked_until = ? 
                 WHERE id = ?",
                [$newCount, $lockedUntil, $existing['id']]
            );
            
            return $existing['id'];
        } else {
            // Créer un nouvel enregistrement
            $this->db->query(
                "INSERT INTO failed_pin_attempts 
                 (employee_id, qr_code, pin_entered, device_id, ip_address) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $employee_id,
                    $qr_code,
                    $pin_entered,
                    $device_id,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]
            );
            
            return $this->db->lastInsertId();
        }
    }

    /**
     * Vérifier si un appareil est verrouillé
     */
    public function isDeviceLocked($employee_id, $device_id) {
        $record = $this->db->fetchOne(
            "SELECT * FROM failed_pin_attempts 
             WHERE employee_id = ? AND device_id = ? 
             AND locked_until IS NOT NULL 
             AND locked_until > NOW()
             ORDER BY created_at DESC LIMIT 1",
            [$employee_id, $device_id]
        );

        return $record !== null;
    }

    /**
     * Réinitialiser les tentatives échouées après succès
     */
    public function resetFailedAttempts($employee_id, $device_id) {
        $this->db->query(
            "DELETE FROM failed_pin_attempts 
             WHERE employee_id = ? AND device_id = ?",
            [$employee_id, $device_id]
        );
    }

    /**
     * Récupérer les logs de connexion d'un employé
     */
    public function getEmployeeLoginLogs($employee_id, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM employee_login_logs 
             WHERE employee_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?",
            [$employee_id, $limit]
        );
    }

    /**
     * Récupérer tous les logs de connexion récents
     */
    public function getRecentLoginLogs($hours = 24, $limit = 100) {
        return $this->db->fetchAll(
            "SELECT ell.*, e.first_name, e.last_name 
             FROM employee_login_logs ell
             JOIN employees e ON ell.employee_id = e.id
             WHERE ell.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY ell.created_at DESC 
             LIMIT ?",
            [$hours, $limit]
        );
    }

    /**
     * Récupérer les tentatives échouées récentes
     */
    public function getRecentFailedAttempts($hours = 24) {
        return $this->db->fetchAll(
            "SELECT fpa.*, e.first_name, e.last_name 
             FROM failed_pin_attempts fpa
             LEFT JOIN employees e ON fpa.employee_id = e.id
             WHERE fpa.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY fpa.created_at DESC",
            [$hours]
        );
    }

    /**
     * Récupérer les logs d'actions admin
     */
    public function getAdminActionLogs($admin_id = null, $limit = 100) {
        if ($admin_id) {
            return $this->db->fetchAll(
                "SELECT * FROM admin_action_logs 
                 WHERE admin_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?",
                [$admin_id, $limit]
            );
        } else {
            return $this->db->fetchAll(
                "SELECT aal.*, a.username 
                 FROM admin_action_logs aal
                 JOIN admins a ON aal.admin_id = a.id
                 ORDER BY aal.created_at DESC 
                 LIMIT ?",
                [$limit]
            );
        }
    }

    /**
     * Obtenir les statistiques de sécurité
     */
    public function getSecurityStats() {
        $stats = [];
        
        // Total tentatives connexion 24h
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total, 
                    SUM(success) as successful, 
                    SUM(NOT success) as failed 
             FROM employee_login_logs 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $stats['login_attempts_24h'] = $result;
        
        // Appareils verrouillés actuellement
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM failed_pin_attempts 
             WHERE locked_until IS NOT NULL AND locked_until > NOW()"
        );
        $stats['locked_devices'] = $result['count'];
        
        // Actions admin 24h
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM admin_action_logs 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $stats['admin_actions_24h'] = $result['count'];
        
        return $stats;
    }
}

