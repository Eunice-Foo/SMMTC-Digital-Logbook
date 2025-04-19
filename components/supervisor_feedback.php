<?php
// filepath: c:\xampp\htdocs\log\components\supervisor_feedback.php
/**
 * Renders a supervisor feedback/remarks section
 * 
 * @param string $remarks The supervisor's remarks text
 * @param string|null $signatureDate Optional date when signed (format YYYY-MM-DD)
 */
function renderSupervisorFeedback($remarks, $signatureDate = null) {
    if (empty($remarks) && empty($signatureDate)) {
        return; // Don't render anything if no remarks or signature date
    }
    ?>
    <div class="supervisor-feedback">
        <h4 class="feedback-heading">Supervisor's Remarks</h4>
        <p><?php echo !empty($remarks) ? nl2br(htmlspecialchars($remarks)) : 'No remarks provided'; ?></p>
    </div>
    <?php
}
?>

<style>
/* Supervisor feedback styles */
.supervisor-feedback {
    background: #f8f9fa;
    padding: 24px;
    margin-top: 15px;
    border-left: 3px solid var(--primary-color);
    border-radius: 4px;
}

.supervisor-feedback h4 {
    margin: 0;
    font-weight: 500;
    color: var(--text-primary);
}

.supervisor-feedback p {
    margin: 0;
}

/* Print styles for export preview */
@media print {
    .supervisor-feedback {
        background: none;
        padding: 10px 0;
        border-left: 2px solid #000;
        padding-left: 10px;
    }
}
</style>