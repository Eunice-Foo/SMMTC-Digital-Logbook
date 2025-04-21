<?php
// filepath: c:\xampp\htdocs\log\admin\check_thumbnails.php
require_once '../includes/session_check.php';
require_once '../includes/db.php';

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_ADMIN) {
    header('Location: ../login.php');
    exit();
}

$stats = [
    'total' => 0,
    'thumbnails' => 0,
    'lqip_thumbs' => 0,
    'webp_main' => 0,
    'missing_thumbs' => 0
];

// Check the thumbnail status of all media
$stmt = $conn->prepare("
    SELECT media_id, file_name, file_type
    FROM media
    WHERE file_type LIKE 'image/%'
    ORDER BY upload_date DESC
    LIMIT 100
");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats['total'] = count($images);
$missing_thumbs = [];

foreach ($images as $image) {
    $filename = pathinfo($image['file_name'], PATHINFO_FILENAME);
    
    // Check for thumbnails with new naming convention
    $thumb = file_exists("../uploads/thumbnails/{$filename}_thumb.webp");
    $lqip = file_exists("../uploads/thumbnails/{$filename}_lqip.webp");
    $webp = file_exists("../uploads/{$filename}.webp");
    
    // Legacy check (for backward compatibility)
    $sm = file_exists("../uploads/thumbnails/{$filename}_sm.webp");
    $md = file_exists("../uploads/thumbnails/{$filename}_md.webp");
    
    // Count if either new thumb or any legacy thumb exists
    if ($thumb || $sm || $md) $stats['thumbnails']++;
    if ($lqip) $stats['lqip_thumbs']++;
    if ($webp) $stats['webp_main']++;
    
    if (!$thumb && !$lqip) {
        $stats['missing_thumbs']++;
        $missing_thumbs[] = [
            'id' => $image['media_id'],
            'filename' => $image['file_name'],
            'thumb' => $thumb,
            'lqip' => $lqip,
            'webp' => $webp
        ];
    }
}

// Regenerate thumbnails if requested
if (isset($_POST['regenerate'])) {
    require_once '../includes/image_converter.php';
    
    $regenerated = 0;
    
    foreach ($missing_thumbs as $img) {
        $srcPath = "../uploads/" . $img['filename'];
        if (file_exists($srcPath)) {
            $webpPath = createThumbnailsAndWebP($srcPath, $img['filename'], 90);
            if ($webpPath) $regenerated++;
        }
    }
    
    $message = "Regenerated thumbnails for $regenerated images.";
    header("Location: check_thumbnails.php?msg=" . urlencode($message));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thumbnail Check</title>
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .missing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .missing-table th, .missing-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
        .regenerate-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../components/side_menu.php'; ?>
    
    <div class="main-content">
        <h1>Thumbnail Status Check</h1>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div>Total Images</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div>Thumbnails</div>
                <div class="stat-value"><?php echo $stats['thumbnails']; ?></div>
            </div>
            <div class="stat-card">
                <div>LQIP Thumbnails</div>
                <div class="stat-value"><?php echo $stats['lqip_thumbs']; ?></div>
            </div>
            <div class="stat-card">
                <div>WebP Versions</div>
                <div class="stat-value"><?php echo $stats['webp_main']; ?></div>
            </div>
            <div class="stat-card">
                <div>Missing Thumbnails</div>
                <div class="stat-value"><?php echo $stats['missing_thumbs']; ?></div>
            </div>
        </div>
        
        <?php if (!empty($missing_thumbs)): ?>
            <h2>Images Missing Thumbnails</h2>
            <table class="missing-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Filename</th>
                        <th>Thumb</th>
                        <th>LQIP</th>
                        <th>WebP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missing_thumbs as $img): ?>
                        <tr>
                            <td><?php echo $img['id']; ?></td>
                            <td><?php echo htmlspecialchars($img['filename']); ?></td>
                            <td><?php echo $img['thumb'] ? '✅' : '❌'; ?></td>
                            <td><?php echo $img['lqip'] ? '✅' : '❌'; ?></td>
                            <td><?php echo $img['webp'] ? '✅' : '❌'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="post">
                <button type="submit" name="regenerate" value="yes" class="regenerate-btn">
                    Regenerate Missing Thumbnails
                </button>
            </form>
        <?php else: ?>
            <p>All images have the necessary thumbnails. 👍</p>
        <?php endif; ?>
    </div>
</body>
</html>