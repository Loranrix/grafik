<?php
/**
 * GRAFIK - Classe SecuritySettings
 * Gestion des paramètres de sécurité configurables
 */

class SecuritySettings {
    private $db;
    private static $cache = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir un paramètre
     */
    public function get($key, $default = null) {
        // Vérifier le cache
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $setting = $this->db->fetchOne(
            "SELECT setting_value, setting_type FROM security_settings WHERE setting_key = ?",
            [$key]
        );

        if (!$setting) {
            return $default;
        }

        // Convertir selon le type
        $value = $this->convertValue($setting['setting_value'], $setting['setting_type']);
        
        // Mettre en cache
        self::$cache[$key] = $value;
        
        return $value;
    }

    /**
     * Définir un paramètre
     */
    public function set($key, $value, $type = 'string') {
        $valueStr = $this->valueToString($value, $type);
        
        $existing = $this->db->fetchOne(
            "SELECT id FROM security_settings WHERE setting_key = ?",
            [$key]
        );

        if ($existing) {
            $this->db->query(
                "UPDATE security_settings SET setting_value = ?, setting_type = ? WHERE setting_key = ?",
                [$valueStr, $type, $key]
            );
        } else {
            $this->db->query(
                "INSERT INTO security_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)",
                [$key, $valueStr, $type]
            );
        }

        // Mettre à jour le cache
        self::$cache[$key] = $value;

        return true;
    }

    /**
     * Obtenir tous les paramètres
     */
    public function getAll() {
        $settings = $this->db->fetchAll("SELECT * FROM security_settings ORDER BY setting_key");
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = [
                'value' => $this->convertValue($setting['setting_value'], $setting['setting_type']),
                'type' => $setting['setting_type'],
                'description' => $setting['description']
            ];
        }
        
        return $result;
    }

    /**
     * Mettre à jour plusieurs paramètres
     */
    public function updateMultiple($settings) {
        foreach ($settings as $key => $data) {
            $this->set($key, $data['value'], $data['type'] ?? 'string');
        }
        return true;
    }

    /**
     * Convertir une valeur selon son type
     */
    private function convertValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return intval($value);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Convertir une valeur en string pour stockage
     */
    private function valueToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return strval($value);
            case 'json':
                return json_encode($value);
            default:
                return strval($value);
        }
    }

    /**
     * Vérifier si la restriction par appareil est activée
     */
    public function isDeviceRestrictionEnabled() {
        return $this->get('device_restriction_enabled', false);
    }

    /**
     * Vérifier si la vérification GPS est activée
     */
    public function isGPSVerificationEnabled() {
        return $this->get('gps_verification_enabled', false);
    }

    /**
     * Obtenir les coordonnées GPS du restaurant
     */
    public function getRestaurantLocation() {
        return [
            'latitude' => floatval($this->get('gps_latitude', '0')),
            'longitude' => floatval($this->get('gps_longitude', '0')),
            'radius' => intval($this->get('gps_radius_meters', 50))
        ];
    }

    /**
     * Vérifier si un employé peut utiliser plusieurs appareils
     */
    public function isMultiDeviceEnabled() {
        return $this->get('multi_device_enabled', true);
    }

    /**
     * Obtenir le nombre maximum de tentatives PIN
     */
    public function getMaxPinAttempts() {
        return intval($this->get('max_pin_attempts', 3));
    }

    /**
     * Obtenir la durée du verrouillage après échecs
     */
    public function getPinLockoutMinutes() {
        return intval($this->get('pin_attempt_lockout_minutes', 15));
    }

    /**
     * Vérifier si les notifications sont activées
     */
    public function areNotificationsEnabled() {
        return $this->get('notifications_enabled', false);
    }

    /**
     * Obtenir l'email admin pour les notifications
     */
    public function getAdminNotificationEmail() {
        return $this->get('admin_notification_email', '');
    }

    /**
     * Obtenir les tolérances de pointage
     */
    public function getPunchTolerances() {
        return [
            'early_minutes' => intval($this->get('early_punch_tolerance_minutes', 15)),
            'late_minutes' => intval($this->get('late_punch_tolerance_minutes', 30))
        ];
    }
}

