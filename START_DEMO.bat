@echo off
cd /d "%~dp0"
echo Atelo: http://127.0.0.1:8080/login
echo Admin: admin@cadence.test / Admin@12345
start "" http://127.0.0.1:8080/login
php artisan serve --port=8080

