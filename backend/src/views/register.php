<?php $view = 'register'; ?>

<div class="auth-form">
    <h2>Register</h2>
    
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

    <?php if (isset($success)): ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="/register">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus
                   autocomplete="username" pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                   title="3-50 characters, letters, numbers, and underscores only">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required 
                   autocomplete="new-password" minlength="8" 
                   title="At least 8 characters with letters and numbers">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>

        <button type="submit">Register</button>
    </form>

    <p class="auth-link">
        <a href="/login">Already have an account? Login</a>
    </p>
</div>
<script src="/js/auth.js"></script>
