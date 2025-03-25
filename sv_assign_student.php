<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Check if user is supervisor
if ($_SESSION['role'] != ROLE_SUPERVISOR) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    try {
        // Begin transaction
        $conn->beginTransaction();

        // Check if student exists and is not already assigned
        $stmt = $conn->prepare("
            SELECT u.user_id 
            FROM user u
            LEFT JOIN supervisor_student ss ON u.user_id = ss.student_id 
                AND ss.supervisor_id = :supervisor_id
            WHERE u.user_id = :student_id 
            AND u.role = :role
            AND ss.supervisor_id IS NULL
        ");
        
        $stmt->execute([
            ':supervisor_id' => $_SESSION['user_id'],
            ':student_id' => $_POST['student_id'],
            ':role' => ROLE_STUDENT
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Student is not available for assignment');
        }

        // Insert into supervisor_student table
        $stmt = $conn->prepare("
            INSERT INTO supervisor_student 
            (supervisor_id, student_id, status) 
            VALUES 
            (:supervisor_id, :student_id, 'active')
        ");

        $stmt->execute([
            ':supervisor_id' => $_SESSION['user_id'],
            ':student_id' => $_POST['student_id']
        ]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student assigned successfully']);

    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>