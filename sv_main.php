<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Check if user is supervisor
if ($_SESSION['role'] != ROLE_SUPERVISOR) {
    header('Location: main_menu.php');
    exit();
}

try {
    // Update the SQL query
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.email,
            s.full_name,
            s.matric_no,
            s.phone_number,
            s.institution,
            ss.status,
            COUNT(DISTINCT le.entry_id) as total_logs,
            SUM(CASE WHEN le.entry_status = 'pending_review' THEN 1 ELSE 0 END) as pending_logs
        FROM supervisor_student ss
        INNER JOIN user u ON ss.student_id = u.user_id
        INNER JOIN student s ON u.user_id = s.student_id
        LEFT JOIN log_entry le ON u.user_id = le.user_id
        WHERE ss.supervisor_id = :supervisor_id
        GROUP BY u.user_id
        ORDER BY s.full_name
    ");
    
    $stmt->bindParam(':supervisor_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <h2>My Students</h2>
        <button onclick="window.location.href='sv_add_student.php'" class="btn btn-add">Add New Student</button>
        
        <!-- Update the HTML table -->
        <table class="student-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Institution</th>
                    <th>Status</th>
                    <th>Total Logs</th>
                    <th>Pending</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['institution']); ?></td>
                        <td class="status-<?php echo strtolower($student['status']); ?>">
                            <?php echo htmlspecialchars($student['status']); ?>
                        </td>
                        <td><?php echo $student['total_logs']; ?></td>
                        <td class="pending-logs"><?php echo $student['pending_logs']; ?></td>
                        <td>
                            <a href="sv_view_logbook.php?student_id=<?php echo $student['user_id']; ?>" 
                               class="btn">View Logbook</a>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function updateStatus(studentId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        if (confirm(`Are you sure you want to ${currentStatus === 'active' ? 'deactivate' : 'activate'} this student?`)) {
            fetch('sv_update_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `student_id=${studentId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
    }
    </script>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>