<?php
/**
 * GRAFIK - Page admin - Messages des employés
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../classes/Message.php';

include 'header.php';

$messageModel = new Message();

// Marquer un message comme lu
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $messageModel->markAsRead($_GET['mark_read']);
    header('Location: messages.php');
    exit;
}

// Récupérer tous les messages
$messages = $messageModel->getAll();
$unread_count = $messageModel->countUnread();
?>

<div class="container">
    <div class="page-header">
        <h1>Messages des employés</h1>
        <?php if ($unread_count > 0): ?>
            <span style="background: #e74c3c; color: white; padding: 5px 10px; border-radius: 5px; font-size: 14px; margin-left: 10px;">
                <?= $unread_count ?> non lu<?= $unread_count > 1 ? 's' : '' ?>
            </span>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <?php if (count($messages) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Date/Heure</th>
                    <th>Message</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr style="<?= $msg['is_read'] == 0 ? 'background-color: #fff3cd;' : '' ?>">
                    <td>
                        <strong><?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?></strong>
                    </td>
                    <td>
                        <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                    </td>
                    <td style="max-width: 400px;">
                        <div style="white-space: pre-wrap; word-wrap: break-word;">
                            <?= htmlspecialchars($msg['message']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($msg['is_read'] == 0): ?>
                            <span style="color: #e74c3c; font-weight: bold;">● Non lu</span>
                        <?php else: ?>
                            <span style="color: #27ae60;">✓ Lu</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($msg['is_read'] == 0): ?>
                            <a href="?mark_read=<?= $msg['id'] ?>" class="btn btn-secondary btn-sm">
                                Marquer comme lu
                            </a>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color: #999; text-align: center; padding: 40px;">
            Aucun message reçu pour le moment.
        </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

