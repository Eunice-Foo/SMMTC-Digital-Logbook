<?php
// filepath: c:\xampp\htdocs\log\components\category_tabs.php
/**
 * Renders category tabs for filtering
 * 
 * @param string $activeCategory The currently active category
 * @param array $categories List of categories to display
 */
function renderCategoryTabs($activeCategory = 'all', $categories = []) {
    // Default categories if none provided
    if (empty($categories)) {
        $categories = [
            'all' => 'All',
            'Image' => 'Images',
            'Video' => 'Videos',
            'Animation' => 'Animations',
            '3D Model' => '3D Models',
            'UI/UX' => 'UI/UX',
            'Graphic Design' => 'Graphic Design',
            'Other' => 'Other'
        ];
    }
    ?>
    <div class="category-tabs">
        <?php foreach ($categories as $value => $label): ?>
            <button class="category-tab <?php echo ($activeCategory === $value) ? 'active' : ''; ?>" 
                    data-category="<?php echo htmlspecialchars($value); ?>">
                <?php echo htmlspecialchars($label); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php
}
?>