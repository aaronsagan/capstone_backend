# Laravel Development Server with CORS Support
# This script starts the server using server.php as a router to handle CORS for storage files

Write-Host "🚀 Starting Laravel development server with CORS support..." -ForegroundColor Cyan
Write-Host ""
Write-Host "Storage files will be served with proper CORS headers" -ForegroundColor Green
Write-Host "Server will be available at: http://127.0.0.1:8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Gray
Write-Host ""

# Change to backend directory
Set-Location $PSScriptRoot

# Start PHP built-in server with custom router
php -S 127.0.0.1:8000 -t public server.php
