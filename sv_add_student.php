<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if ($_SESSION['role'] != ROLE_SUPERVISOR) {
    header('Location: main_menu.php');
    exit();
}

try {
    // Get all students not yet assigned to this supervisor
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.email,
            s.full_name,
            s.matric_no,
            s.phone_number,
            s.institution
        FROM user u
        INNER JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor_student ss ON u.user_id = ss.student_id 
            AND ss.supervisor_id = :supervisor_id
        WHERE u.role = :role
        AND ss.supervisor_id IS NULL
        ORDER BY s.full_name
    ");
    
    $stmt->execute([
        ':supervisor_id' => $_SESSION['user_id'],
        ':role' => ROLE_STUDENT
    ]);
    $available_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Interns</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/supervisor_tables.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css'>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="top-section">
            <h2>Add Interns</h2>
            <a href="javascript:void(0)" class="btn cancel-btn">Cancel</a>
        </div>
        
        <table class="student-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Institution</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($available_students)): ?>
                    <tr>
                        <td colspan="5" class="empty-message">No available students to add</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($available_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['institution']); ?></td>
                            <td>
                                <button onclick="addStudent(<?php echo $student['user_id']; ?>)" class="btn btn-add">
                                    <i class="fi fi-rr-user-add"></i> Add
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function addStudent(studentId) {
        if (confirm('Are you sure you want to add this student?')) {
            fetch('sv_assign_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `student_id=${studentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'sv_main.php';
                } else {
                    alert('Error adding student: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding student');
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.querySelector('.cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(event) {
                event.preventDefault();
                confirmCancel('sv_main.php');
            });
        }
    });
    </script>
    <script src="js/cancel_confirmation.js" defer></script>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>