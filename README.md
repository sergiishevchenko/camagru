# Camagru

Web application for photo editing using webcam and overlaying predefined images.

## Requirements

- Docker and Docker Compose
- Git

## Installation and Setup

1. **Clone the repository** (if not already done):
```bash
git clone https://github.com/sergiishevchenko/camagru.git
cd camagru
```

2. **Create `.env` file** with the following content:
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

Or create the file manually with these values.

3. **Start the containers**:
```bash
docker-compose up -d
```

4. **Check container status**:
```bash
docker-compose ps
```

5. **Open in browser**:
- Application: http://localhost:8080
- MailHog (for viewing emails): http://localhost:8025

## Project Structure

```
camagru/
├── backend/              # Server-side (PHP)
│   ├── src/
│   │   ├── config/       # Configuration
│   │   ├── controllers/  # Controllers
│   │   ├── models/       # Data models
│   │   ├── views/        # Views
│   │   ├── middleware/   # Middleware
│   │   └── utils/        # Utilities
│   └── public/           # Public directory
├── frontend/             # Client-side
│   ├── css/              # Styles
│   ├── js/               # JavaScript
│   └── images/           # Images
├── database/             # SQL schemas
└── docker-compose.yml    # Docker configuration
```

## Database

The database is automatically created on first startup of the `db` container. The schema is located in `database/schema.sql`.

### Database Connection

- Host: localhost
- Port: 3306
- Database: camagru_db
- User: camagru_user
- Password: camagru_pass

## Email (Development)

MailHog is used for development. All sent emails can be viewed at http://localhost:8025

## Stopping

```bash
docker-compose down
```

To remove all data (including database):
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
```

### Restart

After code changes, restart may be required:
```bash
docker-compose restart web
```
