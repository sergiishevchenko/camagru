<?php $view = 'profile'; ?>

<div class="profile-container">
    <h2>Profile</h2>

    <?php if (isset($_SESSION['profile_error'])): ?>
        <div class="error"><?php echo e($_SESSION['profile_error']); unset($_SESSION['profile_error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['profile_errors'])): ?>
        <div class="error">
            <ul>
                <?php foreach ($_SESSION['profile_errors'] as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['profile_errors']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['profile_success'])): ?>
        <div class="success"><?php echo e($_SESSION['profile_success']); unset($_SESSION['profile_success']); ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <form method="POST" action="/profile" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?php echo e($user['username']); ?>"
                       autocomplete="username" pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                       title="3-50 characters, letters, numbers, and underscores only">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       autocomplete="email" value="<?php echo e($user['email']); ?>">
            </div>

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password"
                       autocomplete="current-password" placeholder="Required only when changing password">
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password"
                       autocomplete="new-password" minlength="8"
                       placeholder="Leave empty to keep current"
                       title="At least 8 characters with letters and numbers">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       autocomplete="new-password" placeholder="Repeat new password">
            </div>

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
