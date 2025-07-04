# 🏔️ Latitud90 - Sistema de Gestión Turística

Sistema de gestión turística desarrollado con Laravel 10, Vue.js 3, e Inertia.js para la administración de programas turísticos, pasajeros, pagos y reservas.

## 🚀 Características principales

- **Panel Administrativo**: Gestión completa de programas turísticos
- **Gestión de Pasajeros**: Registro y seguimiento de pasajeros
- **Procesamiento de Pagos**: Integración con Transbank y Khipu
- **Reservas**: Sistema de reservas y contratos
- **Dashboard**: Estadísticas y reportes
- **Responsive Design**: Interfaz moderna con Tailwind CSS

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 10 + PHP 8.2
- **Frontend**: Vue.js 3 + Inertia.js
- **Base de Datos**: MySQL
- **Estilos**: Tailwind CSS
- **Build Tool**: Vite
- **Containerización**: Docker + Docker Compose

## 📋 Requisitos del Sistema

- Docker Desktop
- Docker Compose
- Git

## 🔧 Instalación y Configuración

### 1. Clonar el repositorio
```bash
git clone https://github.com/FranciscoArenas/latitud90.git
cd latitud90
```

### 2. Configurar archivo de entorno
```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar las variables de entorno necesarias
# DB_HOST=db
# DB_DATABASE=laravel
# DB_USERNAME=laravel
# DB_PASSWORD=secret
```

### 3. Construir y ejecutar con Docker
```bash
# Construir e iniciar los contenedores
docker-compose up --build -d

# Ejecutar el script de inicialización completo
docker-compose exec app ./start.sh
```

### 4. Acceder a la aplicación
- **URL**: http://localhost:8086
- **Usuario admin**: admin@example.com
- **Contraseña**: password

## 🚀 Script de Inicio Automatizado

El script `start.sh` ejecuta automáticamente todos los pasos necesarios:

### Pasos incluidos:
1. **📁 Configuración de permisos** - Storage y cache
2. **🔐 Generación de clave** - APP_KEY automática
3. **📦 Dependencias Composer** - Instalación optimizada
4. **⚡ Autoloader** - Optimización de Composer
5. **🎨 Dependencias Node.js** - Frontend dependencies
6. **🔨 Compilación assets** - Vite build process
7. **🗄️ Migraciones** - Base de datos actualizada
8. **🌱 Seeders** - Datos de prueba con factories
9. **⚙️ Caché configuración** - Optimización para producción
10. **🧹 Limpieza cache** - Routes, views, config
11. **🔗 Storage link** - Enlace simbólico para archivos
12. **✅ Verificación** - Estado de la aplicación
13. **🚀 Servidor** - Laravel en puerto 8086

## 🗄️ Base de Datos

### Migraciones
```bash
# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Resetear y migrar desde cero
docker-compose exec app php artisan migrate:fresh
```

### Seeders y Factories
```bash
# Ejecutar todos los seeders
docker-compose exec app php artisan db:seed

# Ejecutar seeder específico
docker-compose exec app php artisan db:seed --class=AdminDemoSeeder

# Resetear base de datos con seeders
docker-compose exec app php artisan migrate:fresh --seed
```

### Datos de Prueba Incluidos
- **10 usuarios** generados con factories
- **Usuario de prueba**: test@example.com
- **Usuario admin**: admin@example.com / password
- **4 programas turísticos** básicos (AdminDemoSeeder)
- **8 programas adicionales** (EnhancedDemoSeeder)
- **45 pasajeros** con datos realistas chilenos
- **48 pagos** con diferentes estados y métodos

## 🎨 Frontend y Assets

### Compilación de Assets
```bash
# Desarrollo (con hot reload)
docker-compose exec app npm run dev

# Producción
docker-compose exec app npm run build

# Instalar dependencias
docker-compose exec app npm install
```

### Tecnologías Frontend
- **Vue.js 3**: Framework progresivo
- **Inertia.js**: SPA sin API
- **Tailwind CSS**: Utility-first CSS
- **Vite**: Build tool moderno
- **Headless UI**: Componentes accesibles

## 📊 Funcionalidades del Sistema

### Panel Administrativo
- Dashboard con estadísticas
- Gestión de programas turísticos
- Administración de pasajeros
- Procesamiento de pagos
- Generación de reportes

### Gestión de Programas
- Crear/editar programas turísticos
- Configurar precios y fechas
- Gestionar cupos y disponibilidad
- Imágenes y descripción detallada

### Gestión de Pasajeros
- Registro de pasajeros
- Documentación requerida
- Historial de viajes
- Estados de reserva

### Procesamiento de Pagos
- Integración con Transbank
- Integración con Khipu
- Gestión de cuotas
- Estados de pago
- Links de pago

## 🔧 Comandos Útiles

### Docker
```bash
# Iniciar contenedores
docker-compose up -d

# Detener contenedores
docker-compose down

# Ver logs
docker-compose logs -f app

# Acceder al contenedor
docker-compose exec app bash
```

### Laravel Artisan
```bash
# Limpiar caché
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Generar clave de aplicación
docker-compose exec app php artisan key:generate

# Crear enlace simbólico
docker-compose exec app php artisan storage:link

# Ver estado de la aplicación
docker-compose exec app php artisan about
```

## 🔐 Configuración de Pagos

### Transbank
```env
TRANSBANK_COMMERCE_CODE=597055555532
TRANSBANK_API_KEY=579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
TRANSBANK_ENVIRONMENT=integration
```

### Khipu
```env
KHIPU_RECEIVER_ID=tu_receiver_id
KHIPU_SECRET=tu_secret
KHIPU_BASE_URL=https://khipu.com/api/2.0
```

## 📝 Estructura del Proyecto

```
latitud90/
├── app/
│   ├── Http/Controllers/    # Controladores
│   ├── Models/             # Modelos Eloquent
│   └── Services/           # Servicios de pago
├── database/
│   ├── migrations/         # Migraciones
│   ├── seeders/           # Seeders
│   └── factories/         # Factories
├── resources/
│   ├── js/                # Vue.js components
│   ├── css/               # Estilos
│   └── views/             # Blade templates
├── routes/                # Rutas
├── docker-compose.yml     # Configuración Docker
├── start.sh              # Script de inicio
└── README.md             # Este archivo
```

## 🚀 Inicio Rápido

```bash
# 1. Clonar repositorio
git clone https://github.com/FranciscoArenas/latitud90.git
cd latitud90

# 2. Configurar entorno
cp .env.example .env

# 3. Iniciar con Docker
docker-compose up --build -d

# 4. Ejecutar script de configuración
docker-compose exec app ./start.sh

# 5. Acceder a la aplicación
# http://localhost:8086
# admin@example.com / password
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- Email: soporte@latitud90.com
- Documentación: [Wiki del proyecto](https://github.com/FranciscoArenas/latitud90/wiki)
- Issues: [GitHub Issues](https://github.com/FranciscoArenas/latitud90/issues)

---

**Desarrollado con ❤️ para Latitud90**
