<?php $view = 'profile'; ?>

<div class="profile-container">
    <h2>Profile</h2>

    <?php if (isset($_SESSION['profile_error'])): ?>
        <div class="error"><?php echo e($_SESSION['profile_error']); unset($_SESSION['profile_error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['profile_success'])): ?>
        <div class="success"><?php echo e($_SESSION['profile_success']); unset($_SESSION['profile_success']); ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo e($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo e($user['email']); ?></p>
        </div>

        <form method="POST" action="/profile" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group profile-option">
                <label>
                    <input type="checkbox" name="email_notifications" value="1"
                           <?php echo !empty($user['email_notifications']) ? 'checked' : ''; ?>>
                    Email notifications for new comments
                </label>
            </div>
            <button type="submit" class="button">Save</button>
        </form>
    </div>

    <div class="profile-gallery">
        <h3>My Photos (<?php echo count($images); ?>)</h3>
        <?php if (empty($images)): ?>
            <p class="no-photos">No photos yet. <a href="/edit">Create your first photo</a>.</p>
        <?php else: ?>
            <div class="gallery-grid profile-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-card">
                        <div class="image-wrapper">
                            <a href="/?page=1">
                                <img src="/uploads/<?php echo e($image['filename']); ?>" alt="My photo">
                            </a>
                        </div>
                        <div class="image-info">
                            <span class="date"><?php echo date('M d, Y', strtotime($image['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
