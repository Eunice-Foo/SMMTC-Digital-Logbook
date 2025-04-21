<?php
// filepath: c:\xampp\htdocs\log\components\media_slideshow_gallery.php

/**
 * Renders a slideshow gallery of media files
 * 
 * @param array $mediaFiles Array of media file data from database
 * @param string $title Optional title for the slideshow
 * @return void
 */
function renderMediaSlideshowGallery($mediaFiles, $title = '') {
    if (empty($mediaFiles)) {
        echo '<div class="empty-state"><p>No media available to display.</p></div>';
        return;
    }
    
    // Count total files
    $totalFiles = count($mediaFiles);
    
    // Extract just the filenames or use full media objects
    $files = is_array($mediaFiles[0]) ? $mediaFiles : array_map(function($file) {
        return ['file_name' => $file, 'file_type' => ''];
    }, $mediaFiles);
    
    // Create unique ID for this gallery instance
    $galleryId = 'gallery_' . rand(1000, 9999);
?>
<div class="slideshow-gallery-container" id="<?php echo $galleryId; ?>_container">
    <?php if (!empty($title)): ?>
        <h2 style="text-align:center"><?php echo htmlspecialchars($title); ?></h2>
    <?php endif; ?>
    
    <div class="slideshow-container">
        <?php foreach ($files as $index => $item): 
            $filename = $item['file_name'];
            $fileType = isset($item['file_type']) ? $item['file_type'] : '';
            $isVideo = strpos($fileType, 'video/') === 0 || preg_match('/\.(mp4|mov)$/i', $filename);
            $number = $index + 1;
        ?>
            <div class="mySlides fade">
                <div class="numbertext"><?php echo $number; ?> / <?php echo $totalFiles; ?></div>
                
                <?php if ($isVideo): ?>
                    <div class="video-container">
                        <video controls class="slide-media">
                            <source src="uploads/<?php echo htmlspecialchars($filename); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php else: ?>
                    <img src="uploads/<?php echo htmlspecialchars($filename); ?>" class="slide-media" alt="Media <?php echo $number; ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Navigation arrows -->
        <a class="prev" onclick="plusSlides(-1, '<?php echo $galleryId; ?>')">❮</a>
        <a class="next" onclick="plusSlides(1, '<?php echo $galleryId; ?>')">❯</a>

        <!-- Thumbnail row -->
        <div class="thumbnail-row">
            <?php foreach ($files as $index => $item): 
                $filename = $item['file_name'];
                $fileType = isset($item['file_type']) ? $item['file_type'] : '';
                $isVideo = strpos($fileType, 'video/') === 0 || preg_match('/\.(mp4|mov)$/i', $filename);
                $mediaName = isset($item['title']) ? $item['title'] : "Media " . ($index + 1);
                $number = $index + 1;
                
                // For thumbnails, use optimized versions if available
                if ($isVideo) {
                    $thumbPath = "uploads/thumbnails/" . pathinfo($filename, PATHINFO_FILENAME) . ".jpg";
                    if (!file_exists($thumbPath)) {
                        $thumbPath = "uploads/" . $filename; // Fallback to original
                    }
                } else {
                    // Try thumbnail, then original
                    $baseName = pathinfo($filename, PATHINFO_FILENAME);
                    $thumbPath = "uploads/thumbnails/{$baseName}_thumb.webp";
                    if (!file_exists($thumbPath)) {
                        $thumbPath = "uploads/" . $filename;
                    }
                }
            ?>
                <div class="column">
                    <img 
                        class="demo cursor <?php echo $index === 0 ? 'active' : ''; ?>"  
                        src="<?php echo htmlspecialchars($thumbPath); ?>" 
                        onclick="currentSlide(<?php echo $number; ?>, '<?php echo $galleryId; ?>')" 
                        alt="<?php echo htmlspecialchars($mediaName); ?>"
                    >
                    <?php if ($isVideo): ?>
                        <div class="play-indicator-thumb">▶</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* Slideshow Gallery Styles - Adapted to match your theme */
.slideshow-gallery-container {
    max-width: 1200px;
    margin: 30px auto;
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.slideshow-container {
    position: relative;
    margin: auto;
}

.mySlides {
    display: none;
    text-align: center;
}

.fade {
    animation-name: fade;
    animation-duration: 0.5s;
}

@keyframes fade {
    from {opacity: .4} 
    to {opacity: 1}
}

.slide-media {
    max-width: 100%;
    max-height: 500px;
    object-fit: contain;
    margin: 0 auto;
    border-radius: 8px;
}

.video-container {
    display: flex;
    justify-content: center;
    height: 500px;
    max-width: 100%;
    margin: 0 auto;
}

.video-container video {
    max-width: 100%;
    max-height: 100%;
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 40%;
    width: auto;
    padding: 16px;
    margin-top: -22px;
    color: white;
    font-weight: bold;
    font-size: 20px;
    border-radius: 0 3px 3px 0;
    user-select: none;
    background-color: rgba(0,0,0,0.3);
    transition: 0.3s ease;
}

.next {
    right: 0;
    border-radius: 3px 0 0 3px;
}

.prev:hover, .next:hover {
    background-color: rgba(0,0,0,0.8);
}

.numbertext {
    color: #f2f2f2;
    font-size: 14px;
    padding: 8px 12px;
    position: absolute;
    top: 0;
    left: 0;
    background-color: rgba(0,0,0,0.5);
    border-radius: 0 0 8px 0;
}

.thumbnail-row {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding-bottom: 10px;
    scrollbar-width: thin;
    margin-top: 16px; /* Added more spacing now that caption is gone */
}

.column {
    flex: 0 0 auto;
    width: 120px;
    position: relative;
}

.demo {
    opacity: 0.6;
    height: 80px;
    width: 100%;
    object-fit: cover;
    transition: opacity 0.3s;
    border-radius: 4px;
}

.active, .demo:hover {
    opacity: 1;
    border: 2px solid var(--primary-color);
}

.play-indicator-thumb {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.6);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    pointer-events: none;
}

@media (max-width: 768px) {
    .slideshow-gallery-container {
        padding: 15px;
    }
    
    .slide-media, .video-container {
        height: auto;
        max-height: 350px;
    }
    
    .column {
        width: 80px;
    }
    
    .demo {
        height: 60px;
    }
}
</style>

<script>
// Initialize the slideshow
document.addEventListener('DOMContentLoaded', function() {
    // Create slideshow state object
    if (!window.slideshows) window.slideshows = {};
    
    // Initialize this slideshow instance
    window.slideshows['<?php echo $galleryId; ?>'] = {
        index: 1,
        initialized: false
    };
    
    // Show first slide
    showSlides(1, '<?php echo $galleryId; ?>');
    
    // Mark as initialized
    window.slideshows['<?php echo $galleryId; ?>'].initialized = true;
});

function plusSlides(n, galleryId) {
    const slideshow = window.slideshows[galleryId];
    showSlides(slideshow.index + n, galleryId);
}

function currentSlide(n, galleryId) {
    showSlides(n, galleryId);
}

function showSlides(n, galleryId) {
    const slideshow = window.slideshows[galleryId];
    const container = document.getElementById(galleryId + '_container');
    const slides = container.getElementsByClassName("mySlides");
    const dots = container.getElementsByClassName("demo");
    
    // Handle out of bounds indices
    let newIndex = n;
    if (n > slides.length) newIndex = 1;
    if (n < 1) newIndex = slides.length;
    
    // Update slideshow state
    slideshow.index = newIndex;
    
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    
    // Remove active class from thumbnails
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    
    // Show current slide
    slides[newIndex - 1].style.display = "block";
    
    // Highlight current thumbnail
    dots[newIndex - 1].className += " active";
    
    // If there's a video in the previous slide, pause it
    if (slideshow.initialized) {
        for (let i = 0; i < slides.length; i++) {
            const video = slides[i].querySelector('video');
            if (video) {
                video.pause();
            }
        }
    }
}
</script>
<?php
}
?>