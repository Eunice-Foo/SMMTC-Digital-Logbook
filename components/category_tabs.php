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
    
    // Determine if we're on main_menu.php
    $isMainMenu = strpos($_SERVER['PHP_SELF'], 'main_menu.php') !== false;
    ?>
    <div class="category-tabs">
        <?php foreach ($categories as $value => $label): ?>
            <?php if ($isMainMenu): ?>
                <!-- For main_menu.php, use direct links -->
                <a href="main_menu.php?category=<?php echo urlencode($value); ?>" 
                   class="category-tab <?php echo ($activeCategory === $value) ? 'active' : ''; ?>"
                   data-category="<?php echo htmlspecialchars($value); ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php else: ?>
                <!-- For other pages like portfolio.php, use buttons with JS filtering -->
                <button class="category-tab <?php echo ($activeCategory === $value) ? 'active' : ''; ?>" 
                        data-category="<?php echo htmlspecialchars($value); ?>">
                    <?php echo htmlspecialchars($label); ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}
?>