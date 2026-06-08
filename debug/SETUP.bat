@echo off
echo IUC Voting System - Quick Setup
echo.

echo Step 1: Starting Blockchain Node...
start "Blockchain Node" cmd /k "cd python && python blockchain_node.py"

echo Step 2: Starting PHP Server...
start "PHP Server" cmd /k "php -S localhost:8000"

echo.
echo System Setup Complete!
echo.
echo Access URLs:
echo - Main System: http://localhost:8000/
echo - Blockchain Node: http://localhost:5000/
echo.
echo Admin Login:
echo - Email: admin@iuc.edu
echo - Password: password
echo.
echo Press any key to exit...
pause
