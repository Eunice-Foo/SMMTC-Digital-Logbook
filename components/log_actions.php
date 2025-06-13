<?php
function renderLogActions($entryId, $entryStatus, $userRole) {
    // Set display style based on status - all styling in this one place
    $displayStyle = ($entryStatus === 'Signed') ? 'display:none;' : 'display:flex;';
    ?>
    <div class="log-actions" data-entry-id="<?php echo $entryId; ?>" data-entry-status="<?php echo $entryStatus; ?>" style="<?php echo $displayStyle; ?>">
        <?php if ($userRole == ROLE_SUPERVISOR): ?>
            <?php if ($entryStatus !== 'Signed'): ?>
                <button class="btn btn-sign" onclick="directSign(<?php echo $entryId; ?>)">
                    Sign
                </button>
                <button class="btn btn-remark" onclick="signLog(<?php echo $entryId; ?>)">
                    Remark
                </button>
            <?php endif; ?>
        <?php else: // ROLE_STUDENT ?>
            <?php if ($entryStatus !== 'Signed'): ?>
                <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=<?php echo $entryId; ?>'">
                    <i class="fi fi-rr-pen-field"></i> Edit
                </button>
                <button class="btn btn-delete" onclick="confirmDelete(<?php echo $entryId; ?>)">
                    <i class="fi fi-rr-trash"></i> Delete
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
?>

<style>
/* Actions section */
.log-actions {
    display: flex;
    flex-direction: column;
    align-self: center;
    gap: 10px;
    width: fit-content;
    position: static;
    margin-left: 20px; /* Added this line */
}

/* Hide the actions div for signed entries */
.signed-entry-actions {
    display: none;
}

/* Show signed actions in export mode */
body.export-mode .signed-entry-actions {
    display: flex;
}

.btn-edit {
    background-color: #2196F3; /* Changed from white to export button blue */
    color: white; /* Changed from primary color to white */
    padding: 12px 18px;
}

.btn-edit:hover {
    background-color: #1976D2; /* Changed to darker blue on hover */
}

.btn-delete {
    background-color: #dc3545;
    color: white;
    padding: 12px 18px;
}

.btn-sign {
    background-color: #28a745;
    color: white;
}

.btn-remark {
    background-color: #17a2b8;
    color: white;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .log-actions {
        flex-direction: row;
        justify-content: flex-end;
    }
    
    .log-actions .btn {
        width: auto;
    }
}

@media screen and (max-width: 576px) {
    .log-actions {
        flex-direction: row;
        justify-content: flex-end;
        margin-top: 15px;
        width: 100%;
    }
}
</style>