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
    <title>Add New Student</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="container">
        <h2>Add New Student</h2>
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
                <?php foreach ($available_students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['institution']); ?></td>
                        <td>
                            <button onclick="addStudent(<?php echo $student['user_id']; ?>)" 
                                    class="btn btn-add">Add Student</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
                    window.location.href = 'sv_main.php'; // Changed from sv_students.php
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
    </script>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>