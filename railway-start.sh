#!/bin/bash

echo "🚀 Starting CapTrack on Railway..."

# Clear and cache configuration (APP_KEY is already set in env vars)
echo "⚡ Caching configuration..."
php artisan config:clear
php artisan config:cache

# Test database connection (optional, don't fail if it doesn't work)
echo "🔍 Testing database connection..."
php artisan migrate:status || echo "⚠️ Database connection failed, continuing..."

# Start the application
echo "🎯 Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=$PORT
