#!/bin/bash

echo "🚀 Starting CapTrack on Railway..."

# Generate APP_KEY if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Clear and cache configuration
echo "⚡ Caching configuration..."
php artisan config:clear
php artisan config:cache

# Test database connection (optional, don't fail if it doesn't work)
echo "🔍 Testing database connection..."
php artisan migrate:status || echo "⚠️ Database connection failed, continuing..."

# Start the application
echo "🎯 Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=$PORT
