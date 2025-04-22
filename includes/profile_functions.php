<?php
/**
 * Get the appropriate path for profile picture
 * 
 * @param string|null $filename The profile picture filename
 * @param string $size Size variant ('sm', 'md', 'profile')
 * @return string URL to the profile picture
 */
function getProfileImagePath($filename, $size = 'profile') {
    if (empty($filename)) {
        // Return default placeholder image
        return "images/default_profile.png";
    }
    
    // Check for thumbnail
    $thumbPath = "uploads/profile/thumbnails/" . pathinfo($filename, PATHINFO_FILENAME) . "_" . $size . ".webp";
    if (file_exists($thumbPath)) {
        return $thumbPath;
    }
    
    // Fall back to original image
    return "uploads/profile/" . $filename;
}
?>