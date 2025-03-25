<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if ($_SESSION['role'] != ROLE_SUPERVISOR) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_id'])) {
    try {
        $conn->beginTransaction();

        // Insert feedback
        $stmt = $conn->prepare("
            INSERT INTO feedback 
            (entry_id, supervisor_id, signature_date, signature_time, remarks)
            VALUES 
            (:entry_id, :supervisor_id, CURDATE(), CURTIME(), :remarks)
        ");
        
        $stmt->execute([
            ':entry_id' => $_POST['entry_id'],
            ':supervisor_id' => $_SESSION['user_id'],
            ':remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null
        ]);

        // Update log entry status
        $stmt = $conn->prepare("
            UPDATE log_entry 
            SET entry_status = 'Signed'
            WHERE entry_id = :entry_id
        ");
        
        $stmt->execute([':entry_id' => $_POST['entry_id']]);
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>