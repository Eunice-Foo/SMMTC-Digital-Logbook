<?php
function getSupervisorInfo($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT 
            sv.supervisor_name,
            sv.signature_image
        FROM student s
        LEFT JOIN supervisor_student ss ON s.student_id = ss.student_id
        LEFT JOIN supervisor sv ON ss.supervisor_id = sv.supervisor_id
        WHERE s.student_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function renderSupervisorSignature($conn, $user_id) {
    $supervisor_info = getSupervisorInfo($conn, $user_id);
    
    if (!$supervisor_info) {
        return; // No supervisor assigned
    }
    ?>
    <div class="signature-section">
        <div class="signature-spacer"></div>
        <div class="supervisor-signature">
            <p>Supervisor's Signature</p>
            <?php if ($supervisor_info['signature_image']): ?>
                <img src="uploads/signatures/<?php echo htmlspecialchars($supervisor_info['signature_image']); ?>" 
                     alt="Supervisor Signature" 
                     class="signature-image">
            <?php else: ?>
                <div class="signature-placeholder"></div>
            <?php endif; ?>
            <p class="supervisor-name"><?php echo htmlspecialchars($supervisor_info['supervisor_name']); ?></p>
        </div>
    </div>
    <?php
}
?>

<style>
/* Supervisor signature styles */
.signature-section {
    display: none;
}

.supervisor-signature {
    text-align: center;
    width: auto;
    border: 1px solid red; /* Debug: Show signature boundaries */
}

.supervisor-signature p {
    font-size: 12px;
}

.supervisor-signature .supervisor-name {
    font-weight: 500;
}

.signature-image {
    width: 150px;
    height: 60px;
    object-fit: contain;
}

.signature-placeholder {
    width: 150px;
    height: 60px;
    border-bottom: 3px solid #000;
}

/* Print styles - only signature related */
@media print {
    /* Fixed positioning for signature */
    .signature-section {
        display: block;
        position: fixed;
        bottom: 1in;
        right: 1in;
        z-index: 9999;
        padding: 0;
        margin: 0;
        border: 2px solid blue;
        background: rgba(0, 0, 255, 0.1);
    }
}
</style>