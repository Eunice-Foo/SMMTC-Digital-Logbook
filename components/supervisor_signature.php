<?php
function getSupervisorInfo($conn, $user_id) {
    // First try to get the supervisor assigned to this student
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
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no result (student with no supervisor) and user is a supervisor, get their own info
    if (!$result && $_SESSION['role'] == ROLE_SUPERVISOR) {
        $stmt = $conn->prepare("
            SELECT 
                supervisor_name,
                signature_image 
            FROM supervisor 
            WHERE supervisor_id = :user_id
        ");
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $result;
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
            <p><strong>Supervisor's Signature</strong><p>
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
}

.supervisor-signature p {
    font-size: 12px;
}

.supervisor-signature .supervisor-name {
    font-weight: 500;
}

.signature-image {
    width: auto;
    height: auto;
    max-height: 80px;
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
        background: rgba(0, 0, 255, 0.1);
    }
}
</style>

<script>
// Add this to js/edit_profile.js to preview the signature before submission
function initSignaturePreview() {
    const signatureInput = document.getElementById('signature_image');
    const previewContainer = document.querySelector('.current-signature');
    
    if (signatureInput && previewContainer) {
        signatureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const placeholder = previewContainer.querySelector('.signature-placeholder');
                    if (placeholder) {
                        previewContainer.removeChild(placeholder);
                    }
                    
                    const existingImg = previewContainer.querySelector('img');
                    if (existingImg) {
                        previewContainer.removeChild(existingImg);
                    }
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Signature preview';
                    previewContainer.appendChild(img);
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

// Modify the existing DOMContentLoaded event to include signature preview
document.addEventListener('DOMContentLoaded', function() {
    // Existing code...
    
    // Initialize signature picture preview
    initSignaturePreview();
});
</script>