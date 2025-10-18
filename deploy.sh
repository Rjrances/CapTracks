#!/bin/bash

# Generate application key
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Start the server
php artisan serve --host=0.0.0.0 --port=8001
