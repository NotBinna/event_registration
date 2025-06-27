#!/bin/bash

# Split terminal and run backend
echo "Starting Backend..."
code -e "cd backend && nodemon app" &

# Split terminal and run frontend servers
echo "Starting Frontend..."
code -e "cd frontend && php artisan serve --host=0.0.0.0 --port=8000" &
code -e "cd frontend && npm run dev" &

echo "All services started!"