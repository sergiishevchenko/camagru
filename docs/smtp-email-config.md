# SMTP Email Configuration

## Where It Is Used

- Config source: `.env`
- Email transport code: `backend/src/utils/email.php`

The app sends emails for:

- Account verification
- Password reset
- New comment notifications

## SMTP Environment Variables

Use these variables in `.env`:

- `SMTP_HOST` SMTP server hostname
- `SMTP_PORT` SMTP port
- `SMTP_USER` SMTP login username (optional for local)
- `SMTP_PASS` SMTP login password (optional for local)
- `SMTP_ENCRYPTION` one of `none`, `tls`, `ssl`
- `SMTP_FROM_EMAIL` sender address shown in emails
- `SMTP_FROM_NAME` sender display name

## Encryption Modes

### `SMTP_ENCRYPTION=none`

Plain SMTP connection, no TLS negotiation.

Recommended for local MailHog only.

### `SMTP_ENCRYPTION=tls`

Uses SMTP `STARTTLS` after `EHLO`, then re-runs `EHLO` in the encrypted channel.

Recommended for providers on port `587`.

### `SMTP_ENCRYPTION=ssl`

Starts with implicit TLS (`ssl://host:port`) from the first connection.

Typical for providers on port `465`.

## Local Development (MailHog)

Recommended `.env` values:

```env
SMTP_HOST=mailhog
SMTP_PORT=1025
SMTP_USER=
SMTP_PASS=
SMTP_ENCRYPTION=none
SMTP_FROM_EMAIL=noreply@camagru.local
SMTP_FROM_NAME=Camagru
```

MailHog Web UI:

- `http://localhost:8025`

## External SMTP Example (STARTTLS)

Example for a real SMTP provider:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@example.com
SMTP_PASS=your-app-password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=your-email@example.com
SMTP_FROM_NAME=Camagru
```

Notes:

- For Gmail, use an App Password (not your regular account password).
- Make sure your provider supports the selected mode/port pair.

## Built-In SMTP Client Features

Current implementation in `email.php` includes:

- Multi-line SMTP response parsing
- Optional authentication (`AUTH PLAIN`, fallback to `AUTH LOGIN`)
- `STARTTLS` support for `tls` mode
- SSL socket support for `ssl` mode
- Socket timeouts
- SMTP dot-stuffing for message body safety
- Error logging per SMTP step

## Troubleshooting

If email sending fails:

1. Check container status: `docker-compose ps`
2. Check web logs: `docker logs camagru_web`
3. Verify `.env` values match provider requirements
4. For local mode, confirm MailHog is running and reachable at `mailhog:1025`
5. For external mode, verify correct host/port/encryption combination

Common mistakes:

- `SMTP_HOST=smtp.gmail.com` with `SMTP_PORT=1025`
- Using `none` for providers that require TLS
- Wrong app password or disabled SMTP auth
