#!/bin/bash

# Exit on any error
set -e

echo "Starting deployment process..."

# Run migrations and seeding
echo "Running fresh migrations and seeding..."
php artisan migrate:fresh --seed

# Cache optimizations
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the server
echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=8001
