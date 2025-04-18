<?php
require_once '../includes/session_check.php';
require_once '../includes/db.php';
require_once '../includes/image_converter.php';

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_ADMIN) {
    header('Location: ../login.php');
    exit();
}

$stats = [
    'total' => 0,
    'converted' => 0,
    'failed' => 0,
    'savings_bytes' => 0
];

// Process conversion if form submitted
if (isset($_POST['convert'])) {
    try {
        // Get all media files from database
        $stmt = $conn->prepare("
            SELECT m.media_id, m.file_name, m.file_type 
            FROM media m
            WHERE m.file_type LIKE 'image/%'
            AND m.file_type != 'image/webp'
        ");
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['total'] = count($images);
        
        // Process each image
        foreach ($images as $image) {
            $source_path = "../uploads/" . $image['file_name'];
            
            if (!file_exists($source_path)) {
                $stats['failed']++;
                continue;
            }
            
            $original_size = filesize($source_path);
            $webp_path = convertToWebP($source_path);
            
            if ($webp_path && file_exists($webp_path)) {
                $webp_size = filesize($webp_path);
                $stats['savings_bytes'] += ($original_size - $webp_size);
                $stats['converted']++;
            } else {
                $stats['failed']++;
            }
        }
        
        $success = true;
        $message = "Conversion complete";
    } catch (Exception $e) {
        $success = false;
        $message = "Error: " . $e->getMessage();
    }
}

// Get count of images that can be converted
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM media 
    WHERE file_type LIKE 'image/%'
    AND file_type != 'image/webp'
");
$stmt->execute();
$total_convertible = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convert Images to WebP</title>
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: var(--primary-hover);
        }
    </style>
</head>
<body>
    <?php include '../components/side_menu.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h1>Convert Images to WebP</h1>
            
            <?php if (isset($message)): ?>
                <div class="<?= $success ? 'success' : 'error' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>About WebP Conversion</h3>
                <p>WebP is a modern image format that provides superior compression compared to JPEG and PNG, resulting in smaller file sizes and faster page loads.</p>
                <p>This tool will convert your existing images to WebP format while keeping the original files as a fallback for older browsers.</p>
            </div>
            
            <?php if (isset($_POST['convert'])): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div>Total Images</div>
                        <div class="stat-value"><?= $stats['total'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div>Converted</div>
                        <div class="stat-value"><?= $stats['converted'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div>Failed</div>
                        <div class="stat-value"><?= $stats['failed'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div>Space Saved</div>
                        <div class="stat-value"><?= round($stats['savings_bytes'] / (1024 * 1024), 2) ?> MB</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="warning">
                    <p>Found <strong><?= $total_convertible ?></strong> images that can be converted to WebP format.</p>
                    <p>Converting large numbers of images may take some time.</p>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <button type="submit" name="convert" value="yes" class="btn">
                    Convert All Images to WebP
                </button>
            </form>
        </div>
    </div>
</body>
</html>