<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['portfolio_id'])) {
    $portfolio_id = $_POST['portfolio_id'];
    
    try {
        // First check if the user owns this portfolio
        $stmt = $conn->prepare("SELECT user_id FROM portfolio WHERE portfolio_id = :portfolio_id");
        $stmt->execute([':portfolio_id' => $portfolio_id]);
        $portfolio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$portfolio || $portfolio['user_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this portfolio']);
            exit();
        }
        
        $conn->beginTransaction();
        
        // Get all media files associated with this portfolio
        $stmt = $conn->prepare("
            SELECT m.media_id, m.file_name 
            FROM portfolio_media pm
            JOIN media m ON pm.media_id = m.media_id
            WHERE pm.portfolio_id = :portfolio_id
        ");
        $stmt->execute([':portfolio_id' => $portfolio_id]);
        $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete portfolio_media associations
        $stmt = $conn->prepare("DELETE FROM portfolio_media WHERE portfolio_id = :portfolio_id");
        $stmt->execute([':portfolio_id' => $portfolio_id]);
        
        // Delete the portfolio
        $stmt = $conn->prepare("DELETE FROM portfolio WHERE portfolio_id = :portfolio_id AND user_id = :user_id");
        $stmt->execute([
            ':portfolio_id' => $portfolio_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        // Check each media file if it's used elsewhere before deleting
        foreach ($mediaFiles as $media) {
            // Check if media is used in other portfolios
            $stmt = $conn->prepare("SELECT COUNT(*) FROM portfolio_media WHERE media_id = :media_id");
            $stmt->execute([':media_id' => $media['media_id']]);
            $usedInOtherPortfolios = $stmt->fetchColumn() > 0;
            
            // Check if media is used in logs
            $stmt = $conn->prepare("SELECT COUNT(*) FROM log_media WHERE media_id = :media_id");
            $stmt->execute([':media_id' => $media['media_id']]);
            $usedInLogs = $stmt->fetchColumn() > 0;
            
            // If not used elsewhere, delete the media and file
            if (!$usedInOtherPortfolios && !$usedInLogs) {
                $stmt = $conn->prepare("DELETE FROM media WHERE media_id = :media_id");
                $stmt->execute([':media_id' => $media['media_id']]);
                
                // Delete physical file
                $filePath = "uploads/" . $media['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete thumbnail if exists
                $thumbnailPath = "uploads/thumbnails/" . pathinfo($media['file_name'], PATHINFO_FILENAME) . '.jpg';
                if (file_exists($thumbnailPath)) {
                    unlink($thumbnailPath);
                }
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>