#!/bin/bash

# Configuración de manejo de errores
set -e  # Salir si cualquier comando falla

# Script de inicio para Laravel con Docker
echo "🚀 Iniciando aplicación Laravel..."
echo "=================================================="

# Arreglar permisos de storage y cache
echo "📁 Configurando permisos..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Generar clave de aplicación si no existe
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔐 Generando clave de aplicación..."
    php artisan key:generate --force
fi

echo "=================================================="

# Instalar/actualizar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
composer install --optimize-autoloader --no-dev

# Verificar autoloader de Composer
echo "⚡ Optimizando autoloader..."
composer dump-autoload --optimize

echo "=================================================="

# Instalar dependencias de Node.js
echo "🎨 Instalando dependencias de Node.js..."
npm install

# Compilar assets de frontend
echo "🔨 Compilando assets de frontend..."
npm run build

echo "=================================================="

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders para cargar datos de prueba
echo "🌱 Ejecutando seeders (datos de prueba y factories)..."
php artisan db:seed --force

echo "=================================================="

# Configurar caché de configuración para producción
echo "⚙️  Configurando caché de configuración..."
php artisan config:cache

# Limpiar y optimizar cache
echo "🧹 Optimizando aplicación..."
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Crear enlace simbólico si no existe
if [ ! -L public/storage ]; then
    echo "🔗 Creando enlace simbólico de storage..."
    php artisan storage:link
fi

echo "=================================================="

# Verificar que la aplicación esté lista
echo "✅ Verificando estado de la aplicación..."
php artisan about --only=environment

echo "=================================================="

# Iniciar el servidor
echo "🚀 Iniciando servidor Laravel en el puerto 8086..."
echo "📱 Accede a la aplicación en: http://localhost:8086"
echo "👤 Usuario admin: admin@example.com / password"
echo "=================================================="
php artisan serve --host=0.0.0.0 --port=8086
