@echo off
echo.
echo Starting Laravel development server with CORS support...
echo Storage files will be served with proper CORS headers
echo Server will be available at: http://127.0.0.1:8000
echo.
echo Press Ctrl+C to stop the server
echo.

cd /d %~dp0
php -S 127.0.0.1:8000 -t public server.php
