# Dockerfile para Laravel + Node.js + Composer
FROM node:20-bullseye as nodebuild

# Instala PHP 8.2, Composer y extensiones necesarias (como root)
RUN apt-get update \
    && apt-get install -y --no-install-recommends lsb-release apt-transport-https ca-certificates \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list \
    && curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor | tee /etc/apt/trusted.gpg.d/php.gpg > /dev/null \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-tokenizer php8.2-bcmath php8.2-redis unzip git curl \
    && update-alternatives --set php /usr/bin/php8.2 \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Crea el directorio de la aplicación y establece 'node' como propietario
RUN mkdir -p /app && chown -R node:node /app

# Cambia al usuario 'node' para las operaciones de npm
USER node
WORKDIR /app

# Copia los archivos package.json y package-lock.json (si existe) como usuario 'node'
COPY --chown=node:node package*.json ./

# Instala las dependencias de Node.js como usuario 'node'
# Esto crea /app/node_modules propiedad de 'node'
RUN npm install

# Vuelve al usuario 'root' para copiar el resto de los archivos y ejecutar composer
USER root
WORKDIR /app

# Copia el resto del código del proyecto.
# ¡ASEGÚRATE DE TENER UN .dockerignore QUE EXCLUYA node_modules LOCAL!
COPY . .

# Instala las dependencias de PHP como 'root'
RUN if [ -f composer.lock ]; then rm composer.lock; fi \
    && composer install --no-dev --optimize-autoloader \
    && composer dump-autoload --optimize

# Crea el archivo .env automáticamente si no existe
RUN if [ ! -f .env ]; then \
        echo "APP_NAME=Laravel" > .env && \
        echo "APP_ENV=local" >> .env && \
        echo "APP_DEBUG=true" >> .env && \
        echo "APP_URL=http://localhost:8086" >> .env && \
        echo "" >> .env && \
        echo "LOG_CHANNEL=stack" >> .env && \
        echo "LOG_DEPRECATIONS_CHANNEL=null" >> .env && \
        echo "LOG_LEVEL=debug" >> .env && \
        echo "" >> .env && \
        echo "DB_CONNECTION=mysql" >> .env && \
        echo "DB_HOST=db" >> .env && \
        echo "DB_PORT=3306" >> .env && \
        echo "DB_DATABASE=laravel" >> .env && \
        echo "DB_USERNAME=laravel" >> .env && \
        echo "DB_PASSWORD=secret" >> .env && \
        echo "" >> .env && \
        echo "BROADCAST_DRIVER=log" >> .env && \
        echo "CACHE_DRIVER=file" >> .env && \
        echo "FILESYSTEM_DISK=local" >> .env && \
        echo "QUEUE_CONNECTION=sync" >> .env && \
        echo "SESSION_DRIVER=file" >> .env && \
        echo "SESSION_LIFETIME=120" >> .env && \
        echo "" >> .env && \
        echo "MEMCACHED_HOST=127.0.0.1" >> .env && \
        echo "" >> .env && \
        echo "REDIS_HOST=127.0.0.1" >> .env && \
        echo "REDIS_PASSWORD=null" >> .env && \
        echo "REDIS_PORT=6379" >> .env && \
        echo "" >> .env && \
        echo "MAIL_MAILER=smtp" >> .env && \
        echo "MAIL_HOST=mailpit" >> .env && \
        echo "MAIL_PORT=1025" >> .env && \
        echo "MAIL_USERNAME=null" >> .env && \
        echo "MAIL_PASSWORD=null" >> .env && \
        echo "MAIL_ENCRYPTION=null" >> .env && \
        echo "MAIL_FROM_ADDRESS=\"hello@example.com\"" >> .env && \
        echo "MAIL_FROM_NAME=\"\${APP_NAME}\"" >> .env && \
        echo "" >> .env && \
        echo "AWS_ACCESS_KEY_ID=" >> .env && \
        echo "AWS_SECRET_ACCESS_KEY=" >> .env && \
        echo "AWS_DEFAULT_REGION=us-east-1" >> .env && \
        echo "AWS_BUCKET=" >> .env && \
        echo "AWS_USE_PATH_STYLE_ENDPOINT=false" >> .env && \
        echo "" >> .env && \
        echo "PUSHER_APP_ID=" >> .env && \
        echo "PUSHER_APP_KEY=" >> .env && \
        echo "PUSHER_APP_SECRET=" >> .env && \
        echo "PUSHER_HOST=" >> .env && \
        echo "PUSHER_PORT=443" >> .env && \
        echo "PUSHER_SCHEME=https" >> .env && \
        echo "PUSHER_APP_CLUSTER=mt1" >> .env && \
        echo "" >> .env && \
        echo "VITE_APP_NAME=\"\${APP_NAME}\"" >> .env && \
        echo "VITE_PUSHER_APP_KEY=\"\${PUSHER_APP_KEY}\"" >> .env && \
        echo "VITE_PUSHER_HOST=\"\${PUSHER_HOST}\"" >> .env && \
        echo "VITE_PUSHER_PORT=\"\${PUSHER_PORT}\"" >> .env && \
        echo "VITE_PUSHER_SCHEME=\"\${PUSHER_SCHEME}\"" >> .env && \
        echo "VITE_PUSHER_APP_CLUSTER=\"\${PUSHER_APP_CLUSTER}\"" >> .env; \
    fi

# Genera la clave de la aplicación automáticamente
RUN php artisan key:generate --force

# Crear enlace simbólico para storage y configurar directorios
RUN php artisan storage:link \
    && mkdir -p storage/app/public/programs \
    && cp resources/images/programs/default.jpg storage/app/public/programs/ 2>/dev/null || true

# Asegura que todos los archivos en /app sean propiedad de 'node' después de todas las copias
RUN chown -R node:node /app

# Da permisos de ejecución al script de inicio
RUN chmod +x /app/start.sh

# Cambia al usuario 'node' para la compilación final y la ejecución
USER node

# Compila los assets como 'node'.
# npx debería encontrar vite en /app/node_modules/.bin
RUN npm run build || true

CMD ["bash", "/app/start.sh"]
