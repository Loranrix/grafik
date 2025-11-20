<?php
/**
 * GRAFIK - Classe AuditLog
 * Gestion des logs d'audit et de sécurité
 */

require_once __DIR__ . '/Firebase.php';
require_once __DIR__ . '/Employee.php';

class AuditLog {
    private $firebase;
    private $employee;

    public function __construct() {
        $this->firebase = Firebase::getInstance();
        $this->employee = new Employee();
    }

    /**
     * Logger une tentative de connexion employé
     */
    public function logEmployeeLogin($employee_id, $data) {
        return $this->firebase->logEmployeeLogin($employee_id, $data);
    }

    /**
     * Logger une action admin
     */
    public function logAdminAction($admin_id, $action_type, $description, $target_type = null, $target_id = null, $old_values = null, $new_values = null) {
        return $this->firebase->logAdminAction($admin_id, $action_type, $description, $target_type, $target_id, $old_values, $new_values);
    }

    /**
     * Enregistrer une tentative PIN échouée
     */
    public function logFailedPinAttempt($employee_id, $qr_code, $pin_entered, $device_id) {
        return $this->firebase->logFailedPinAttempt($employee_id, $qr_code, $pin_entered, $device_id);
    }

    /**
     * Vérifier si un appareil est verrouillé
     */
    public function isDeviceLocked($employee_id, $device_id) {
        // Pour Firebase, on vérifie les tentatives récentes
        $failedLogs = $this->firebase->getRecentLoginLogs(1, 1000); // Dernière heure
        
        $attempts = 0;
        $settings = new SecuritySettings();
        $maxAttempts = $settings->getMaxPinAttempts();
        $lockoutMinutes = $settings->getPinLockoutMinutes();
        
        foreach ($failedLogs as $log) {
            if (isset($log['employee_id']) && $log['employee_id'] == $employee_id &&
                isset($log['device_id']) && $log['device_id'] === $device_id &&
                (!isset($log['success']) || !$log['success'])) {
                $attempts++;
            }
        }
        
        // Si on atteint le maximum, vérifier si on est dans la période de verrouillage
        if ($attempts >= $maxAttempts) {
            // Vérifier la dernière tentative
            $lastFailed = null;
            foreach ($failedLogs as $log) {
                if (isset($log['employee_id']) && $log['employee_id'] == $employee_id &&
                    isset($log['device_id']) && $log['device_id'] === $device_id &&
                    (!isset($log['success']) || !$log['success'])) {
                    $lastFailed = $log;
                    break;
                }
            }
            
            if ($lastFailed && isset($lastFailed['created_at'])) {
                $lastAttempt = strtotime($lastFailed['created_at']);
                $lockoutUntil = $lastAttempt + ($lockoutMinutes * 60);
                return time() < $lockoutUntil;
            }
        }
        
        return false;
    }

    /**
     * Réinitialiser les tentatives échouées après succès
     */
    public function resetFailedAttempts($employee_id, $device_id) {
        // Dans Firebase, on ne supprime pas les logs, mais on peut les marquer
        // Pour l'instant, on ne fait rien car les logs sont historiques
        return true;
    }

    /**
     * Récupérer les logs de connexion d'un employé
     */
    public function getEmployeeLoginLogs($employee_id, $limit = 50) {
        return $this->firebase->getEmployeeLoginLogs($employee_id, $limit);
    }

    /**
     * Récupérer tous les logs de connexion récents
     */
    public function getRecentLoginLogs($hours = 24, $limit = 100) {
        $logs = $this->firebase->getRecentLoginLogs($hours, $limit);
        
        // Enrichir avec les noms des employés
        foreach ($logs as &$log) {
            if (isset($log['employee_id'])) {
                $emp = $this->employee->getById($log['employee_id']);
                if ($emp) {
                    $log['first_name'] = $emp['first_name'] ?? '';
                    $log['last_name'] = $emp['last_name'] ?? '';
                }
            }
        }
        
        return $logs;
    }

    /**
     * Récupérer les tentatives échouées récentes
     */
    public function getRecentFailedAttempts($hours = 24) {
        $allLogs = $this->firebase->getRecentLoginLogs($hours, 1000);
        
        $failed = [];
        foreach ($allLogs as $log) {
            if (!isset($log['success']) || !$log['success']) {
                if (isset($log['employee_id'])) {
                    $emp = $this->employee->getById($log['employee_id']);
                    if ($emp) {
                        $log['first_name'] = $emp['first_name'] ?? '';
                        $log['last_name'] = $emp['last_name'] ?? '';
                    }
                }
                $failed[] = $log;
            }
        }
        
        return $failed;
    }

    /**
     * Récupérer les logs d'actions admin
     */
    public function getAdminActionLogs($admin_id = null, $limit = 100) {
        return $this->firebase->getAdminActionLogs($admin_id, $limit);
    }

    /**
     * Obtenir les statistiques de sécurité
     */
    public function getSecurityStats() {
        return $this->firebase->getSecurityStats();
    }
}
