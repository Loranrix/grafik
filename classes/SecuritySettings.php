<?php
/**
 * GRAFIK - Classe SecuritySettings
 * Gestion des paramètres de sécurité configurables
 */

require_once __DIR__ . '/Firebase.php';

class SecuritySettings {
    private $firebase;
    private static $cache = [];

    public function __construct() {
        $this->firebase = Firebase::getInstance();
    }

    /**
     * Obtenir un paramètre
     */
    public function get($key, $default = null) {
        // Vérifier le cache
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = $this->firebase->getSecuritySetting($key, $default);
        
        // Mettre en cache
        self::$cache[$key] = $value;
        
        return $value;
    }

    /**
     * Définir un paramètre
     */
    public function set($key, $value, $type = 'string') {
        $description = $this->getDescription($key);
        $result = $this->firebase->setSecuritySetting($key, $value, $type, $description);
        
        if ($result) {
            // Mettre à jour le cache
            self::$cache[$key] = $value;
        }
        
        return $result;
    }

    /**
     * Obtenir tous les paramètres
     */
    public function getAll() {
        $settings = $this->firebase->getAllSecuritySettings();
        
        $result = [];
        foreach ($settings as $key => $setting) {
            $result[$key] = [
                'value' => $setting['value'],
                'type' => $setting['type'],
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
     * Obtenir la description d'un paramètre
     */
    private function getDescription($key) {
        $descriptions = [
            'device_restriction_enabled' => 'Restriction par appareil activée',
            'multi_device_enabled' => 'Multi-appareil activé',
            'gps_verification_enabled' => 'Vérification GPS activée',
            'gps_latitude' => 'Latitude du restaurant',
            'gps_longitude' => 'Longitude du restaurant',
            'gps_radius_meters' => 'Rayon GPS en mètres',
            'max_pin_attempts' => 'Nombre maximum de tentatives PIN',
            'pin_attempt_lockout_minutes' => 'Durée de verrouillage après échecs (minutes)',
            'early_punch_tolerance_minutes' => 'Tolérance pointage anticipé (minutes)',
            'late_punch_tolerance_minutes' => 'Tolérance pointage retard (minutes)',
            'notifications_enabled' => 'Notifications activées',
            'admin_notification_email' => 'Email admin pour notifications'
        ];
        
        return $descriptions[$key] ?? '';
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
