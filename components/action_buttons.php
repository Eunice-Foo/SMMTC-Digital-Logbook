<?php
function renderActionButtons($entryId) { ?>
    <div class="action-buttons">
        <button class="btn btn-edit" onclick="window.location.href='edit_log.php?id=<?php echo $entryId; ?>'">
            Edit
        </button>
        <button class="btn btn-delete" onclick="confirmDelete(<?php echo $entryId; ?>)">
            Delete
        </button>
    </div>
<?php }
?>

<style>

</style>