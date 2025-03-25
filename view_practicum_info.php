<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

try {
    if ($_SESSION['role'] == ROLE_STUDENT) {
        // Get student info
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                u.email
            FROM student s
            INNER JOIN user u ON s.student_id = u.user_id
            WHERE s.student_id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get assigned supervisor info
        $stmt = $conn->prepare("
            SELECT 
                cs.*,
                u.email as user_email
            FROM supervisor_student ss
            INNER JOIN supervisor cs ON ss.supervisor_id = cs.supervisor_id
            INNER JOIN user u ON cs.supervisor_id = u.user_id
            WHERE ss.student_id = :student_id
        ");
        $stmt->execute([':student_id' => $_SESSION['user_id']]);
        $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);

    } else {
        // Get supervisor info
        $stmt = $conn->prepare("
            SELECT 
                cs.*,
                u.email as user_email
            FROM supervisor cs
            INNER JOIN user u ON cs.supervisor_id = u.user_id
            WHERE cs.supervisor_id = :user_id
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practicum Information</title>
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .info-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .info-header {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 10px;
        }

        .info-item label {
            font-weight: 500;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 5px;
        }

        .info-item p {
            margin: 0;
            color: var(--text-primary);
        }

        .address-item {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <?php if ($_SESSION['role'] == ROLE_STUDENT): ?>
            <!-- Student Information Section -->
            <div class="info-section">
                <div class="info-header">
                    <h2>Student Information</h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($student['full_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Matric Number</label>
                        <p><?php echo htmlspecialchars($student['matric_no']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Contact Number</label>
                        <p><?php echo htmlspecialchars($student['phone_number']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Institution</label>
                        <p><?php echo htmlspecialchars($student['institution']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>School/Faculty</label>
                        <p><?php echo htmlspecialchars($student['school']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Programme</label>
                        <p><?php echo htmlspecialchars($student['programme']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Practicum Duration</label>
                        <p><?php 
                            echo date('d M Y', strtotime($student['practicum_start_date'])) . ' - ' . 
                                 date('d M Y', strtotime($student['practicum_end_date'])) . 
                                 ' (' . $student['practicum_duration'] . ' months)'; 
                        ?></p>
                    </div>
                    <div class="info-item address-item">
                        <label>Home Address</label>
                        <p><?php echo nl2br(htmlspecialchars($student['home_address'])); ?></p>
                    </div>
                </div>
            </div>

            <?php if ($supervisor): ?>
            <!-- Supervisor Information Section -->
            <div class="info-section">
                <div class="info-header">
                    <h2>Company Supervisor Information</h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Company Name</label>
                        <p><?php echo htmlspecialchars($supervisor['company_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Supervisor Name</label>
                        <p><?php echo htmlspecialchars($supervisor['supervisor_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Designation</label>
                        <p><?php echo htmlspecialchars($supervisor['designation']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Contact Number</label>
                        <p><?php echo htmlspecialchars($supervisor['contact_number']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($supervisor['supervisor_email']); ?></p>
                    </div>
                    <div class="info-item address-item">
                        <label>Company Address</label>
                        <p><?php echo nl2br(htmlspecialchars($supervisor['company_address'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Supervisor Information Section -->
            <div class="info-section">
                <div class="info-header">
                    <h2>Company Information</h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Company Name</label>
                        <p><?php echo htmlspecialchars($supervisor['company_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Supervisor Name</label>
                        <p><?php echo htmlspecialchars($supervisor['supervisor_name']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Designation</label>
                        <p><?php echo htmlspecialchars($supervisor['designation']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Contact Number</label>
                        <p><?php echo htmlspecialchars($supervisor['contact_number']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($supervisor['user_email']); ?></p>
                    </div>
                    <div class="info-item address-item">
                        <label>Company Address</label>
                        <p><?php echo nl2br(htmlspecialchars($supervisor['company_address'])); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>