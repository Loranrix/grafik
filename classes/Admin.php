<?php
/**
 * GRAFIK - Classe Admin
 * Gestion des administrateurs
 */

class Admin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
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
                $this->create($username, $hashed_password);
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
        return $this->db->fetchOne(
            "SELECT * FROM admins WHERE username = ?",
            [$username]
        );
    }

    /**
     * Créer un admin
     */
    public function create($username, $hashed_password) {
        $this->db->query(
            "INSERT INTO admins (username, password) VALUES (?, ?)",
            [$username, $hashed_password]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Mettre à jour last_login
     */
    public function updateLastLogin($admin_id) {
        $this->db->query(
            "UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?",
            [$admin_id]
        );
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

