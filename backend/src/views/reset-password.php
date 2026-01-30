<?php $view = 'reset-password'; ?>

<div class="auth-form">
    <h2>Reset Password</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="success">
            Password has been reset successfully! <a href="/login">Login here</a>
        </div>
    <?php else: ?>
        <form method="POST" action="/reset-password">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="token" value="<?php echo e($token ?? ''); ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required 
                       minlength="8" 
                       title="At least 8 characters with letters and numbers">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>

    <p class="auth-link">
        <a href="/login">Back to Login</a>
    </p>
</div>
<script src="/js/auth.js"></script>
