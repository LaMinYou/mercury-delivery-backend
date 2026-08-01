[supervisord]
nodaemon=true
user=root
logfile=/dev/null
logfile_maxbytes=0

# 1. Main Web Server Process (Port 8000)
[program:laravel-serve]
command=php /var/www/html/artisan serve --host=0.0.0.0 --port=8000
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
startretries=3

# 2. Reverb WebSocket Server Process (Port 8080)
[program:laravel-reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
startretries=3