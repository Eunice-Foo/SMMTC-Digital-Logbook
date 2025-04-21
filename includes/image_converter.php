<?php
/**
 * Functions for image optimization and conversion to next-gen formats
 */

/**
 * Convert an image to WebP format
 * 
 * @param string $source_path Path to the original image
 * @param int $quality WebP quality (0-100)
 * @return string|bool Path to WebP file or false on failure
 */
function convertToWebP($source_path, $quality = 80) {
    // Skip if file doesn't exist or is already WebP
    if (!file_exists($source_path) || pathinfo($source_path, PATHINFO_EXTENSION) === 'webp') {
        return false;
    }
    
    // Generate WebP path
    $webp_path = pathinfo($source_path, PATHINFO_DIRNAME) . '/' . 
                pathinfo($source_path, PATHINFO_FILENAME) . '.webp';
    
    // Check if WebP version exists and is newer than source file
    if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($source_path)) {
        return $webp_path;
    }
    
    // Check if GD is available with WebP support
    if (!function_exists('imagewebp')) {
        error_log("WebP conversion not available: imagewebp function missing");
        return false;
    }
    
    // Detect image type from path
    $mime_type = mime_content_type($source_path);
    
    // Create image resource based on file type
    $image = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            
            // Preserve alpha channel
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        default:
            error_log("Unsupported image format for WebP conversion: $mime_type");
            return false;
    }
    
    if (!$image) {
        error_log("Failed to create image resource from: $source_path");
        return false;
    }
    
    // Convert to WebP format
    $result = imagewebp($image, $webp_path, $quality);
    imagedestroy($image);
    
    if ($result) {
        // Log file size reduction
        $original_size = filesize($source_path);
        $webp_size = filesize($webp_path);
        $saving_percent = round(($original_size - $webp_size) / $original_size * 100);
        error_log("WebP conversion: $source_path - Reduced by $saving_percent% ($original_size → $webp_size bytes)");
        return $webp_path;
    }
    
    return false;
}

/**
 * Render optimized image with WebP and fallback
 * 
 * @param string $image_path Original image path
 * @param string $alt Alt text
 * @param string $class CSS classes to add
 * @param string $attributes Additional HTML attributes
 * @return string HTML with picture and source elements
 */
function renderOptimizedImage($image_path, $alt = '', $class = '', $attributes = '') {
    $full_path = $image_path;
    
    // Handle paths without uploads prefix
    if (strpos($image_path, 'uploads/') !== 0) {
        $full_path = 'uploads/' . $image_path;
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    
    // Skip WebP source for already webp images
    $webp_source = '';
    if ($extension !== 'webp') {
        // Generate WebP path
        $webp_path = pathinfo($full_path, PATHINFO_DIRNAME) . '/' . 
                    pathinfo($full_path, PATHINFO_FILENAME) . '.webp';
                    
        // Create webp version if doesn't exist yet
        if (!file_exists($webp_path) && file_exists($full_path)) {
            convertToWebP($full_path);
        }
        
        // Check again if WebP exists (it might have been created)
        if (file_exists($webp_path)) {
            $webp_source = '<source srcset="' . htmlspecialchars($webp_path) . '" type="image/webp">';
        }
    }
    
    // Generate HTML picture element
    $picture_html = '<picture>';
    
    // Add WebP source if available
    if (!empty($webp_source)) {
        $picture_html .= $webp_source;
    }
    
    // Add original image as fallback
    $picture_html .= '<img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($alt) . '"';
    
    // Add optional class
    if (!empty($class)) {
        $picture_html .= ' class="' . htmlspecialchars($class) . '"';
    }
    
    // Add any other attributes
    if (!empty($attributes)) {
        $picture_html .= ' ' . $attributes;
    }
    
    $picture_html .= '></picture>';
    
    return $picture_html;
}

/**
 * Create thumbnails and WebP versions for an image with improved quality
 * 
 * @param string $srcPath Path to the source image
 * @param string $filename Name of the source image file
 * @param int $quality WebP quality (0-100)
 * @return string|bool Path to the main WebP file or false on failure
 */
function createThumbnailsAndWebP($srcPath, $filename, $quality = 90) {
    $thumbDir = "uploads/thumbnails/";
    if (!file_exists($thumbDir)) mkdir($thumbDir, 0755, true);

    $sizes = [
        'sm' => 350,    // Grid thumbnail - CHANGE 'thumb' TO 'sm' for consistency
        'md' => 600,    // Medium size for detail views
        'lqip' => 24    // Low-quality image placeholder for blur-up effect
    ];

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) return false;

    // Create image resource with appropriate function based on image type
    if ($ext === 'png') {
        $img = imagecreatefrompng($srcPath);
        // For PNGs, preserve transparency
        imagealphablending($img, false);
        imagesavealpha($img, true);
    } else {
        $img = imagecreatefromjpeg($srcPath);
    }

    // Get original dimensions
    $orig_width = imagesx($img);
    $orig_height = imagesy($img);
    
    // Generate thumbnails at each size
    foreach ($sizes as $suffix => $target_width) {
        // Calculate height maintaining aspect ratio
        $ratio = $target_width / $orig_width;
        $target_height = intval($orig_height * $ratio);
        
        // Create thumbnail canvas
        $thumb = imagecreatetruecolor($target_width, $target_height);
        
        // Preserve transparency for PNGs
        if ($ext === 'png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        
        // Use high quality resampling
        imagecopyresampled(
            $thumb, $img, 
            0, 0, 0, 0, 
            $target_width, $target_height, 
            $orig_width, $orig_height
        );
        
        // Path for the thumbnail
        $thumbPath = $thumbDir . pathinfo($filename, PATHINFO_FILENAME) . "_$suffix.webp";
        
        // Quality settings: higher for regular thumbnails, lower for LQIP
        $thumbQuality = ($suffix === 'lqip') ? 40 : $quality; 
        
        // Save as WebP
        imagewebp($thumb, $thumbPath, $thumbQuality);
        imagedestroy($thumb);

        // For LQIP, also save as base64 for inline blur-up
        if ($suffix === 'lqip') {
            ob_start();
            imagewebp($thumb, null, 40);
            $lqipData = ob_get_clean();
            file_put_contents($thumbDir . pathinfo($filename, PATHINFO_FILENAME) . "_lqip.b64", base64_encode($lqipData));
        }
    }

    // Save main WebP version with high quality
    $webpPath = "uploads/" . pathinfo($filename, PATHINFO_FILENAME) . ".webp";
    imagewebp($img, $webpPath, min(95, $quality + 5));

    imagedestroy($img);
    return $webpPath;
}
?>