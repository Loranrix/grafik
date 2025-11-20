<?php
/**
 * GRAFIK - Classe Message
 * Gestion des messages envoyés par les employés
 */

require_once __DIR__ . '/Firebase.php';
require_once __DIR__ . '/Employee.php';

class Message {
    private $firebase;
    private $employee;

    public function __construct() {
        $this->firebase = Firebase::getInstance();
        $this->employee = new Employee();
    }

    /**
     * Créer un nouveau message
     */
    public function create($employee_id, $message) {
        $message_id = $this->firebase->generateMessageId();
        
        $emp = $this->employee->getById($employee_id);
        
        $data = [
            'employee_id' => $employee_id,
            'message' => $message,
            'is_read' => false,
            'created_at' => date('Y-m-d\TH:i:s')
        ];
        
        if ($this->firebase->saveMessage($message_id, $data)) {
            return $message_id;
        }
        
        return false;
    }

    /**
     * Récupérer tous les messages avec les informations de l'employé
     */
    public function getAll($limit = null, $offset = 0) {
        $messages = $this->firebase->getAllMessages($limit, $offset);
        
        // Enrichir avec les noms des employés
        foreach ($messages as &$message) {
            if (isset($message['employee_id'])) {
                $emp = $this->employee->getById($message['employee_id']);
                if ($emp) {
                    $message['first_name'] = $emp['first_name'] ?? '';
                    $message['last_name'] = $emp['last_name'] ?? '';
                }
            }
        }
        
        return $messages;
    }

    /**
     * Récupérer les messages non lus
     */
    public function getUnread() {
        $messages = $this->firebase->getUnreadMessages();
        
        // Enrichir avec les noms des employés
        foreach ($messages as &$message) {
            if (isset($message['employee_id'])) {
                $emp = $this->employee->getById($message['employee_id']);
                if ($emp) {
                    $message['first_name'] = $emp['first_name'] ?? '';
                    $message['last_name'] = $emp['last_name'] ?? '';
                }
            }
        }
        
        return $messages;
    }

    /**
     * Compter les messages non lus
     */
    public function countUnread() {
        return $this->firebase->countUnreadMessages();
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead($message_id) {
        return $this->firebase->markMessageAsRead($message_id);
    }

    /**
     * Récupérer un message par son ID
     */
    public function getById($message_id) {
        $message = $this->firebase->getMessage($message_id);
        
        if ($message && isset($message['employee_id'])) {
            $emp = $this->employee->getById($message['employee_id']);
            if ($emp) {
                $message['first_name'] = $emp['first_name'] ?? '';
                $message['last_name'] = $emp['last_name'] ?? '';
            }
        }
        
        return $message;
    }
}
