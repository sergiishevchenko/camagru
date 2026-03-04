<?php $view = 'image'; ?>

<div class="image-view-container">
    <div class="image-view-card">
        <div class="image-view-photo">
            <?php if (!empty($isOwner)): ?>
                <button class="image-view-delete" data-image-id="<?php echo (int)$image['id']; ?>" aria-label="Delete photo">×</button>
            <?php endif; ?>
            <img src="/uploads/<?php echo e($image['filename']); ?>" alt="Photo by <?php echo e($image['username']); ?>">
        </div>
        <div class="image-view-details">
            <div class="image-view-header">
                <div class="image-view-avatar"><?php echo strtoupper(substr($image['username'], 0, 1)); ?></div>
                <div>
                    <p class="image-view-username">@<?php echo e($image['username']); ?></p>
                    <p class="image-view-date"><?php echo date('F j, Y \a\t g:i A', strtotime($image['created_at'])); ?></p>
                </div>
            </div>

            <div class="image-view-stats">
                <span class="image-view-stat">
                    <span class="image-view-stat-icon">♥</span>
                    <?php echo (int)($likeCount ?? 0); ?> like<?php echo ($likeCount ?? 0) != 1 ? 's' : ''; ?>
                </span>
                <span class="image-view-stat">
                    <span class="image-view-stat-icon">💬</span>
                    <?php echo count($comments ?? []); ?> comment<?php echo count($comments ?? []) != 1 ? 's' : ''; ?>
                </span>
            </div>

            <?php if (!empty($comments)): ?>
            <div class="image-view-comments">
                <?php foreach ($comments as $c): ?>
                <div class="image-view-comment">
                    <span class="image-view-comment-user">@<?php echo e($c['username']); ?></span>
                    <span class="image-view-comment-text"><?php echo e($c['content']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="image-view-share">
                <span class="image-view-share-label">Share</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e(rawurlencode($pageUrl)); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-fb" title="Facebook">f</a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo e(rawurlencode($pageUrl)); ?>&text=<?php echo e(rawurlencode('Photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tw" title="Twitter">𝕏</a>
                <a href="https://pinterest.com/pin/create/button/?url=<?php echo e(rawurlencode($pageUrl)); ?>&media=<?php echo e(rawurlencode($imageUrl)); ?>&description=<?php echo e(rawurlencode('Photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-pin" title="Pinterest">P</a>
                <a href="https://api.whatsapp.com/send?text=<?php echo e(rawurlencode('Photo by @' . $image['username'] . ' ' . $pageUrl)); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-wa" title="WhatsApp">W</a>
                <a href="https://t.me/share/url?url=<?php echo e(rawurlencode($pageUrl)); ?>&text=<?php echo e(rawurlencode('Photo by @' . $image['username'])); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-tg" title="Telegram">T</a>
            </div>

            <a href="/" class="button image-view-back">← Back to Gallery</a>
        </div>
    </div>
</div>

<?php if (!empty($isOwner)): ?>
<script>
(function() {
    function getCSRFToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;
        var input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    var btn = document.querySelector('.image-view-delete');
    if (!btn) return;

    btn.addEventListener('click', function(e) {
        e.preventDefault();
        window.confirmModal('Delete this photo?').then(function(ok) {
            if (!ok) return;
            var imageId = btn.dataset.imageId;
            var token = getCSRFToken();
            if (!token) {
                window.alertModal('CSRF token not found');
                return;
            }
            btn.disabled = true;
            fetch('/image/' + imageId, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: token })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.href = '/';
                } else {
                    window.alertModal(data.error || 'Failed to delete image');
                    btn.disabled = false;
                }
            })
            .catch(function() {
                window.alertModal('An error occurred');
                btn.disabled = false;
            });
        });
    });
})();
</script>
<?php endif; ?>
