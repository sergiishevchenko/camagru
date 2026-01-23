# Camagru

Web application for photo editing using webcam and overlaying predefined images with alpha channel. Users can take photos, apply overlay effects, and share results in a public gallery.

## Features

- ✅ User authentication (registration, login, email verification)
- ✅ Password recovery via email
- ✅ Webcam photo capture
- ✅ Image upload
- ✅ Overlay effects on images
- ✅ Public gallery with pagination
- ✅ Like system
- ✅ Comment system
- ✅ Email notifications for comments
- ✅ Image deletion (own images only)
- ✅ CSRF protection
- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (output escaping)

## Requirements

- Docker and Docker Compose
- Git
- Modern web browser with camera support (Chrome, Firefox, Safari)

## Installation and Setup

1. **Clone the repository** (if not already done):
```bash
git clone https://github.com/sergiishevchenko/camagru.git
cd camagru
```

2. **Create `.env` file** in the project root with the following content:
```bash
# Database Configuration
DB_HOST=db
DB_NAME=camagru_db
DB_USER=camagru_user
DB_PASS=camagru_pass

# Application Configuration
APP_URL=http://localhost:8080
APP_ENV=development

# Security
SECRET_KEY=your-secret-key-here-change-in-production
SESSION_LIFETIME=3600

# Email Configuration (MailHog for development)
SMTP_HOST=mailhog
SMTP_PORT=1025
SMTP_USER=
SMTP_PASS=
SMTP_FROM_EMAIL=noreply@camagru.local
SMTP_FROM_NAME=Camagru

# File Upload
UPLOAD_DIR=public/uploads
MAX_FILE_SIZE=5242880
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif
```

3. **Add overlay images** (optional):
   - Place PNG images with alpha channel in `frontend/images/overlays/`
   - Name them without extension (e.g., `frame1.png` → use as `frame1`)
   - Images will be automatically detected and available for selection

4. **Start the containers**:
```bash
docker-compose up -d
```

5. **Check container status**:
```bash
docker-compose ps
```

6. **Open in browser**:
   - Application: http://localhost:8080
   - MailHog (for viewing emails): http://localhost:8025

## Usage

### Registration
1. Go to http://localhost:8080/register
2. Fill in username, email, and password
3. Check your email (view in MailHog at http://localhost:8025)
4. Click the verification link to activate your account

### Creating Photos
1. Login to your account
2. Go to the Edit page (`/edit`)
3. Select an overlay from the available options
4. Choose one of the following:
   - **Webcam**: Click "Start Camera", allow camera access, then click "Capture"
   - **Upload**: Click "Choose File" and select an image from your device
5. Click "Use This Image" to apply the overlay
6. Your photo will be saved and appear in the gallery

### Gallery
- View all public photos on the main page
- Like photos by clicking the heart icon
- Add comments below each photo
- Delete your own photos using the × button
- Navigate through pages using pagination controls

## Project Structure

```
camagru/
├── backend/                                       # Server-side (PHP)
│   ├── src/
│   │   ├── config/                                # Configuration files
│   │   │   ├── bootstrap.php
│   │   │   ├── database.php
│   │   │   ├── env.php
│   │   │   └── router.php
│   │   ├── controllers/                           # Controllers
│   │   │   ├── AuthController.php
│   │   │   ├── ImageController.php
│   │   │   ├── GalleryController.php
│   │   │   ├── LikeController.php
│   │   │   └── CommentController.php
│   │   ├── models/                                # Data models
│   │   │   ├── User.php
│   │   │   ├── Image.php
│   │   │   ├── Like.php
│   │   │   └── Comment.php
│   │   ├── views/                                 # Views (PHP templates)
│   │   │   ├── layout.php
│   │   │   ├── index.php
│   │   │   ├── login.php
│   │   │   ├── register.php
│   │   │   ├── edit.php
│   │   │   └── ...
│   │   ├── middleware/                            # Middleware
│   │   │   └── AuthMiddleware.php
│   │   └── utils/                                 # Utilities
│   │       ├── functions.php
│   │       ├── email.php
│   │       └── image_processor.php
│   ├── public/                                    # Public directory (web root)
│   │   ├── index.php                              # Entry point
│   │   ├── uploads/                               # User uploaded images
│   │   └── .htaccess                              # Apache rewrite rules
│   ├── Dockerfile
│   └── apache-config.conf
├── frontend/                                      # Client-side
│   ├── css/                                       # Stylesheets
│   │   └── style.css
│   ├── js/                                        # JavaScript files
│   │   ├── edit.js                                # Webcam and image upload
│   │   └── gallery.js                             # Gallery interactions (likes, comments)
│   └── images/                                    # Static images
│       └── overlays/                              # Overlay images (PNG with alpha)
├── database/                                      # SQL schemas
│   └── schema.sql
├── docker-compose.yml                             # Docker configuration
└── README.md                                      # This file
```

## API Endpoints

### Authentication
- `GET /login` - Login page
- `POST /login` - Process login
- `GET /register` - Registration page
- `POST /register` - Process registration
- `GET /verify/{token}` - Verify email
- `GET /forgot-password` - Password recovery page
- `POST /forgot-password` - Send recovery email
- `GET /reset-password/{token}` - Reset password page
- `POST /reset-password` - Process password reset
- `GET /logout` - Logout

### Images
- `GET /edit` - Photo editing page (requires auth)
- `POST /edit/capture` - Save webcam capture (requires auth)
- `POST /edit/upload` - Upload and process image (requires auth)
- `DELETE /image/{id}` - Delete image (requires auth, owner only)

### Gallery
- `GET /` - Gallery with pagination
- `GET /?page={n}` - Gallery page n

### Interactions
- `POST /like/{imageId}` - Toggle like (requires auth)
- `POST /comment/{imageId}` - Add comment (requires auth)
- `GET /comment/{imageId}` - Get comments for image

## Database

The database is automatically created on first startup of the `db` container. The schema is located in `database/schema.sql`.

### Database Connection

- Host: localhost (from host machine)
- Port: 3306
- Database: camagru_db
- User: camagru_user
- Password: camagru_pass

### Tables

- **users**: User accounts with email verification
- **images**: User-uploaded photos with overlay info
- **likes**: User likes on images
- **comments**: User comments on images

## Email (Development)

MailHog is used for development. All sent emails can be viewed at http://localhost:8025

Email types:
- Email verification (registration)
- Password reset
- Comment notifications

## Security Features

- **SQL Injection Protection**: All queries use prepared statements
- **XSS Protection**: All user output is escaped using `htmlspecialchars()`
- **CSRF Protection**: CSRF tokens for all forms and AJAX requests
- **Password Security**: Bcrypt hashing with complexity requirements
- **File Upload Security**: MIME type validation, size limits, file renaming
- **Authentication**: Session-based with middleware protection
- **Authorization**: Users can only delete their own images

## Stopping

Stop containers:
```bash
docker-compose down
```

Stop and remove all data (including database):
```bash
docker-compose down -v
```

## Development

### Logs

View logs for all services:
```bash
docker-compose logs -f
```

Logs for specific service:
```bash
docker-compose logs -f web
docker-compose logs -f db
docker-compose logs -f mailhog
```

### Restart

After code changes, restart may be required:
```bash
docker-compose restart web
```

### Adding Overlay Images

1. Create PNG images with alpha channel (transparency)
2. Place them in `frontend/images/overlays/`
3. Name them descriptively (e.g., `frame1.png`, `mask.png`)
4. Restart the web container or refresh the edit page
5. Overlays will appear in the selection list

### Code Structure

- **MVC Pattern**: Controllers handle requests, Models handle data, Views render output
- **Router**: Custom router with middleware support
- **Middleware**: Authentication checks before protected routes
- **Utilities**: Reusable functions for common tasks

## Browser Compatibility

- Chrome 46+
- Firefox 41+
- Safari (latest)
- Edge (latest)

**Note**: Webcam functionality requires HTTPS in production or localhost for development.

## License

This project is part of the 42 School curriculum.
