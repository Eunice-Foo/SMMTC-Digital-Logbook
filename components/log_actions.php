<?php
function renderLogActions($entryId, $entryStatus, $userRole) {
    ?>
    <div class="log-actions" data-entry-id="<?php echo $entryId; ?>">
        <?php if ($userRole == ROLE_SUPERVISOR): ?>
            <?php if ($entryStatus !== 'Signed'): ?>
                <button class="btn btn-view" onclick="signLog(<?php echo $entryId; ?>)">
                    Sign
                </button>
            <?php endif; ?>
        <?php else: // ROLE_STUDENT ?>
            <button class="btn btn-view" onclick="window.location.href='view_log.php?id=<?php echo $entryId; ?>'">
                View
            </button>
            <?php if ($entryStatus !== 'Signed'): ?>
                <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=<?php echo $entryId; ?>'">
                    Edit
                </button>
                <button class="btn btn-delete" onclick="confirmDelete(<?php echo $entryId; ?>)">
                    Delete
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
    width: 100px;
    position: static;
}

.btn-view {
    background-color: #007bff;
    color: white;
}

.btn-edit {
    background-color: #6c757d;
    color: white;
}

.btn-delete {
    background-color: #dc3545;
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