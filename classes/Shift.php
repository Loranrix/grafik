<?php
/**
 * GRAFIK - Classe Shift
 * Gestion des plannings
 */

class Shift {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Créer un nouveau shift
     */
    public function create($employee_id, $shift_date, $start_time, $end_time) {
        $this->db->query(
            "INSERT INTO shifts (employee_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?)",
            [$employee_id, $shift_date, $start_time, $end_time]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Mettre à jour un shift
     */
    public function update($id, $shift_date, $start_time, $end_time) {
        $this->db->query(
            "UPDATE shifts SET shift_date = ?, start_time = ?, end_time = ? WHERE id = ?",
            [$shift_date, $start_time, $end_time, $id]
        );
    }

    /**
     * Supprimer un shift
     */
    public function delete($id) {
        $this->db->query("DELETE FROM shifts WHERE id = ?", [$id]);
    }

    /**
     * Récupérer tous les shifts d'un employé pour un mois
     */
    public function getByEmployeeMonth($employee_id, $year, $month) {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $this->db->fetchAll(
            "SELECT * FROM shifts WHERE employee_id = ? AND shift_date BETWEEN ? AND ? ORDER BY shift_date, start_time",
            [$employee_id, $start_date, $end_date]
        );
    }

    /**
     * Récupérer tous les shifts pour une date
     */
    public function getByDate($date) {
        return $this->db->fetchAll(
            "SELECT s.*, e.first_name, e.last_name 
             FROM shifts s 
             JOIN employees e ON s.employee_id = e.id 
             WHERE s.shift_date = ? 
             ORDER BY s.start_time, e.last_name",
            [$date]
        );
    }

    /**
     * Récupérer tous les shifts pour un mois (tous employés)
     */
    public function getAllByMonth($year, $month) {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $this->db->fetchAll(
            "SELECT s.*, e.first_name, e.last_name 
             FROM shifts s 
             JOIN employees e ON s.employee_id = e.id 
             WHERE s.shift_date BETWEEN ? AND ? 
             ORDER BY s.shift_date, s.start_time, e.last_name",
            [$start_date, $end_date]
        );
    }

    /**
     * Récupérer un shift spécifique
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM shifts WHERE id = ?",
            [$id]
        );
    }

    /**
     * Vérifier si un shift existe pour un employé à une date
     */
    public function existsForEmployeeDate($employee_id, $date) {
        $result = $this->db->fetchOne(
            "SELECT id FROM shifts WHERE employee_id = ? AND shift_date = ?",
            [$employee_id, $date]
        );
        return (bool)$result;
    }
}

