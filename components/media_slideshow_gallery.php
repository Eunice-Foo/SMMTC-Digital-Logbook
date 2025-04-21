<?php
// filepath: c:\xampp\htdocs\log\components\media_slideshow_gallery.php

/**
 * Renders a slideshow gallery of media files
 * 
 * @param array $mediaFiles Array of media file data from database
 * @param string $title Optional title for the slideshow (now ignored)
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
    <div class="slideshow-container">
        <?php foreach ($files as $index => $item): 
            $filename = $item['file_name'];
            $fileType = isset($item['file_type']) ? $item['file_type'] : '';
            $isVideo = strpos($fileType, 'video/') === 0 || preg_match('/\.(mp4|mov)$/i', $filename);
            $number = $index + 1;
        ?>
            <div class="mySlides slide">
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
        <div class="prev" onclick="plusSlides(-1, '<?php echo $galleryId; ?>')">
            <i class="fi fi-rr-arrow-small-left"></i>
        </div>
        <div class="next" onclick="plusSlides(1, '<?php echo $galleryId; ?>')">
            <i class="fi fi-rr-arrow-small-right"></i>
        </div>

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
    text-align: center;
}

/* Updated animation styles for smoother transitions */
.slide {
    position: relative;
    display: none;
}

/* Faster and smoother float-in animations */
.slide.slide-right {
    animation: floatRight 0.4s ease-out;
}

.slide.slide-left {
    animation: floatLeft 0.4s ease-out;
}

@keyframes floatRight {
    from {
        opacity: 0;
        transform: translateX(-40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes floatLeft {
    from {
        opacity: 0;
        transform: translateX(40px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }

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

/* Updated styles for navigation arrows */
.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 0;
    width: 20%;
    height: 100%;
    background-color: transparent;
    color: black;
    display: flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    transition: background-color 0.3s ease;
}

.prev {
    left: 0;
    justify-content: flex-start;
    padding-left: 20px;
}

.next {
    right: 0;
    justify-content: flex-end;
    padding-right: 20px;
}

.prev i, .next i {
    font-size: 36px;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.prev:hover i, .next:hover i {
    opacity: 1;
}

.thumbnail-row {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding-bottom: 10px;
    scrollbar-width: thin;
    margin-top: 16px;
    justify-content: center;
    padding: 0 20px;
}

.column {
    flex: 0 0 auto;
    width: 120px;
    position: relative;
    display: flex;
    justify-content: center;
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
    
    // Add keyboard navigation event listener
    document.addEventListener('keydown', function(e) {
        // Only handle keyboard events when this slideshow is visible
        const container = document.getElementById('<?php echo $galleryId; ?>_container');
        if (!container || !isElementInViewport(container)) return;
        
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            plusSlides(-1, '<?php echo $galleryId; ?>');
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            plusSlides(1, '<?php echo $galleryId; ?>');
        }
    });
});

// Helper function to check if element is visible in viewport
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Rest of existing functions
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
    
    // Determine direction (added this)
    let direction = 'slide-left'; // default direction
    if (slideshow.initialized && slideshow.index > newIndex) {
        direction = 'slide-right';
    }
    // Special case: going from last slide to first
    if (slideshow.index === 1 && newIndex === slides.length) {
        direction = 'slide-right';
    }
    // Special case: going from first slide to last
    if (slideshow.index === slides.length && newIndex === 1) {
        direction = 'slide-left';
    }
    
    // Update slideshow state
    slideshow.index = newIndex;
    
    // Hide all slides and remove direction classes
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
        slides[i].classList.remove("slide-left", "slide-right");
    }
    
    // Remove active class from thumbnails
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    
    // Add direction class to current slide and show it
    slides[newIndex - 1].classList.add(direction);
    slides[newIndex - 1].style.display = "block";
    
    // Highlight current thumbnail
    dots[newIndex - 1].className += " active";
    
    // If there's a video in the slides, pause them all
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