# 🚀 Railway Deployment Guide for CapTrack

## Prerequisites
- Railway account (free tier available)
- GitHub repository with your CapTrack code
- Domain name (optional, Railway provides free subdomain)

## Step 1: Prepare Your Repository

### 1.1 Add Railway Configuration Files
- ✅ `railway.json` - Railway deployment configuration
- ✅ `Procfile` - Process definition for Railway

### 1.2 Environment Variables Setup
Create these environment variables in Railway dashboard:

#### Required Variables:
```
APP_NAME=CapTrack
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

## Step 2: Deploy to Railway

### 2.1 Connect Repository
1. Go to [Railway.app](https://railway.app)
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your CapTrack repository

### 2.2 Add Database
1. In Railway dashboard, click "New"
2. Select "Database" → "PostgreSQL"
3. Railway will automatically create database credentials

### 2.3 Configure Environment Variables
1. Go to your service settings
2. Add all required environment variables
3. Railway will automatically inject database credentials

### 2.4 Deploy
1. Railway will automatically build and deploy
2. Check the deployment logs for any errors
3. Your app will be available at `https://your-app-name.railway.app`

## Step 3: Post-Deployment Setup

### 3.1 Run Migrations
```bash
# In Railway dashboard, go to your service
# Click "Deploy" tab, then "View Logs"
# Run these commands in the console:

php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.2 Generate Application Key
```bash
php artisan key:generate --force
```

## Step 4: Custom Domain (Optional)

### 4.1 Add Custom Domain
1. In Railway dashboard, go to "Settings"
2. Click "Domains"
3. Add your custom domain
4. Update DNS records as instructed

### 4.2 Update Environment Variables
```
APP_URL=https://your-custom-domain.com
```

## Troubleshooting

### Common Issues:

#### 1. Database Connection Error
- Check if PostgreSQL service is running
- Verify database credentials in environment variables
- Ensure database exists

#### 2. Application Key Error
- Run `php artisan key:generate --force`
- Check if APP_KEY is set in environment variables

#### 3. Permission Errors
- Check file permissions in storage/ and bootstrap/cache/
- Run `php artisan storage:link` if needed

#### 4. Session Issues
- Ensure SESSION_DRIVER=database
- Check if sessions table exists
- Run migrations if needed

## Environment Variables Reference

### Required for Production:
```
APP_NAME=CapTrack
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
```

### Optional:
```
LOG_LEVEL=error
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=CapTrack
```

## Benefits of Railway Deployment

### For Your Defense:
- ✅ **Professional deployment** - Shows production readiness
- ✅ **Scalable infrastructure** - Can handle real users
- ✅ **Database sessions work perfectly** - Your choice was right!
- ✅ **Easy to demonstrate** - Panel can see live system
- ✅ **Cost-effective** - Free tier available

### Technical Benefits:
- ✅ **Automatic deployments** - Push to GitHub = deploy
- ✅ **Built-in database** - PostgreSQL included
- ✅ **Environment management** - Easy config changes
- ✅ **Monitoring** - Built-in logs and metrics
- ✅ **SSL certificates** - Automatic HTTPS

## Next Steps

1. **Test locally** with production-like settings
2. **Push to GitHub** with Railway config files
3. **Deploy to Railway** following this guide
4. **Test thoroughly** on live environment
5. **Prepare demo** for your defense

Your CapTrack system is now ready for professional deployment! 🎉
