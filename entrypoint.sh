#!/bin/sh

set -e

echo "🚀 Starting deployment script..."

# Clear and cache
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache

# Run Migrations
echo "📦 Running database migrations..."
php artisan migrate --force

# Storage Link
php artisan storage:link --force || true

# Start Reverb on Port 8080
echo "⚡ Starting Reverb Server..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Start Web Server on Port 8000
echo "🌐 Starting Main Server..."
exec php artisan serve --host=0.0.0.0 --port=8000