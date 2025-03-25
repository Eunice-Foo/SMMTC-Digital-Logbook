<?php
function renderPagination($total_results, $items_per_page, $current_page) {
    $total_pages = ceil($total_results / $items_per_page);
    if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo ($current_page - 1); ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   class="<?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo ($current_page + 1); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif;
}
?>

<style>
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 20px 0;
}
.pagination a {
    padding: 8px 16px;
    text-decoration: none;
    border: 1px solid #ddd;
    color: black;
}
.pagination a.active {
    background-color: #4CAF50;
    color: white;
    border-color: #4CAF50;
}
</style>