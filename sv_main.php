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
            SUM(CASE WHEN le.entry_status = 'Pending' THEN 1 ELSE 0 END) as pending_logs
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
    <title>Interns</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/supervisor_tables.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="top-section">
            <h1>Interns</h1>
            <button onclick="window.location.href='sv_add_student.php'" class="btn-add">
                <i class="fi fi-rr-user-add"></i> Add Interns
            </button>
        </div>
        
        <!-- Student table -->
        <table class="student-table">
            <thead>
                <tr>
                    <th>Matric Number</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Total Logs</th>
                    <th>Pending</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" class="empty-message">No interns assigned yet</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['matric_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                            <td>
                                <span class="status-<?php echo strtolower($student['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($student['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $student['total_logs']; ?></td>
                            <td class="pending-logs"><?php echo $student['pending_logs']; ?></td>
                            <td>
                                <a href="sv_view_logbook.php?student_id=<?php echo $student['user_id']; ?>" class="view-logbook-link">
                                    <i class="fi fi-rr-search-alt"></i> View Logbook
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>