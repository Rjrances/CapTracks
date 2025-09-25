# Complete Railway Environment Setup Guide

## Current Issue: 502 Bad Gateway
The application starts but fails to respond due to missing environment variables.

## Step 1: Set ALL Required Environment Variables

Go to Railway → Your Project → CapTracks Service → Variables tab

### Copy and paste these EXACT values:

```env
APP_NAME=CapTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://captracks-production.up.railway.app
LOG_CHANNEL=stack
LOG_LEVEL=error
DB_CONNECTION=mysql
DB_HOST=[Get from MySQL service variables]
DB_PORT=3306
DB_DATABASE=[Get from MySQL service variables]
DB_USERNAME=[Get from MySQL service variables]
DB_PASSWORD=[Get from MySQL service variables]
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
SESSION_LIFETIME=120
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME=CapTrack
```

## Step 2: Get MySQL Connection Details

1. Go to Railway → Your Project → MySQL Service
2. Click on MySQL service
3. Go to Variables tab
4. Copy these values:
   - `MYSQL_HOST` → Use as `DB_HOST`
   - `MYSQL_DATABASE` → Use as `DB_DATABASE`
   - `MYSQL_USER` → Use as `DB_USERNAME`
   - `MYSQL_PASSWORD` → Use as `DB_PASSWORD`

## Step 3: Deploy and Test

1. Push your changes to GitHub
2. Wait for Railway to redeploy
3. Check if the 502 error is resolved

## Step 4: Manual Database Setup (After App Starts)

Once the app starts successfully, run these commands in Railway terminal:

```bash
php artisan migrate --force
php artisan db:seed --force
```

## Step 5: Verify Success

- ✅ No more 502 errors
- ✅ Application loads properly
- ✅ Database tables created
- ✅ Sample data seeded

## Common Issues:

1. **Missing APP_KEY**: Fixed by `php artisan key:generate --force`
2. **Database connection failed**: Check MySQL variables
3. **Session driver error**: Set `SESSION_DRIVER=database`
4. **Cache issues**: Fixed by `php artisan config:cache`

## Emergency Fallback:

If still having issues, try this minimal start command:
```json
"startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT"
```

Then manually run:
```bash
php artisan key:generate --force
php artisan config:cache
php artisan migrate --force
php artisan db:seed --force
```
