#!/bin/sh

# Fail immediately if any command fails
set -e

echo "🚀 Starting deployment script..."

# Clear and cache configurations
echo "🧹 Optimizing Laravel configuration..."
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache

# Run Database Migrations
echo "📦 Running database migrations..."
php artisan migrate --seed --force

# Create storage link if it doesn't exist
echo "🔗 Creating storage link..."
php artisan storage:link --force || true

# Start Reverb Server in the background
echo "⚡ Starting Reverb Server..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Start the Main Web Server (Laravel Development Server or Apache/Nginx/Octane)
echo "🌐 Starting Main Server..."
php artisan serve --host=0.0.0.0 --port=8000