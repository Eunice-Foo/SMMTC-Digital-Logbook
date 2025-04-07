<?php
require_once 'includes/session_check.php';  // Add this line
require_once 'includes/db.php';

$search_results = [];
$total_results = 0;
$items_per_page = 8; // Show 8 items at a time (2 rows of 4)
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $items_per_page;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search = $_POST['search'];
    if (empty($search)) {
        $stmt = $conn->prepare("
            SELECT p.portfolio_title, p.portfolio_description, 
                   p.portfolio_date, p.portfolio_time, 
                   u.user_name as username, m.file_name as media, m.file_type
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare("
            SELECT p.portfolio_title, p.portfolio_description, 
                   p.portfolio_date, p.portfolio_time, 
                   u.user_name as username, m.file_name as media, m.file_type
            FROM portfolio p
            INNER JOIN user u ON p.user_id = u.user_id
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
            WHERE p.portfolio_title LIKE :search 
            OR p.portfolio_description LIKE :search
            ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $search_param = "%" . $search . "%";
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    if (empty($search)) {
        $count_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as count
            FROM portfolio p
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
        ");
    } else {
        $count_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.portfolio_id) as count
            FROM portfolio p
            LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
            LEFT JOIN media m ON pm.media_id = m.media_id
            WHERE p.portfolio_title LIKE :search 
            OR p.portfolio_description LIKE :search
        ");
        $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} else {
    // Initial page load
    $stmt = $conn->prepare("
        SELECT p.portfolio_title, p.portfolio_description, 
               p.portfolio_date, p.portfolio_time, 
               u.user_name as username, m.file_name as media, m.file_type
        FROM portfolio p
        INNER JOIN user u ON p.user_id = u.user_id
        LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        LEFT JOIN media m ON pm.media_id = m.media_id
        ORDER BY p.portfolio_date DESC, p.portfolio_time DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT p.portfolio_id) as count
        FROM portfolio p
        LEFT JOIN portfolio_media pm ON p.portfolio_id = pm.portfolio_id
        LEFT JOIN media m ON pm.media_id = m.media_id
    ");
    $count_stmt->execute();
    $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// If this is an AJAX request, return JSON
if (isset($_POST['ajax'])) {
    echo json_encode(['results' => $search_results, 'total' => $total_results]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Menu</title>
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="css/main_menu.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
    <?php include 'components/side_menu.php'; ?>

    <div class="main-content">
        <h2>Search Portfolio</h2>
        <form action="main_menu.php" method="post">
            <input type="text" name="search" placeholder="Search by title or description">
            <input type="submit" value="Search">
        </form>

        <h3>Total Results: <?php echo $total_results; ?></h3>
        <div class="gallery" id="gallery">
            <?php foreach ($search_results as $result): ?>
                <div class="media-card">
                    <div class="media-preview">
                        <?php if (strpos($result['file_type'], 'video/') === 0): ?>
                            <?php 
                            require_once 'components/video_thumbnail.php';
                            renderVideoThumbnail($result['media']);
                            ?>
                        <?php else: ?>
                            <img src="uploads/<?php echo htmlspecialchars($result['media']); ?>" alt="Portfolio Media">
                        <?php endif; ?>
                    </div>
                    <div class="card-info">
                        <h4><?php echo htmlspecialchars($result['portfolio_title']); ?></h4>
                        <p class="username">By: <?php echo htmlspecialchars($result['username']); ?></p>
                        <p class="date"><?php echo date('F j, Y g:i A', strtotime($result['portfolio_date'] . ' ' . $result['portfolio_time'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let loading = false;
        let hasMore = true;

        $(window).scroll(function() {
            if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
                if (!loading && hasMore) {
                    loadMore();
                }
            }
        });

        function loadMore() {
            loading = true;
            currentPage++;
            
            $.post('main_menu.php', {
                page: currentPage,
                search: $('input[name="search"]').val(),
                ajax: true
            }, function(data) {
                const response = JSON.parse(data);
                if (response.results.length > 0) {
                    response.results.forEach(result => {
                        $('#gallery').append(`
                            <div class="media-card">
                                <div class="media-preview">
                                    ${result.file_type.startsWith('video/') ? `
                                        <div class="video-thumbnail">
                                            <video preload="metadata">
                                                <source src="uploads/${result.media}" type="video/mp4">
                                            </video>
                                            <canvas class="video-canvas"></canvas>
                                            <div class="play-button">▶</div>
                                        </div>
                                    ` : `<img src="uploads/${result.media}" alt="Portfolio Media">`}
                                </div>
                                <div class="card-info">
                                    <h4>${result.portfolio_title}</h4>
                                    <p class="username">By: ${result.username}</p>
                                    <p class="date">${new Date(result.portfolio_date + ' ' + result.portfolio_time).toLocaleString()}</p>
                                </div>
                            </div>
                        `);
                    });
                    // Initialize video thumbnails for new content
                    generateVideoThumbnails();
                } else {
                    hasMore = false;
                }
                loading = false;
            });
        }
    </script>
</body>
</html>