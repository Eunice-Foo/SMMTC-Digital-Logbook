<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if (!isset($_GET['month']) || !isset($_GET['year'])) {
    exit('Missing parameters');
}

function generateCalendar($conn, $userId, $month, $year) {
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $first_day_of_week = date('w', $first_day);
    
    // Get all log entries for this month with their first media
    $stmt = $conn->prepare("
        SELECT 
            le.entry_id,
            le.entry_date,
            MIN(m.file_name) as first_media,
            MIN(m.file_type) as media_type
        FROM log_entry le
        LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
        LEFT JOIN media m ON lm.media_id = m.media_id
        WHERE le.user_id = :user_id 
        AND MONTH(le.entry_date) = :month 
        AND YEAR(le.entry_date) = :year
        GROUP BY le.entry_id, le.entry_date
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':month' => $month,
        ':year' => $year
    ]);
    
    $entries = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $entries[date('j', strtotime($row['entry_date']))] = $row;
    }
    
    $calendar = "<table class='calendar-table'>";
    $calendar .= "<thead><tr>";
    $calendar .= "<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>";
    $calendar .= "</tr></thead><tbody>";
    
    $day_count = 1;
    
    for($i = 0; $i < 6; $i++) {
        $calendar .= "<tr>";
        for($j = 0; $j < 7; $j++) {
            if(($i == 0 && $j < $first_day_of_week) || ($day_count > $days_in_month)) {
                $calendar .= "<td class='calendar-day other-month'></td>";
            } else {
                $has_entry = isset($entries[$day_count]) ? 'has-entry' : '';
                $calendar .= "<td class='calendar-day $has_entry'>";
                $calendar .= "<div class='calendar-date'>$day_count</div>";
                
                if(isset($entries[$day_count])) {
                    $entry = $entries[$day_count];
                    if($entry['first_media']) {
                        $calendar .= "<div class='calendar-entry' onclick='viewLog({$entry['entry_id']})'>";
                        if (strpos($entry['media_type'], 'video/') === 0) {
                            $calendar .= "<div class='video-placeholder'>🎥</div>";
                        } else {
                            $calendar .= "<img src='uploads/{$entry['first_media']}' alt='Entry Media'>";
                        }
                        $calendar .= "</div>";
                    }
                }
                
                $calendar .= "</td>";
                $day_count++;
            }
        }
        $calendar .= "</tr>";
        if($day_count > $days_in_month) break;
    }
    
    $calendar .= "</tbody></table>";
    return $calendar;
}

echo generateCalendar($conn, $_SESSION['user_id'], $_GET['month'], $_GET['year']);
?>