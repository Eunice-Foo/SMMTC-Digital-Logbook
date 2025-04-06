<?php
function renderLogbookProfile($conn, $user_id) {
    // Get student and supervisor info
    $stmt = $conn->prepare("
        SELECT 
            s.full_name,
            s.matric_no,
            s.institution,
            s.school,
            s.programme,
            s.phone_number,
            s.home_address,
            s.practicum_start_date,
            s.practicum_end_date,
            s.practicum_duration,
            sv.supervisor_name,
            sv.company_name,
            sv.designation,
            sv.contact_number,
            sv.company_address,
            u.email as student_email,
            su.email as supervisor_email
        FROM student s
        LEFT JOIN supervisor_student ss ON s.student_id = ss.student_id
        LEFT JOIN supervisor sv ON ss.supervisor_id = sv.supervisor_id
        LEFT JOIN user u ON s.student_id = u.user_id
        LEFT JOIN user su ON sv.supervisor_id = su.user_id
        WHERE s.student_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) return;
    ?>

    <div class="logbook-profile">
        
        <!-- Practicum Information -->
        <div class="info-section">
            <h2>Practicum Information</h2>
            <table class="info-table">
                <tr>
                    <th>Start Date</th>
                    <td><?php echo date('d M Y', strtotime($info['practicum_start_date'])); ?></td>
                </tr>
                <tr>
                    <th>End Date</th>
                    <td><?php echo date('d M Y', strtotime($info['practicum_end_date'])); ?></td>
                </tr>
                <tr>
                    <th>Duration</th>
                    <td><?php echo $info['practicum_duration'] . ' months'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Student Information -->
        <div class="info-section">
            <h2>Student Details</h2>
            <table class="info-table">
                <tr>
                    <th>Full Name</th>
                    <td><?php echo htmlspecialchars($info['full_name']); ?></td>
                </tr>
                <tr>
                    <th>Matric Number</th>
                    <td><?php echo htmlspecialchars($info['matric_no']); ?></td>
                </tr>
                <tr>
                    <th>Programme</th>
                    <td><?php echo htmlspecialchars($info['programme']); ?></td>
                </tr>
                <tr>
                    <th>School/Faculty</th>
                    <td><?php echo htmlspecialchars($info['school']); ?></td>
                </tr>
                <tr>
                    <th>Institution</th>
                    <td><?php echo htmlspecialchars($info['institution']); ?></td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?php echo htmlspecialchars($info['phone_number']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($info['student_email']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Company Information -->
        <div class="info-section">
            <h2>Company Details</h2>
            <table class="info-table">
                <tr>
                    <th>Company Name</th>
                    <td><?php echo htmlspecialchars($info['company_name']); ?></td>
                </tr>
                <tr>
                    <th>Company Address</th>
                    <td><?php echo nl2br(htmlspecialchars($info['company_address'])); ?></td>
                </tr>
            </table>
        </div>

        <!-- Supervisor Information -->
        <div class="info-section">
            <h2>Company Supervisor Details</h2>
            <table class="info-table">
                <tr>
                    <th>Supervisor Name</th>
                    <td><?php echo htmlspecialchars($info['supervisor_name']); ?></td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td><?php echo htmlspecialchars($info['designation']); ?></td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td><?php echo htmlspecialchars($info['contact_number']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($info['supervisor_email']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <style>
    .logbook-profile {
        font-family: "Times New Roman", Times, serif;
        font-size: 12pt;
        line-height: 1.5;
        padding: 0;
    }

    .logbook-profile h1 {
        text-align: center;
        margin-bottom: 40px;
        font-size: 14pt;
        font-weight: bold;
    }

    .info-section {
        margin-bottom: 30px;
    }

    .info-section h2 {
        font-family: "Times New Roman", Times, serif;
        font-size: 14pt !important;
        font-weight: bold;
        margin-bottom: 15px;
        border-bottom: 1px solid #000;
        padding-bottom: 5px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        box-shadow: none;
    }

    .info-table th,
    .info-table td {
        font-family: "Times New Roman", Times, serif;
        padding: 8px;
        text-align: left;
        border: 1px solid #000;
        font-size: 12pt;
        background: none;
        box-shadow: none;
    }

    .info-table th {
        width: 30%;
        font-weight: bold;
    }
    </style>
    <?php
}
?>