<?php $view = 'image'; ?>

<div class="image-view-container">
    <div class="image-view-card">
        <img src="/uploads/<?php echo e($image['filename']); ?>" alt="Photo by <?php echo e($image['username']); ?>">
        <div class="image-view-info">
            <p class="username">@<?php echo e($image['username']); ?></p>
            <p class="date"><?php echo date('M d, Y', strtotime($image['created_at'])); ?></p>
            <p><a href="/" class="button">Back to Gallery</a></p>
        </div>
    </div>
</div>
