<?php
function renderLogActions($entryId, $entryStatus, $userRole) {
    // Set display style based on status - all styling in this one place
    $displayStyle = ($entryStatus === 'Signed') ? 'display:none;' : 'display:flex;';
    ?>
    <div class="log-actions" data-entry-id="<?php echo $entryId; ?>" data-entry-status="<?php echo $entryStatus; ?>" style="<?php echo $displayStyle; ?>">
        <?php if ($userRole == ROLE_SUPERVISOR): ?>
            <?php if ($entryStatus !== 'Signed'): ?>
                <button class="btn btn-sign" onclick="directSign(<?php echo $entryId; ?>)">
                    <i class="fi fi-rr-attribution-pen"></i> Sign
                </button>
                <button class="btn btn-remark" onclick="signLog(<?php echo $entryId; ?>)">
                    <i class="fi fi-rr-comment"></i> Remark
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

.btn-delete:hover {
    background-color:rgb(188, 14, 31); /* Added darker red hover state */
}

.btn-sign {
    background-color: var(--primary-color); /* Changed to primary color */
    color: white;
        padding: 12px 18px;
}

.btn-sign:hover {
    background-color:rgb(98, 53, 131); /* Darker shade of primary color */
}

.btn-remark {
    background-color: #2196F3; /* Changed to same blue as Edit button */
    color: white;
    padding: 12px 18px;
}

.btn-remark:hover {
    background-color: #1976D2; /* Same hover color as Edit button */
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