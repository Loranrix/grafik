<?php
/**
 * GRAFIK - Classe Consumption
 * Gestion des consommations employés
 */

class Consumption {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ajouter une consommation
     */
    public function add($employee_id, $item_name, $original_price, $discount_percent = 50) {
        $discounted_price = $original_price * (1 - $discount_percent / 100);
        $consumption_date = date('Y-m-d');
        $consumption_time = date('H:i:s');
        
        $this->db->query(
            "INSERT INTO consumptions 
             (employee_id, item_name, original_price, discounted_price, discount_percent, consumption_date, consumption_time) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$employee_id, $item_name, $original_price, $discounted_price, $discount_percent, $consumption_date, $consumption_time]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Récupérer les consommations d'un employé
     */
    public function getForEmployee($employee_id, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM consumptions 
             WHERE employee_id = ? 
             ORDER BY consumption_date DESC, consumption_time DESC
             LIMIT ?",
            [$employee_id, $limit]
        );
    }

    /**
     * Récupérer les consommations d'un employé pour une période
     */
    public function getForEmployeePeriod($employee_id, $start_date, $end_date) {
        return $this->db->fetchAll(
            "SELECT * FROM consumptions 
             WHERE employee_id = ? AND consumption_date BETWEEN ? AND ?
             ORDER BY consumption_date DESC, consumption_time DESC",
            [$employee_id, $start_date, $end_date]
        );
    }

    /**
     * Calculer le total des consommations pour un employé sur une période
     */
    public function getTotalForPeriod($employee_id, $start_date, $end_date) {
        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as count,
                SUM(original_price) as total_original,
                SUM(discounted_price) as total_discounted
             FROM consumptions 
             WHERE employee_id = ? AND consumption_date BETWEEN ? AND ?",
            [$employee_id, $start_date, $end_date]
        );
        
        return $result ?: ['count' => 0, 'total_original' => 0, 'total_discounted' => 0];
    }

    /**
     * Supprimer une consommation
     */
    public function delete($id) {
        $this->db->query("DELETE FROM consumptions WHERE id = ?", [$id]);
    }

    /**
     * Récupérer les consommations du jour pour un employé
     */
    public function getTodayForEmployee($employee_id) {
        return $this->db->fetchAll(
            "SELECT * FROM consumptions 
             WHERE employee_id = ? AND consumption_date = CURDATE()
             ORDER BY consumption_time DESC",
            [$employee_id]
        );
    }

    /**
     * Récupérer les consommations du mois pour un employé
     */
    public function getMonthForEmployee($employee_id, $year, $month) {
        $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $this->getForEmployeePeriod($employee_id, $start_date, $end_date);
    }
}

