<?php
/**
 * GRAFIK - Classe Employee
 * Gestion des employés
 */

class Employee {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les employés actifs
     */
    public function getAll($active_only = true) {
        $sql = "SELECT * FROM employees";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY last_name, first_name";
        return $this->db->fetchAll($sql);
    }

    /**
     * Récupérer un employé par ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM employees WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupérer un employé par PIN
     */
    public function getByPin($pin) {
        return $this->db->fetchOne(
            "SELECT * FROM employees WHERE pin = ? AND is_active = 1",
            [$pin]
        );
    }

    /**
     * Récupérer un employé par QR code
     */
    public function getByQr($qr_code) {
        return $this->db->fetchOne(
            "SELECT * FROM employees WHERE qr_code = ? AND is_active = 1",
            [$qr_code]
        );
    }

    /**
     * Créer un nouvel employé
     */
    public function create($first_name, $last_name, $pin) {
        // Générer un QR code unique
        $qr_code = $this->generateUniqueQr();
        
        $this->db->query(
            "INSERT INTO employees (first_name, last_name, pin, qr_code) VALUES (?, ?, ?, ?)",
            [$first_name, $last_name, $pin, $qr_code]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Mettre à jour un employé
     */
    public function update($id, $first_name, $last_name, $pin = null) {
        if ($pin !== null) {
            $this->db->query(
                "UPDATE employees SET first_name = ?, last_name = ?, pin = ? WHERE id = ?",
                [$first_name, $last_name, $pin, $id]
            );
        } else {
            $this->db->query(
                "UPDATE employees SET first_name = ?, last_name = ? WHERE id = ?",
                [$first_name, $last_name, $id]
            );
        }
    }

    /**
     * Activer/désactiver un employé
     */
    public function setActive($id, $is_active) {
        $this->db->query(
            "UPDATE employees SET is_active = ? WHERE id = ?",
            [$is_active ? 1 : 0, $id]
        );
    }

    /**
     * Supprimer un employé
     */
    public function delete($id) {
        $this->db->query("DELETE FROM employees WHERE id = ?", [$id]);
    }

    /**
     * Générer un QR code unique
     */
    private function generateUniqueQr() {
        do {
            $qr_code = bin2hex(random_bytes(16));
            $exists = $this->db->fetchOne(
                "SELECT id FROM employees WHERE qr_code = ?",
                [$qr_code]
            );
        } while ($exists);
        
        return $qr_code;
    }

    /**
     * Vérifier si un PIN existe déjà
     */
    public function pinExists($pin, $exclude_id = null) {
        if ($exclude_id) {
            $result = $this->db->fetchOne(
                "SELECT id FROM employees WHERE pin = ? AND id != ?",
                [$pin, $exclude_id]
            );
        } else {
            $result = $this->db->fetchOne(
                "SELECT id FROM employees WHERE pin = ?",
                [$pin]
            );
        }
        return (bool)$result;
    }
}

