<?php
/**
 * GRAFIK - Classe Punch
 * Gestion des pointages
 */

class Punch {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistrer un pointage (arrivée ou départ)
     */
    public function record($employee_id, $type, $datetime = null) {
        if ($datetime === null) {
            $datetime = date('Y-m-d H:i:s');
        }

        // Trouver le shift correspondant si existe
        $shift_id = $this->findShiftForPunch($employee_id, $datetime);

        $this->db->query(
            "INSERT INTO punches (employee_id, punch_type, punch_datetime, shift_id) VALUES (?, ?, ?, ?)",
            [$employee_id, $type, $datetime, $shift_id]
        );

        return $this->db->lastInsertId();
    }

    /**
     * Trouver le shift correspondant pour un pointage
     */
    private function findShiftForPunch($employee_id, $datetime) {
        $date = date('Y-m-d', strtotime($datetime));
        $shift = $this->db->fetchOne(
            "SELECT id FROM shifts WHERE employee_id = ? AND shift_date = ? LIMIT 1",
            [$employee_id, $date]
        );
        return $shift ? $shift['id'] : null;
    }

    /**
     * Récupérer le dernier pointage d'un employé
     */
    public function getLastPunch($employee_id) {
        return $this->db->fetchOne(
            "SELECT * FROM punches WHERE employee_id = ? ORDER BY punch_datetime DESC LIMIT 1",
            [$employee_id]
        );
    }

    /**
     * Récupérer tous les pointages d'un employé pour une date
     */
    public function getByEmployeeAndDate($employee_id, $date) {
        return $this->db->fetchAll(
            "SELECT * FROM punches WHERE employee_id = ? AND DATE(punch_datetime) = ? ORDER BY punch_datetime",
            [$employee_id, $date]
        );
    }

    /**
     * Récupérer tous les pointages d'un employé pour une période
     */
    public function getByEmployeeDateRange($employee_id, $start_date, $end_date) {
        return $this->db->fetchAll(
            "SELECT * FROM punches WHERE employee_id = ? AND DATE(punch_datetime) BETWEEN ? AND ? ORDER BY punch_datetime",
            [$employee_id, $start_date, $end_date]
        );
    }

    /**
     * Calculer les heures travaillées pour un employé pour une date
     */
    public function calculateHours($employee_id, $date) {
        $punches = $this->getByEmployeeAndDate($employee_id, $date);
        
        $total_hours = 0;
        $in_time = null;

        foreach ($punches as $punch) {
            if ($punch['punch_type'] === 'in') {
                $in_time = strtotime($punch['punch_datetime']);
            } elseif ($punch['punch_type'] === 'out' && $in_time !== null) {
                $out_time = strtotime($punch['punch_datetime']);
                $hours = ($out_time - $in_time) / 3600; // Convertir en heures
                $total_hours += $hours;
                $in_time = null;
            }
        }

        return round($total_hours, 2);
    }

    /**
     * Calculer les heures travaillées pour une période
     */
    public function calculateHoursRange($employee_id, $start_date, $end_date) {
        $total = 0;
        $current_date = $start_date;
        
        while (strtotime($current_date) <= strtotime($end_date)) {
            $total += $this->calculateHours($employee_id, $current_date);
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        return round($total, 2);
    }

    /**
     * Ajouter manuellement un pointage (admin)
     */
    public function addManual($employee_id, $type, $datetime) {
        return $this->record($employee_id, $type, $datetime);
    }

    /**
     * Supprimer un pointage
     */
    public function delete($id) {
        $this->db->query("DELETE FROM punches WHERE id = ?", [$id]);
    }

    /**
     * Récupérer tous les pointages pour une date (admin)
     */
    public function getAllByDate($date) {
        return $this->db->fetchAll(
            "SELECT p.*, e.first_name, e.last_name 
             FROM punches p 
             JOIN employees e ON p.employee_id = e.id 
             WHERE DATE(p.punch_datetime) = ? 
             ORDER BY p.punch_datetime DESC",
            [$date]
        );
    }
}

