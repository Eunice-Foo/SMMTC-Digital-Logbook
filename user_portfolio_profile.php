<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';
require_once 'components/media_count_label.php';
require_once 'components/portfolio_card.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: main_menu.php");
    exit();
}

$user_id = $_GET['id'];
$is_own_profile = ($user_id == $_SESSION['user_id']);

try {
    // Get user info
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.user_name,
            u.email,
            u.role,
            COALESCE(s.full_name, sv.supervisor_name) as full_name,
            CASE 
                WHEN u.role = 1 THEN s.institution
                WHEN u.role = 2 THEN sv.company_name
                ELSE NULL
            END as institution,
            CASE
                WHEN u.role = 1 THEN s.phone_number
                WHEN u.role = 2 THEN sv.contact_number
                ELSE NULL
            END as contact_number,
            CASE 
                WHEN u.role = 1 THEN s.programme
                WHEN u.role = 2 THEN sv.designation
                ELSE NULL
            END as position
        FROM user u
        LEFT JOIN student s ON u.user_id = s.student_id AND u.role = 1
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id AND u.role = 2
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: main_menu.php");
        exit();
    }

    // Get user's portfolio items
    $stmt = $conn->prepare("
        SELECT 
            p.portfolio_id,
            p.portfolio_title,
            p.portfolio_date, 
            p.portfolio_time, 
            p.category,
            u.user_name as username,
            u.user_id,
            COALESCE(s.full_name, sv.supervisor_name) as full_name,
            m.file_name as media, 
            m.file_type,
            (SELECT COUNT(*) FROM portfolio_media pm2 WHERE pm2.portfolio_id = p.portfolio_id) as media_count
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN student s ON u.user_id = s.student_id
        LEFT JOIN supervisor sv ON u.user_id = sv.supervisor_id
        LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        LEFT JOIN media m ON pm.media_id = m.media_id
        WHERE p.user_id = :user_id
        GROUP BY p.portfolio_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
        LIMIT 8
    ");
    $stmt->execute([':user_id' => $user_id]);
    $portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total portfolio items
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.portfolio_id) as count
        FROM portfolio p
        WHERE p.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['full_name']); ?> - Profile</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <link rel="stylesheet" href="css/user_portfolio_profile.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'components/topnav.php'; ?>
    
    <div class="main-content">
        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-title">
                        <?php if ($user['role'] == ROLE_STUDENT): ?>
                            Student at <?php echo htmlspecialchars($user['institution']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($user['position']); ?> at <?php echo htmlspecialchars($user['institution']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="profile-details">
                <div class="detail-item">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <?php if (!empty($user['contact_number'])): ?>
                <div class="detail-item">
                    <span class="label">Contact:</span>
                    <span class="value"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <span class="label">Role:</span>
                    <span class="value"><?php echo $user['role'] == ROLE_STUDENT ? 'Student' : 'Supervisor'; ?></span>
                </div>
            </div>
        </div>
        
        <div class="portfolio-section">
            <div class="section-header">
                <h2><?php echo htmlspecialchars($user['full_name']); ?>'s Portfolio</h2>
                <span class="count-badge"><?php echo $total_items; ?> items</span>
            </div>
            
            <?php if (empty($portfolio_items)): ?>
                <div class="empty-state">
                    <h3>No portfolio items yet</h3>
                    <p>This user hasn't added any portfolio items.</p>
                </div>
            <?php else: ?>
                <div class="gallery">
                    <?php foreach ($portfolio_items as $item): ?>
                        <?php renderPortfolioCard($item); ?>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_items > 8): ?>
                <div class="view-more">
                    <a href="user_portfolio.php?id=<?php echo $user_id; ?>" class="view-more-btn">View all <?php echo $total_items; ?> items</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/video_thumbnail.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            generateVideoThumbnails();
        });
    </script>
</body>
</html>