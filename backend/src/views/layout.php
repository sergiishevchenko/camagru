<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo isset($title) ? e($title) : 'Camagru'; ?></title>
    <?php if (!empty($metaOg)): ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo e($metaOg['title'] ?? 'Camagru'); ?>">
    <meta property="og:image" content="<?php echo e($metaOg['image'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo e($metaOg['url'] ?? ''); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($metaOg['title'] ?? 'Camagru'); ?>">
    <meta name="twitter:image" content="<?php echo e($metaOg['image'] ?? ''); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/">Camagru</a></h1>
            <button type="button" id="nav-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav>
                <?php if (isAuthenticated()): ?>
                    <a href="/edit">Edit</a>
                    <a href="/profile">Profile</a>
                    <a href="/logout">Logout</a>
                <?php else: ?>
                    <a href="/login">Login</a>
                    <a href="/register">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php
        if (isset($view)) {
            $viewFile = __DIR__ . '/' . $view . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<p>View not found</p>';
            }
        }
        ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Camagru. All rights reserved.</p>
        </div>
    </footer>
    <script>
    (function() {
        var toggle = document.getElementById('nav-toggle');
        var nav = document.querySelector('header nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('is-open');
            });
        }
    })();
    </script>
</body>
</html>
