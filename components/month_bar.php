<?php
function renderMonthBar($log_entries, $practicum_start_date, $practicum_duration) {
    $months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 
              'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    
    // Get start month and generate sequence of months
    $start_month = (int)date('n', strtotime($practicum_start_date)) - 1; // 0-based index
    $practicum_months = [];
    
    for ($i = 0; $i < $practicum_duration; $i++) {
        $month_index = ($start_month + $i) % 12;
        $practicum_months[] = $month_index;
    }
    
    // Get available months from log entries
    $available_months = [];
    foreach ($log_entries as $entry) {
        $month = date('n', strtotime($entry['entry_date'])) - 1;
        if (!in_array($month, $available_months)) {
            $available_months[] = $month;
        }
    }

    // Get current month for active state
    $current_month = (int)date('n') - 1; // 0-based index
    ?>
    <div class="month-bar" data-duration="<?php echo $practicum_duration; ?>">
        <?php
        foreach ($practicum_months as $month_index) {
            $active = ($month_index === $current_month) ? 'active' : '';
            $month = $months[$month_index];
            echo "<button class='month-btn $active' data-month='$month_index' onclick='filterLogsByMonth($month_index)'>$month</button>";
        }
        ?>
    </div>
    <?php
}
?>