<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <link rel="icon" type="image/png" href="/favicon.png">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css?v=<?php echo file_exists(__DIR__ . '/../../public/css/style.css') ? filemtime(__DIR__ . '/../../public/css/style.css') : time(); ?>">
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
                <a href="/">Gallery</a>
                <?php if (isAuthenticated()): ?>
                    <a href="/edit">+ New Photo</a>
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
            <span class="footer-logo">Camagru</span>
            <p>&copy; <?php echo date('Y'); ?> &mdash; A 42 School Project</p>
        </div>
    </footer>
    <div id="confirm-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <p id="confirm-modal-text">Are you sure?</p>
            <div class="modal-actions">
                <button id="confirm-modal-cancel" class="button modal-btn-cancel" type="button">Cancel</button>
                <button id="confirm-modal-ok" class="button modal-btn-ok" type="button">Delete</button>
            </div>
        </div>
    </div>
    <div id="alert-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <p id="alert-modal-text"></p>
            <div class="modal-actions">
                <button id="alert-modal-ok" class="button modal-btn-ok" type="button">OK</button>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var toggle = document.getElementById('nav-toggle');
        var nav = document.querySelector('header nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                nav.classList.toggle('is-open');
                toggle.classList.toggle('is-open');
            });
            nav.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() {
                    nav.classList.remove('is-open');
                    toggle.classList.remove('is-open');
                });
            });
        }
    })();
    window.confirmModal = function(text) {
        return new Promise(function(resolve) {
            var overlay = document.getElementById('confirm-modal');
            var msg = document.getElementById('confirm-modal-text');
            var okBtn = document.getElementById('confirm-modal-ok');
            var cancelBtn = document.getElementById('confirm-modal-cancel');
            msg.textContent = text || 'Are you sure?';
            overlay.style.display = 'flex';
            function cleanup() {
                overlay.style.display = 'none';
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                overlay.removeEventListener('click', onOverlay);
            }
            function onOk() { cleanup(); resolve(true); }
            function onCancel() { cleanup(); resolve(false); }
            function onOverlay(e) { if (e.target === overlay) { cleanup(); resolve(false); } }
            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            overlay.addEventListener('click', onOverlay);
        });
    };
    window.alertModal = function(text) {
        return new Promise(function(resolve) {
            var overlay = document.getElementById('alert-modal');
            var msg = document.getElementById('alert-modal-text');
            var okBtn = document.getElementById('alert-modal-ok');
            msg.textContent = text || 'Something went wrong';
            overlay.style.display = 'flex';
            function cleanup() {
                overlay.style.display = 'none';
                okBtn.removeEventListener('click', onOk);
                overlay.removeEventListener('click', onOverlay);
            }
            function onOk() { cleanup(); resolve(); }
            function onOverlay(e) { if (e.target === overlay) { cleanup(); resolve(); } }
            okBtn.addEventListener('click', onOk);
            overlay.addEventListener('click', onOverlay);
        });
    };
    </script>
</body>
</html>
