@echo off
echo Syncing HabitHub project to XAMPP htdocs...

:: Replace these paths with your actual paths
set SOURCE_DIR=C:\Users\Siddhu\OneDrive\Documents\HabitHub
set DEST_DIR=C:\xampp\htdocs\HabitHub

:: Create destination directory if it doesn't exist
if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

:: Sync the files using robocopy
robocopy "%SOURCE_DIR%" "%DEST_DIR%" /MIR /XD .git node_modules /XF .gitignore

echo.
if errorlevel 8 (
    echo Failed to sync files!
) else (
    echo Files synced successfully!
)

echo.
echo Source: %SOURCE_DIR%
echo Destination: %DEST_DIR%
echo.
pause
