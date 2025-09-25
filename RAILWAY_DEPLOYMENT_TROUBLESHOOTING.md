# Railway Deployment Troubleshooting Guide

## Current Issue: 502 Bad Gateway

The application is failing to start due to database/migration issues. Here's how to fix it:

## Step 1: Deploy with Minimal Configuration

The current `railway.json` is set to just start the application:
```json
{
  "startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT"
}
```

## Step 2: Set Environment Variables in Railway

Go to your Railway project → CapTracks service → Variables tab and set:

### Required Variables:
```
APP_NAME=CapTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://captracks-production.up.railway.app
DB_CONNECTION=mysql
DB_HOST=[Your MySQL host from Railway MySQL service]
DB_PORT=3306
DB_DATABASE=[Your database name]
DB_USERNAME=[Your MySQL username]
DB_PASSWORD=[Your MySQL password]
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
LOG_CHANNEL=stack
LOG_LEVEL=error
```

## Step 3: Manual Database Setup

After the application starts successfully, run these commands in Railway's terminal:

1. **Generate Application Key:**
   ```bash
   php artisan key:generate --force
   ```

2. **Run Migrations:**
   ```bash
   php artisan migrate --force
   ```

3. **Seed Database:**
   ```bash
   php artisan db:seed --force
   ```

4. **Cache Configuration:**
   ```bash
   php artisan config:cache
   ```

## Step 4: Restart Application

After completing the database setup, restart your Railway service.

## Alternative: Use Railway CLI

If you have Railway CLI installed:

```bash
# Connect to your project
railway login
railway link

# Run commands directly
railway run php artisan key:generate --force
railway run php artisan migrate --force
railway run php artisan db:seed --force
railway run php artisan config:cache
```

## Common Issues:

1. **Database Connection Failed**: Check MySQL service variables
2. **APP_KEY Missing**: Run `php artisan key:generate --force`
3. **Migration Errors**: Check database permissions
4. **Seeding Errors**: Our seeders now use `firstOrCreate()` to prevent duplicates

## Success Indicators:

- ✅ Application responds with 200 status
- ✅ Database tables created
- ✅ Sample data seeded
- ✅ No 502 errors in logs
