@echo off
title Event Registration Project

:: Start Backend (new window)
start cmd /k "cd backend && nodemon app"

:: Start Frontend (new window)
start cmd /k "cd frontend && php artisan serve --port=8000"
start cmd /k "cd frontend && npm run dev"

echo All services started!