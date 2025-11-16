<?php
/**
 * GRAFIK - Classe Firebase
 * Gestion de la connexion et des opérations Firebase
 */

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Firebase {
    private static $instance = null;
    private $database = null;
    private $auth = null;
    private $isConnected = false;

    private function __construct() {
        $this->connect();
    }

    /**
     * Obtenir l'instance singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connexion à Firebase
     */
    private function connect() {
        try {
            $configPath = __DIR__ . '/../firebase-config.json';
            
            if (!file_exists($configPath)) {
                throw new Exception("Fichier de configuration Firebase introuvable : $configPath");
            }

            $serviceAccount = ServiceAccount::fromJsonFile($configPath);
            
            $firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->withDatabaseUri('https://' . $serviceAccount->getProjectId() . '.firebaseio.com');
            
            $this->database = $firebase->createDatabase();
            $this->auth = $firebase->createAuth();
            $this->isConnected = true;
            
        } catch (Exception $e) {
            error_log("Firebase Connection Error: " . $e->getMessage());
            $this->isConnected = false;
        }
    }

    /**
     * Vérifier si Firebase est connecté
     */
    public function isConnected() {
        return $this->isConnected;
    }

    /**
     * Obtenir la référence de la base de données
     */
    public function getDatabase() {
        if (!$this->isConnected) {
            throw new Exception("Firebase n'est pas connecté");
        }
        return $this->database;
    }

    /**
     * Obtenir la référence d'authentification
     */
    public function getAuth() {
        if (!$this->isConnected) {
            throw new Exception("Firebase n'est pas connecté");
        }
        return $this->auth;
    }

    /**
     * Sauvegarder un employé dans Firebase
     */
    public function saveEmployee($employee_id, $data) {
        try {
            $ref = $this->database->getReference('grafik/employees/' . $employee_id);
            $ref->set($data);
            return true;
        } catch (Exception $e) {
            error_log("Firebase saveEmployee Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer un employé depuis Firebase
     */
    public function getEmployee($employee_id) {
        try {
            $ref = $this->database->getReference('grafik/employees/' . $employee_id);
            return $ref->getValue();
        } catch (Exception $e) {
            error_log("Firebase getEmployee Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer tous les employés depuis Firebase
     */
    public function getAllEmployees() {
        try {
            $ref = $this->database->getReference('grafik/employees');
            return $ref->getValue() ?? [];
        } catch (Exception $e) {
            error_log("Firebase getAllEmployees Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprimer un employé de Firebase
     */
    public function deleteEmployee($employee_id) {
        try {
            $ref = $this->database->getReference('grafik/employees/' . $employee_id);
            $ref->remove();
            return true;
        } catch (Exception $e) {
            error_log("Firebase deleteEmployee Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sauvegarder un pointage dans Firebase
     */
    public function savePunch($employee_id, $punch_data) {
        try {
            $ref = $this->database->getReference('grafik/punches/' . $employee_id);
            $newPunchRef = $ref->push($punch_data);
            return $newPunchRef->getKey();
        } catch (Exception $e) {
            error_log("Firebase savePunch Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les pointages d'un employé
     */
    public function getPunches($employee_id, $start_date = null, $end_date = null) {
        try {
            $ref = $this->database->getReference('grafik/punches/' . $employee_id);
            $punches = $ref->getValue() ?? [];
            
            // Filtrer par date si spécifié
            if ($start_date || $end_date) {
                $filtered = [];
                foreach ($punches as $key => $punch) {
                    $punch_date = substr($punch['datetime'], 0, 10);
                    
                    $include = true;
                    if ($start_date && $punch_date < $start_date) {
                        $include = false;
                    }
                    if ($end_date && $punch_date > $end_date) {
                        $include = false;
                    }
                    
                    if ($include) {
                        $filtered[$key] = $punch;
                    }
                }
                return $filtered;
            }
            
            return $punches;
        } catch (Exception $e) {
            error_log("Firebase getPunches Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier le PIN d'un employé
     */
    public function verifyPin($employee_id, $pin) {
        try {
            $employee = $this->getEmployee($employee_id);
            if ($employee && isset($employee['pin'])) {
                return $employee['pin'] === $pin;
            }
            return false;
        } catch (Exception $e) {
            error_log("Firebase verifyPin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier le PIN par QR code
     */
    public function verifyPinByQr($qr_code, $pin) {
        try {
            $employees = $this->getAllEmployees();
            foreach ($employees as $id => $employee) {
                if (isset($employee['qr_code']) && $employee['qr_code'] === $qr_code) {
                    if (isset($employee['pin']) && $employee['pin'] === $pin) {
                        return ['success' => true, 'employee_id' => $id, 'employee' => $employee];
                    }
                    return ['success' => false, 'message' => 'PIN incorrect'];
                }
            }
            return ['success' => false, 'message' => 'QR code invalide'];
        } catch (Exception $e) {
            error_log("Firebase verifyPinByQr Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de connexion'];
        }
    }

    /**
     * Enregistrer un appareil
     */
    public function registerDevice($employee_id, $device_id, $device_info) {
        try {
            $ref = $this->database->getReference('grafik/devices/' . $employee_id . '/' . $device_id);
            $existing = $ref->getValue();
            
            if ($existing) {
                // Mettre à jour last_used
                $ref->update([
                    'last_used' => date('Y-m-d\TH:i:s'),
                    'is_allowed' => $device_info['is_allowed'] ?? true
                ]);
            } else {
                // Créer nouveau device
                $ref->set([
                    'name' => $device_info['name'] ?? 'Unknown Device',
                    'first_registered' => date('Y-m-d\TH:i:s'),
                    'last_used' => date('Y-m-d\TH:i:s'),
                    'is_allowed' => $device_info['is_allowed'] ?? true,
                    'user_agent' => $device_info['user_agent'] ?? ''
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Firebase registerDevice Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un appareil est autorisé
     */
    public function isDeviceAllowed($employee_id, $device_id) {
        try {
            $ref = $this->database->getReference('grafik/devices/' . $employee_id . '/' . $device_id);
            $device = $ref->getValue();
            
            if (!$device) {
                // Appareil non enregistré - par défaut autorisé lors de la première utilisation
                return true;
            }
            
            return $device['is_allowed'] ?? false;
        } catch (Exception $e) {
            error_log("Firebase isDeviceAllowed Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir tous les appareils d'un employé
     */
    public function getDevices($employee_id) {
        try {
            $ref = $this->database->getReference('grafik/devices/' . $employee_id);
            return $ref->getValue() ?? [];
        } catch (Exception $e) {
            error_log("Firebase getDevices Error: " . $e->getMessage());
            return [];
        }
    }
}

