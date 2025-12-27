<?php $view = 'index'; ?>

<div class="welcome">
    <h2>Welcome to Camagru!</h2>
    <p>Create amazing photos with your webcam and overlay effects.</p>
    
    <?php if (isAuthenticated()): ?>
        <p><a href="/edit" class="button">Start Creating</a></p>
    <?php else: ?>
        <p><a href="/register" class="button">Get Started</a> or <a href="/login">Login</a></p>
    <?php endif; ?>
</div>
