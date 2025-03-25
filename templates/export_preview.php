<div class="export-preview">
    <h3>Logbook Export Preview</h3>
    
    <?php foreach ($log_entries as $entry): ?>
        <div class="preview-entry">
            <div class="entry-header">
                <h4><?php echo htmlspecialchars($entry['entry_title']); ?></h4>
                <div class="entry-meta">
                    <span class="date">
                        <?php echo date('F j, Y', strtotime($entry['entry_date'])); ?>
                    </span>
                    <span class="time">
                        <?php echo date('g:i A', strtotime($entry['entry_time'])); ?>
                    </span>
                    <span class="status <?php echo $entry['entry_status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $entry['entry_status'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="entry-description">
                <?php echo nl2br(htmlspecialchars($entry['entry_description'])); ?>
            </div>

            <?php if ($includeMedia && !empty($entry['media_files'])): ?>
                <div class="entry-media">
                    <h5>Media Files:</h5>
                    <div class="media-preview">
                        <?php 
                        $media_files = explode(',', $entry['media_files']);
                        foreach ($media_files as $file): 
                            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        ?>
                            <div class="media-item">
                                <?php if (in_array($file_extension, ['mp4', 'mov'])): ?>
                                    <div class="video-preview">
                                        <?php
                                        $thumbnail_path = 'uploads/thumbnails/' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                                        if (file_exists($thumbnail_path)): ?>
                                            <img src="<?php echo htmlspecialchars($thumbnail_path); ?>" alt="Video Thumbnail">
                                        <?php else: ?>
                                            <div class="video-placeholder">🎥</div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($file); ?>" alt="Media">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
.export-preview {
    padding: 20px;
    background: white;
    border-radius: 8px;
}

.preview-entry {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 15px;
}

.entry-header {
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 10px;
    margin-bottom: 10px;
}

.entry-header h4 {
    margin: 0 0 10px 0;
}

.entry-meta {
    display: flex;
    gap: 15px;
    color: var(--text-secondary);
    font-size: 0.9em;
}

.status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85em;
}

.status.pending_review {
    background-color: var(--warning-color);
    color: white;
}

.status.reviewed {
    background-color: var(--success-color);
    color: white;
}

.entry-description {
    margin: 15px 0;
    white-space: pre-line;
}

.entry-media {
    margin-top: 15px;
}

.entry-media h5 {
    margin: 0 0 10px 0;
}

.media-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.media-item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-preview {
    width: 100%;
    height: 100%;
    background-color: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.video-placeholder {
    font-size: 32px;
    opacity: 0.5;
}
</style>