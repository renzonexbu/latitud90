@echo off
cd /d "C:\laragon\www\latitud90"
"C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" artisan schedule:run
