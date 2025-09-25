# 🚀 Railway Environment Variables Setup

## 📋 **Required Environment Variables for Railway**

Copy these variables into your Railway project settings:

### **1. Application Settings**
```
APP_NAME=CapTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app
```

### **2. Database Settings (Railway will auto-inject these)**
```
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

### **3. Session & Cache Settings**
```
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
SESSION_LIFETIME=120
```

### **4. Logging Settings**
```
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### **5. Mail Settings (Optional)**
```
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME=CapTrack
```

## 🔧 **How to Set Environment Variables in Railway**

### **Step 1: Go to Your Project**
1. Open your Railway project dashboard
2. Click on your CapTrack service

### **Step 2: Add Variables**
1. Click on "Variables" tab
2. Click "New Variable"
3. Add each variable from the list above

### **Step 3: Database Variables**
Railway will automatically inject these when you add PostgreSQL:
- `DATABASE_URL` (Railway will parse this into individual variables)
- Or manually add: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

## 🎯 **Critical Variables for CapTrack**

### **Must Set Manually:**
```
APP_NAME=CapTrack
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

### **Will Be Auto-Injected by Railway:**
```
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

## 🚀 **Post-Deployment Commands**

After deployment, run these commands in Railway console:

### **1. Generate Application Key**
```bash
php artisan key:generate --force
```

### **2. Run Migrations**
```bash
php artisan migrate --force
```

### **3. Seed Database**
```bash
php artisan db:seed --force
```

### **4. Cache Configuration**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔍 **Environment Variables Reference**

### **Production Optimized:**
- `APP_ENV=production` - Production environment
- `APP_DEBUG=false` - Disable debug mode
- `LOG_LEVEL=error` - Only log errors
- `SESSION_DRIVER=database` - Use database sessions (your choice!)
- `QUEUE_CONNECTION=sync` - Process jobs immediately

### **Database Sessions Benefits:**
- ✅ **Scalable** - Works with multiple servers
- ✅ **Reliable** - Won't lose sessions on restart
- ✅ **Secure** - Session data in database
- ✅ **Professional** - Industry standard

## 🎤 **For Your Defense**

**"How did you configure the production environment?"**

**Answer**: "We configured Railway with production-optimized environment variables. We set `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_DRIVER=database` to ensure our system runs securely and efficiently in production. Railway automatically provides PostgreSQL database credentials, and we use database sessions for scalability and reliability."

## ✅ **Quick Setup Checklist**

- [ ] Set `APP_NAME=CapTrack`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `SESSION_DRIVER=database`
- [ ] Set `QUEUE_CONNECTION=sync`
- [ ] Add PostgreSQL database service
- [ ] Run `php artisan key:generate --force`
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan db:seed --force`

Your CapTrack system is now ready for professional Railway deployment! 🚀
