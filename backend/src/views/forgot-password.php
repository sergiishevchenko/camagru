<?php $view = 'forgot-password'; ?>

<div class="auth-form">
    <h2>Forgot Password</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="/forgot-password">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus autocomplete="email">
        </div>

        <button type="submit">Send Reset Link</button>
    </form>

    <p class="auth-link">
        <a href="/login">Back to Login</a>
    </p>
</div>
<script src="/js/auth.js"></script>
