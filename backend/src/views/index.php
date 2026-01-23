<?php $view = 'index'; ?>

<div class="gallery-container">
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
                        <img src="/uploads/<?php echo e($image['filename']); ?>" alt="Photo by <?php echo e($image['username']); ?>">
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
        
        <?php if ($totalPages > 1): ?>
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
