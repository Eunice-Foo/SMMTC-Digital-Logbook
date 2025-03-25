<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$entries = $data['entries'];
$includeMedia = $data['includeMedia'];

$stmt = $conn->prepare("
    SELECT 
        le.*,
        GROUP_CONCAT(CONCAT('uploads/', m.file_name)) as media_files
    FROM log_entry le
    LEFT JOIN log_media lm ON le.entry_id = lm.entry_id
    LEFT JOIN media m ON lm.media_id = m.media_id
    WHERE le.entry_id IN (" . str_repeat('?,', count($entries)-1) . "?)
    GROUP BY le.entry_id
    ORDER BY le.entry_date, le.entry_time
");

$stmt->execute($entries);
$log_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate preview HTML
include 'templates/export_preview.php';