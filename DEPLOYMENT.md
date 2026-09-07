# Deployment Guide

This project is a Laravel 12 application for `phpkoronadalcms.com`. Your host must support PHP 8.2 or newer, Composer, MySQL/MariaDB, and the PHP extensions Laravel commonly needs: `pdo_mysql`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `zip`, and `gd`.

## Information To Get From Your Host

- Hosting type: cPanel/shared hosting, VPS, Hostinger, Namecheap, GoDaddy, etc.
- Domain name and whether SSL is already enabled.
- Server PHP version. Use PHP 8.2+.
- SSH access details, if available.
- Database host, port, database name, username, and password.
- Public web root setting. It should point to this project's `public` folder.
- Whether `storage:link` symlinks are allowed.
- If using TiDB Cloud or another remote database with SSL, ask where to upload the CA certificate and what full server path to use.

## Production `.env`

Copy `.env.production.example` to `.env` on the server, then fill in:

```env
APP_KEY=base64:...
APP_URL=https://phpkoronadalcms.com
DB_HOST=...
DB_PORT=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
```

Keep these production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_FORCE_HTTPS=true
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Your local `.env` currently uses this Windows-only SSL certificate path:

```env
MYSQL_ATTR_SSL_CA=C:/xampp/apache/bin/curl-ca-bundle.crt
```

That path will not work on Linux hosting. On the server, either remove `MYSQL_ATTR_SSL_CA` if your database does not need it, or upload the CA certificate and set the Linux path provided by your host.

## Build Locally Before Upload

Run these locally:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Upload the project files after `public/build` exists. Do not upload `.env` from your local machine unless you have changed it for production.

## Server Commands

From the project folder on the host:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you already migrated/seeded the same production database, do not run `php artisan db:seed --force` again until you confirm the seeders are safe for repeated production use.

## Folder Permissions

These folders must be writable by the web server:

```text
storage/
bootstrap/cache/
```

On cPanel this is usually fixed through File Manager permissions. On VPS/Linux it is commonly:

```bash
chmod -R 775 storage bootstrap/cache
```

## Domain Setup

The domain document root must be:

```text
public
```

For example, if the project is uploaded to:

```text
/home/account/cms
```

then the domain document root should be:

```text
/home/account/cms/public
```

Do not point the domain to the project root.

## Final Checks

- Visit `https://phpkoronadalcms.com`.
- Confirm it redirects to `/login`.
- Log in with the seeded admin account or a production admin account.
- Test dashboard, crime creation, map data, PDF export, evidence upload/download, password reset email, and backups.
- After confirming SSL works, keep `APP_URL` as `https://...`.

## Security Notes

- Never share `.env` screenshots publicly.
- Rotate the database password and Gmail app password if they were exposed.
- Use a unique production `APP_KEY`.
- Keep `APP_DEBUG=false` in production.
