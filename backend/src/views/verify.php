<?php $view = 'verify'; ?>

<div class="auth-form">
    <h2>Email Verification</h2>
    
    <?php if (isset($success) && $success): ?>
        <div class="success">
            Your email has been verified successfully! <a href="/login">Login here</a>
        </div>
    <?php else: ?>
        <div class="error">
            Verification failed. The link may be invalid or expired. 
            <a href="/register">Register again</a> or <a href="/login">Login</a>
        </div>
    <?php endif; ?>
</div>
