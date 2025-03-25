<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

// Check if user is supervisor and student_id is provided
if ($_SESSION['role'] != ROLE_SUPERVISOR || !isset($_GET['student_id'])) {
    header('Location: sv_main.php');
    exit();
}

try {
    // Get student information
    $stmt = $conn->prepare("
        SELECT 
            s.full_name,
            s.matric_no,
            s.phone_number,
            s.institution,
            u.email
        FROM student s
        INNER JOIN user u ON s.student_id = u.user_id
        WHERE s.student_id = :student_id
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id']);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get pending log entries
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_date,
            le.entry_time,
            le.entry_status,
            GROUP_CONCAT(DISTINCT m.file_name) as media_files,
            GROUP_CONCAT(DISTINCT m.file_type) as media_types
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        WHERE le.user_id = :student_id 
        AND le.entry_status = 'Pending'
        GROUP BY le.entry_id, le.entry_title, le.entry_description, le.entry_date, le.entry_time
        ORDER BY le.entry_date DESC, le.entry_time DESC
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    $pending_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending_count = count($pending_logs);

    // Get reviewed log entries
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_title,
            le.entry_description,
            le.entry_date,
            le.entry_time,
            le.entry_status,
            GROUP_CONCAT(DISTINCT m.file_name) as media_files,
            GROUP_CONCAT(DISTINCT m.file_type) as media_types
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        WHERE le.user_id = :student_id 
        AND le.entry_status = 'Signed'
        GROUP BY le.entry_id, le.entry_title, le.entry_description, le.entry_date, le.entry_time
        ORDER BY le.entry_date DESC, le.entry_time DESC
    ");
    
    $stmt->bindParam(':student_id', $_GET['student_id'], PDO::PARAM_INT);
    $stmt->execute();
    $reviewed_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reviewed_count = count($reviewed_logs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Logbook</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/log_form.css">
    <style>
        .student-info {
            margin-bottom: 30px;
        }
        .section-header {
            background-color: #f5f5f5;
            padding: 10px;
            margin: 20px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .log-entry {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
        }
        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .media-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
        }
        .no-entries {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            background: var(--bg-secondary);
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include 'components/side_menu.php'; ?>
    
    <div class="main-content">
        <!-- Student Information -->
        <div class="student-info">
            <h2>Student Information</h2>
            <table class="student-table">
                <tr>
                    <th>Full Name</th>
                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                    <th>Matric No</th>
                    <td><?php echo htmlspecialchars($student['matric_no']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <th>Phone</th>
                    <td><?php echo htmlspecialchars($student['phone_number']); ?></td>
                </tr>
                <tr>
                    <th>Institution</th>
                    <td colspan="3"><?php echo htmlspecialchars($student['institution']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Pending Logs Section -->
        <div class="section-header">
            <h3>Pending Review</h3>
            <span>Count: <?php echo $pending_count; ?></span>
        </div>
        
        <?php if ($pending_count > 0): ?>
            <?php foreach ($pending_logs as $log): ?>
                <div class="log-entry">
                    <div class="log-header">
                        <h4><?php echo htmlspecialchars($log['entry_title']); ?></h4>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($log['entry_date'] . ' ' . $log['entry_time']); ?></p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($log['entry_description'])); ?></p>
                    
                    <?php if (!empty($log['media_files'])): ?>
                        <div class="media-gallery">
                            <?php foreach (explode(',', $log['media_files']) as $media): ?>
                                <div class="media-item">
                                    <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                                        <video width="100%" height="150" controls>
                                            <source src="uploads/<?php echo htmlspecialchars($media); ?>" type="video/mp4">
                                        </video>
                                    <?php else: ?>
                                        <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Log Entry Media">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn" onclick="signLog(<?php echo $log['entry_id']; ?>)">Sign</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-entries">No pending entries</p>
        <?php endif; ?>

        <!-- Reviewed Logs Section -->
        <div class="section-header">
            <h3>Reviewed</h3>
            <span>Count: <?php echo $reviewed_count; ?></span>
        </div>
        
        <?php foreach ($reviewed_logs as $log): ?>
            <div class="log-entry">
                <div class="log-header">
                    <h4><?php echo htmlspecialchars($log['entry_title']); ?></h4>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($log['entry_date'] . ' ' . $log['entry_time']); ?></p>
                </div>
                <p><?php echo nl2br(htmlspecialchars($log['entry_description'])); ?></p>
                
                <?php if (!empty($log['media_files'])): ?>
                    <div class="media-gallery">
                        <?php foreach (explode(',', $log['media_files']) as $media): ?>
                            <div class="media-item">
                                <?php if (strpos($media, '.mp4') !== false || strpos($media, '.mov') !== false): ?>
                                    <video width="100%" height="150" controls>
                                        <source src="uploads/<?php echo htmlspecialchars($media); ?>" type="video/mp4">
                                    </video>
                                <?php else: ?>
                                    <img src="uploads/<?php echo htmlspecialchars($media); ?>" alt="Log Entry Media">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function signLog(entryId) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Sign Log Entry</h3>
                <div class="form-group">
                    <label for="remarks">Remarks (Optional):</label>
                    <textarea id="remarks" rows="4"></textarea>
                </div>
                <div class="modal-buttons">
                    <button onclick="submitSignature(${entryId})" class="btn">Sign</button>
                    <button onclick="closeModal()" class="btn">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function closeModal() {
        const modal = document.querySelector('.modal');
        if (modal) {
            modal.remove();
        }
    }

    function submitSignature(entryId) {
        const remarks = document.getElementById('remarks').value;
        
        fetch('sv_sign_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `entry_id=${entryId}&remarks=${encodeURIComponent(remarks)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error signing log: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error signing log');
        });
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