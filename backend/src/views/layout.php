<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) : 'Camagru'; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/">Camagru</a></h1>
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
</body>
</html>
