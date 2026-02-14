<?php $view = 'index'; ?>
<?php $baseUrl = getBaseUrl(); ?>

<div class="gallery-container" data-authenticated="<?php echo isAuthenticated() ? '1' : '0'; ?>" data-base-url="<?php echo e($baseUrl); ?>" data-next-page="<?php echo isset($infiniteScroll) && $infiniteScroll && !empty($images) && !empty($hasNextPage) ? $currentPage + 1 : ''; ?>">
    <h2>Gallery</h2>
    
    <?php if (empty($images)): ?>
        <div class="empty-gallery">
            <p>No images yet. Be the first to share!</p>
            <?php if (isAuthenticated()): ?>
                <p><a href="/edit" class="button">Create Your First Photo</a></p>
            <?php else: ?>
                <p><a href="/register" class="button">Get Started</a> or <a href="/login">Login</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($images as $image): ?>
                <div class="image-card" id="image-<?php echo $image['id']; ?>">
                    <div class="image-wrapper">
                        <a href="/image/<?php echo $image['id']; ?>">
                            <img src="/uploads/<?php echo e($image['filename']); ?>" alt="Photo by <?php echo e($image['username']); ?>">
                        </a>
                        <?php if (isset($image['is_owner']) && $image['is_owner']): ?>
                            <button class="delete-image" data-image-id="<?php echo $image['id']; ?>">×</button>
                        <?php endif; ?>
                    </div>
                    <div class="image-info">
                        <div class="image-meta">
                            <span class="username">@<?php echo e($image['username']); ?></span>
                            <span class="date"><?php echo date('M d, Y', strtotime($image['created_at'])); ?></span>
                        </div>
                        <div class="image-actions">
                            <button class="like-btn <?php echo (isset($image['is_liked']) && $image['is_liked']) ? 'liked' : ''; ?>" 
                                    data-image-id="<?php echo $image['id']; ?>"
                                    <?php echo !isAuthenticated() ? 'disabled' : ''; ?>>
                                <span class="like-icon">♥</span>
                                <span class="like-count"><?php echo $image['like_count'] ?? 0; ?></span>
                            </button>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e(rawurlencode($baseUrl . '/image/' . $image['id'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-fb" title="Share on Facebook">f</a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo e(rawurlencode($baseUrl . '/image/' . $image['id'])); ?>&text=<?php echo e(rawurlencode('Camagru photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tw" title="Share on Twitter">𝕏</a>
                                <a href="https://pinterest.com/pin/create/button/?url=<?php echo e(rawurlencode($baseUrl . '/image/' . $image['id'])); ?>&media=<?php echo e(rawurlencode($baseUrl . '/uploads/' . $image['filename'])); ?>&description=<?php echo e(rawurlencode('Camagru photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-pin" title="Share on Pinterest">P</a>
                                <a href="https://api.whatsapp.com/send?text=<?php echo e(rawurlencode('Camagru photo by @' . $image['username'] . ' ' . $baseUrl . '/image/' . $image['id'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-wa" title="Share on WhatsApp">W</a>
                                <a href="https://t.me/share/url?url=<?php echo e(rawurlencode($baseUrl . '/image/' . $image['id'])); ?>&text=<?php echo e(rawurlencode('Camagru photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tg" title="Share on Telegram">T</a>
                            </div>
                        </div>
                        <div class="comments-section">
                            <div class="comments-list" id="comments-<?php echo $image['id']; ?>"></div>
                            <?php if (isAuthenticated()): ?>
                                <form class="comment-form" data-image-id="<?php echo $image['id']; ?>">
                                    <input type="text" name="comment" placeholder="Add a comment..." required>
                                    <button type="submit">Post</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (isset($infiniteScroll) && $infiniteScroll && $hasNextPage): ?>
            <div id="load-more-sentinel" class="load-more-sentinel"></div>
            <div id="load-more-status" class="load-more-status" style="display: none;"></div>
        <?php elseif (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination">
                <?php if ($hasPrevPage): ?>
                    <a href="/?page=<?php echo $currentPage - 1; ?>" class="button">Previous</a>
                <?php endif; ?>
                <span class="page-info">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>
                <?php if ($hasNextPage): ?>
                    <a href="/?page=<?php echo $currentPage + 1; ?>" class="button">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="/js/gallery.js"></script>
