<?php $view = 'login'; ?>

<div class="auth-form">
    <h2>Login</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">Login</button>
    </form>

    <p class="auth-link">
        <a href="/forgot-password">Forgot password?</a> | 
        <a href="/register">Don't have an account? Register</a>
    </p>
</div>
<script src="/js/auth.js"></script>
