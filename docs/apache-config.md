# Apache Config

## Purpose

The file defines the Apache VirtualHost used by the PHP container.

Main goals:

- Serve the app from `public` only, not from the project root
- Enable `.htaccess` rewrite rules for clean routing
- Keep standard Apache access and error logs enabled

## File Location

- Source file in repo: `backend/apache-config.conf`
- Active destination in container: `/etc/apache2/sites-available/000-default.conf`

The copy is done in `backend/Dockerfile`:

- `COPY apache-config.conf /etc/apache2/sites-available/000-default.conf`

## What It Configures

### 1) DocumentRoot

`DocumentRoot /var/www/html/public`

Why this matters:

- Prevents direct web access to internal source code and config files
- Exposes only the public entrypoint (`public/index.php`) and public assets

### 2) Directory Permissions and Overrides

```apache
<Directory /var/www/html/public>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Why this matters:

- `AllowOverride All` lets Apache apply `.htaccess` rules
- Routing/rewrites depend on this in many PHP front-controller setups

### 3) Logging

- `ErrorLog ${APACHE_LOG_DIR}/error.log`
- `CustomLog ${APACHE_LOG_DIR}/access.log combined`

Why this matters:

- Keeps request and error diagnostics available for debugging

## Related Dockerfile Settings

In `backend/Dockerfile`, Apache is prepared with:

- `a2enmod rewrite` to enable rewrite module
- `sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf`
- Copy of this VirtualHost file to replace default site config

## What Can Break Without This File

If this file is removed or not copied:

- Apache may serve the wrong root directory
- Clean URLs and route rewrites may stop working
- Internal project files could become web-accessible depending on defaults

## Quick Validation

After `docker-compose up -d`, check:

1. Open `http://localhost:8080` and confirm app loads
2. Open a routed URL like `http://localhost:8080/image/1`
3. Confirm no directory listing of non-public project files is exposed
