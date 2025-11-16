<?php
/**
 * GRAFIK - Classe Schedule
 * Gestion des plannings
 */

class Schedule {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer le planning pour un mois donné
     */
    public function getForMonth($year, $month) {
        $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $this->db->fetchAll(
            "SELECT s.*, e.first_name, e.last_name 
             FROM schedules s
             JOIN employees e ON s.employee_id = e.id
             WHERE s.schedule_date BETWEEN ? AND ?
             ORDER BY s.schedule_date, e.last_name, e.first_name",
            [$start_date, $end_date]
        );
    }

    /**
     * Récupérer le planning d'un employé pour une période
     */
    public function getForEmployee($employee_id, $start_date, $end_date) {
        return $this->db->fetchAll(
            "SELECT * FROM schedules 
             WHERE employee_id = ? AND schedule_date BETWEEN ? AND ?
             ORDER BY schedule_date",
            [$employee_id, $start_date, $end_date]
        );
    }

    /**
     * Récupérer le planning d'un employé pour un jour spécifique
     */
    public function getForEmployeeDate($employee_id, $date) {
        return $this->db->fetchOne(
            "SELECT * FROM schedules 
             WHERE employee_id = ? AND schedule_date = ?",
            [$employee_id, $date]
        );
    }

    /**
     * Ajouter ou mettre à jour un planning
     */
    public function saveSchedule($employee_id, $date, $start_time, $end_time, $notes = null) {
        // Vérifier si un planning existe déjà
        $existing = $this->getForEmployeeDate($employee_id, $date);
        
        if ($existing) {
            // Mettre à jour
            $this->db->query(
                "UPDATE schedules 
                 SET start_time = ?, end_time = ?, notes = ?
                 WHERE employee_id = ? AND schedule_date = ?",
                [$start_time, $end_time, $notes, $employee_id, $date]
            );
            return $existing['id'];
        } else {
            // Insérer
            $this->db->query(
                "INSERT INTO schedules (employee_id, schedule_date, start_time, end_time, notes) 
                 VALUES (?, ?, ?, ?, ?)",
                [$employee_id, $date, $start_time, $end_time, $notes]
            );
            return $this->db->lastInsertId();
        }
    }

    /**
     * Supprimer un planning
     */
    public function delete($id) {
        $this->db->query("DELETE FROM schedules WHERE id = ?", [$id]);
    }

    /**
     * Supprimer tous les plannings d'un employé pour une date
     */
    public function deleteForEmployeeDate($employee_id, $date) {
        $this->db->query(
            "DELETE FROM schedules WHERE employee_id = ? AND schedule_date = ?",
            [$employee_id, $date]
        );
    }

    /**
     * Dupliquer le planning d'une semaine vers une autre
     */
    public function duplicateWeek($source_start_date, $target_start_date) {
        $schedules = $this->db->fetchAll(
            "SELECT * FROM schedules 
             WHERE schedule_date BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)",
            [$source_start_date, $source_start_date]
        );
        
        foreach ($schedules as $schedule) {
            $source_date = new DateTime($schedule['schedule_date']);
            $source_week_start = new DateTime($source_start_date);
            $day_diff = $source_date->diff($source_week_start)->days;
            
            $target_date = new DateTime($target_start_date);
            $target_date->modify("+{$day_diff} days");
            
            $this->saveSchedule(
                $schedule['employee_id'],
                $target_date->format('Y-m-d'),
                $schedule['start_time'],
                $schedule['end_time'],
                $schedule['notes']
            );
        }
    }

    /**
     * Obtenir les statistiques du planning pour un mois
     */
    public function getMonthStats($year, $month) {
        $start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        return $this->db->fetchAll(
            "SELECT 
                e.id, 
                e.first_name, 
                e.last_name,
                COUNT(s.id) as days_scheduled,
                SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)) / 3600) as total_hours
             FROM employees e
             LEFT JOIN schedules s ON e.id = s.employee_id 
                AND s.schedule_date BETWEEN ? AND ?
             WHERE e.is_active = 1
             GROUP BY e.id, e.first_name, e.last_name
             ORDER BY e.last_name, e.first_name",
            [$start_date, $end_date]
        );
    }
}

