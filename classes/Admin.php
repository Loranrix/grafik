<?php
/**
 * GRAFIK - Classe Admin
 * Gestion des administrateurs
 */

require_once __DIR__ . '/Firebase.php';

class Admin {
    private $firebase;

    public function __construct() {
        $this->firebase = Firebase::getInstance();
    }

    /**
     * Authentifier un administrateur
     */
    public function authenticate($username, $password) {
        // Pour la version simple, on utilise la constante
        if ($username === ADMIN_USER && $password === ADMIN_PASS) {
            // Mettre à jour last_login
            $admin = $this->getByUsername($username);
            if (!$admin) {
                // Créer l'admin s'il n'existe pas
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $admin_id = $this->create($username, $hashed_password);
                if ($admin_id) {
                    $this->updateLastLogin($admin_id);
                }
            } else {
                $this->updateLastLogin($admin['id']);
            }
            return true;
        }
        return false;
    }

    /**
     * Récupérer un admin par username
     */
    public function getByUsername($username) {
        return $this->firebase->getAdminByUsername($username);
    }

    /**
     * Créer un admin
     */
    public function create($username, $hashed_password) {
        $admin_id = 'admin_' . time() . '_' . uniqid();
        
        $data = [
            'username' => $username,
            'password' => $hashed_password,
            'created_at' => date('Y-m-d\TH:i:s'),
            'last_login' => null
        ];
        
        if ($this->firebase->saveAdmin($admin_id, $data)) {
            return $admin_id;
        }
        
        return false;
    }

    /**
     * Mettre à jour last_login
     */
    public function updateLastLogin($admin_id) {
        return $this->firebase->updateAdminLastLogin($admin_id);
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Connecter l'utilisateur
     */
    public static function login($username) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
    }

    /**
     * Déconnecter l'utilisateur
     */
    public static function logout() {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
        session_destroy();
    }
}
