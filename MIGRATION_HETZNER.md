# 🚀 Plan de Migración a Hetzner VPS + Object Storage

**Fecha:** 2026-02-04
**Objetivo:** Migrar de DigitalOcean a Hetzner para mejor rendimiento y costo

---

## 📊 Comparación de Infraestructura

| Aspecto | DigitalOcean Actual | Hetzner Planeado |
|---------|---------------------|------------------|
| **VPS** | 2 vCPU, 4GB RAM | CPX31: 4 vCPU, 8GB RAM |
| **Costo VPS** | ~$20/mes | ~€12/mes (~$13/mes) |
| **Object Storage** | Spaces (SFO3) | Object Storage (FSN1/NBG1) |
| **Costo Storage** | $5 + egress | €5 + egress similar |
| **Latencia VPS-Storage** | ~150ms (cross-region) | <5ms (mismo datacenter) |
| **Workers Simultáneos** | 1 (limitado) | 3-4 (escalable) |
| **Compresión Video 4GB** | ~4 horas | ~1-2 horas |

**Beneficios:**
- ✅ 2x CPU/RAM = procesar 3-4 videos simultáneamente
- ✅ Latencia VPS-Storage 30x mejor (crítico para compresión)
- ✅ Costo similar o menor
- ✅ Infraestructura europea (mejor para compliance GDPR)

---

## 🎯 Checklist de Migración

### Fase 1: Preparación (Pre-migración)

#### 1.1 Contratar Servicios Hetzner

**VPS Recomendado:**
- **CPX31** (4 vCPU, 8GB RAM) - €12.96/mes - [MÍNIMO]
- **CPX41** (8 vCPU, 16GB RAM) - €23.76/mes - [IDEAL]

**Object Storage:**
- Crear bucket en Hetzner Object Storage
- Región recomendada: FSN1 (Falkenstein, Alemania) o NBG1 (Nuremberg)

#### 1.2 Obtener Credenciales

```bash
# Hetzner Cloud Console → Object Storage → Manage Keys
# Guardar:
- Access Key ID
- Secret Access Key
- Endpoint URL (ej: https://fsn1.your-objectstorage.com)
```

#### 1.3 Crear Backup Completo

```bash
# En VPS actual (DigitalOcean)
ssh root@161.35.108.164
cd /var/www/analisis_videos

# Backup base de datos
mysqldump -u root -p rugby_db > /root/backup_rugby_$(date +%Y%m%d).sql
gzip /root/backup_rugby_*.sql

# Verificar tamaño
ls -lh /root/backup_rugby_*.sql.gz

# Backup configuración
cp .env /root/backup_env_$(date +%Y%m%d)
```

---

### Fase 2: Configurar Nuevo VPS Hetzner (2-4 horas)

#### 2.1 Acceso Inicial

```bash
# Conectar al nuevo VPS
ssh root@NUEVA_IP_HETZNER

# Actualizar sistema
apt update && apt upgrade -y
```

#### 2.2 Instalar LEMP Stack

```bash
# Nginx
apt install nginx -y

# PHP 8.2+ (Laravel 12 requiere PHP 8.2+)
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
  php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath \
  php8.2-soap php8.2-redis -y

# MySQL
apt install mysql-server -y
mysql_secure_installation

# FFmpeg (para compresión de video)
apt install ffmpeg -y

# Supervisor (para queue workers)
apt install supervisor -y

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Node.js + NPM (para assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
```

#### 2.3 Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p
```

```sql
CREATE DATABASE rugby_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rugbyhub'@'localhost' IDENTIFIED BY 'PASSWORD_SEGURO';
GRANT ALL PRIVILEGES ON rugby_db.* TO 'rugbyhub'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 2.4 Importar Backup BD

```bash
# Transferir backup desde DO a Hetzner
# Opción A: Desde tu PC local
scp root@161.35.108.164:/root/backup_rugby_*.sql.gz .
scp backup_rugby_*.sql.gz root@NUEVA_IP_HETZNER:/root/

# Opción B: Directamente entre servidores
ssh root@NUEVA_IP_HETZNER
scp root@161.35.108.164:/root/backup_rugby_*.sql.gz /root/

# Descomprimir e importar
gunzip /root/backup_rugby_*.sql.gz
mysql -u rugbyhub -p rugby_db < /root/backup_rugby_*.sql
```

#### 2.5 Clonar Código

```bash
# Instalar Git
apt install git -y

# Clonar repo
cd /var/www
git clone https://github.com/tu-usuario/rugbyhub.git
cd rugbyhub

# Permisos
chown -R www-data:www-data /var/www/rugbyhub
chmod -R 755 /var/www/rugbyhub
chmod -R 775 /var/www/rugbyhub/storage
chmod -R 775 /var/www/rugbyhub/bootstrap/cache

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

---

### Fase 3: Configurar Hetzner Object Storage

#### 3.1 Actualizar .env

```bash
nano /var/www/rugbyhub/.env
```

**Variables críticas a cambiar:**

```bash
# App
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rugby_db
DB_USERNAME=rugbyhub
DB_PASSWORD=PASSWORD_SEGURO

# Hetzner Object Storage (reemplazar DO_SPACES_*)
DO_SPACES_KEY=TU_HETZNER_ACCESS_KEY
DO_SPACES_SECRET=TU_HETZNER_SECRET_KEY
DO_SPACES_ENDPOINT=https://fsn1.your-objectstorage.com
DO_SPACES_REGION=fsn1
DO_SPACES_BUCKET=rugbyhub-videos
DO_SPACES_CDN_URL=https://rugbyhub-videos.fsn1.your-objectstorage.com

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database
```

#### 3.2 Verificar Conexión a Object Storage

```bash
# Instalar AWS CLI
apt install awscli -y

# Configurar credenciales
aws configure set aws_access_key_id TU_HETZNER_ACCESS_KEY
aws configure set aws_secret_access_key TU_HETZNER_SECRET_KEY
aws configure set default.region fsn1

# Probar conexión
aws s3 ls --endpoint-url=https://fsn1.your-objectstorage.com
aws s3 ls s3://rugbyhub-videos --endpoint-url=https://fsn1.your-objectstorage.com
```

#### 3.3 Ajustar config/filesystems.php (si es necesario)

```bash
nano /var/www/rugbyhub/config/filesystems.php
```

**Verificar/ajustar configuración `spaces`:**

```php
'spaces' => [
    'driver' => 's3',
    'key' => env('DO_SPACES_KEY'),
    'secret' => env('DO_SPACES_SECRET'),
    'endpoint' => env('DO_SPACES_ENDPOINT'),
    'region' => env('DO_SPACES_REGION', 'fsn1'),  // Default a fsn1
    'bucket' => env('DO_SPACES_BUCKET'),
    'url' => env('DO_SPACES_CDN_URL'),
    'use_path_style_endpoint' => true,  // ⚠️ Puede ser necesario para Hetzner
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
    'http' => [
        'verify' => env('APP_ENV') === 'production' ? true : false,
    ],
],
```

**Nota:** Si `use_path_style_endpoint => true` no funciona, prueba con `false`.

---

### Fase 4: Configurar Queue Workers (Crítico)

#### 4.1 Crear Configuración Supervisor

```bash
nano /etc/supervisor/conf.d/rugby-queue-worker.conf
```

**Contenido:**

```ini
[program:rugby-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/rugbyhub/artisan queue:work database --sleep=3 --tries=1 --timeout=14400
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/rugbyhub/storage/logs/queue-worker.log
stopwaitsecs=14400

[group:rugby-workers]
programs=rugby-queue-worker
```

**Parámetros clave:**
- `numprocs=4` → 4 workers simultáneos con CPX31 (4 vCPUs)
- `numprocs=8` → 8 workers con CPX41 (8 vCPUs)
- `timeout=14400` → 4 horas para videos grandes

#### 4.2 Activar Supervisor

```bash
# Recargar configuración
supervisorctl reread
supervisorctl update

# Iniciar workers
supervisorctl start rugby-queue-worker:*

# Verificar estado
supervisorctl status

# Ver logs en tiempo real
supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00
```

---

### Fase 5: Configurar Nginx

#### 5.1 Crear Configuración del Sitio

```bash
nano /etc/nginx/sites-available/rugbyhub
```

**Contenido básico:**

```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;
    root /var/www/rugbyhub/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Max upload 5GB (para videos grandes)
    client_max_body_size 5G;
    client_body_timeout 3600s;
    proxy_read_timeout 3600s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 3600;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 5.2 Activar Sitio

```bash
# Enlace simbólico
ln -s /etc/nginx/sites-available/rugbyhub /etc/nginx/sites-enabled/

# Verificar configuración
nginx -t

# Reiniciar Nginx
systemctl restart nginx
```

#### 5.3 Configurar SSL (Certbot)

```bash
# Instalar Certbot
apt install certbot python3-certbot-nginx -y

# Obtener certificado SSL
certbot --nginx -d tu-dominio.com -d www.tu-dominio.com

# Renovación automática ya está configurada
systemctl status certbot.timer
```

---

### Fase 6: Migrar Videos Existentes (Opcional)

#### Opción A: No Migrar (Más Fácil)

**Ventajas:**
- Sin tiempo de inactividad
- Videos existentes siguen funcionando
- Solo nuevos videos en Hetzner

**Desventaja:**
- Dependencia de 2 proveedores

**Implementación:**
```php
// En código, detectar donde está el video
if (str_contains($video->storage_path, 'digitalocean')) {
    // Usar credenciales DO
} else {
    // Usar credenciales Hetzner
}
```

#### Opción B: Migrar Todo (Completo)

**Usando rclone (recomendado):**

```bash
# Instalar rclone
curl https://rclone.org/install.sh | bash

# Configurar DO Spaces
rclone config
# Name: digitalocean
# Type: s3
# Provider: DigitalOcean Spaces
# Access Key: TU_DO_KEY
# Secret Key: TU_DO_SECRET
# Endpoint: sfo3.digitaloceanspaces.com

# Configurar Hetzner
rclone config
# Name: hetzner
# Type: s3
# Provider: Other
# Access Key: TU_HETZNER_KEY
# Secret Key: TU_HETZNER_SECRET
# Endpoint: fsn1.your-objectstorage.com

# Migrar archivos
rclone copy digitalocean:tu-bucket hetzner:rugbyhub-videos --progress --transfers=8

# Verificar
rclone ls hetzner:rugbyhub-videos | wc -l
```

**Tiempo estimado:**
- 100GB: ~2-4 horas
- 500GB: ~10-20 horas
- 1TB: ~20-40 horas

---

### Fase 7: Testing Completo

#### 7.1 Probar Funcionalidad Básica

```bash
# En navegador: https://tu-dominio.com

# Verificar:
✅ Login funciona
✅ Dashboard carga
✅ Videos existentes reproducen
```

#### 7.2 Probar Upload de Video

```bash
# Subir video de prueba (500MB-1GB)
# Verificar en logs:
tail -f /var/www/rugbyhub/storage/logs/laravel.log

# Debe mostrar:
# - Upload successful
# - CompressVideoJob dispatched
```

#### 7.3 Probar Compresión

```bash
# Ver queue workers procesando
supervisorctl status

# Ver logs de compresión
tail -f /var/www/rugbyhub/storage/logs/queue-worker.log | grep CompressVideoJob

# Verificar en Hetzner Object Storage
aws s3 ls s3://rugbyhub-videos/videos/ --endpoint-url=https://fsn1.your-objectstorage.com
```

#### 7.4 Verificar Performance

```bash
# Monitorear recursos
htop

# Con 4 videos procesando simultáneamente:
✅ CPU: 300-400% (de 400% disponible)
✅ RAM: 3-4GB (de 8GB disponible)
✅ Sin swap
```

---

### Fase 8: Cambiar DNS (Go Live)

#### 8.1 Backup Final del VPS Viejo

```bash
# En DO VPS
ssh root@161.35.108.164
mysqldump -u root -p rugby_db > /root/backup_final_$(date +%Y%m%d_%H%M).sql.gz
```

#### 8.2 Actualizar DNS

```bash
# En tu proveedor DNS (Cloudflare, GoDaddy, etc.)
# Cambiar A record:
# tu-dominio.com → NUEVA_IP_HETZNER
```

**Tiempo de propagación:** 5-60 minutos

#### 8.3 Verificar Propagación

```bash
# Desde tu PC
dig tu-dominio.com +short
# Debe mostrar: NUEVA_IP_HETZNER

# Verificar en navegador (modo incógnito)
https://tu-dominio.com
```

---

## 🔧 Troubleshooting

### Problema: "Connection refused to Object Storage"

**Verificar:**
```bash
# 1. Endpoint correcto
env | grep DO_SPACES

# 2. Credenciales válidas
aws s3 ls --endpoint-url=https://fsn1.your-objectstorage.com

# 3. Firewall
ufw status
```

**Solución:**
```bash
# Probar con curl
curl -I https://fsn1.your-objectstorage.com
```

### Problema: "FFmpeg no comprime videos"

**Verificar:**
```bash
# FFmpeg instalado
ffmpeg -version

# Workers corriendo
supervisorctl status

# Permisos storage
ls -la /var/www/rugbyhub/storage/app/temp/
chown -R www-data:www-data /var/www/rugbyhub/storage/
```

### Problema: "Videos no reproducen"

**Verificar:**
```bash
# CORS en bucket
# Configurar en Hetzner Console:
# AllowedOrigins: *
# AllowedMethods: GET, HEAD
# AllowedHeaders: *

# Verificar URLs en BD
mysql -u rugbyhub -p rugby_db
SELECT id, title, storage_path FROM videos LIMIT 5;
```

---

## 📊 Métricas de Éxito

| Métrica | Antes (DO) | Después (Hetzner) | Objetivo |
|---------|------------|-------------------|----------|
| Videos simultáneos | 1 | 3-4 | ✅ 3-4 |
| Compresión 4GB | 4 horas | 1-2 horas | ✅ <2h |
| Latencia VPS-Storage | 150ms | <5ms | ✅ <10ms |
| Espera usuario 9 | 16h | 4-6h | ✅ <6h |
| Costo mensual | $25 | €18 (~$20) | ✅ Similar |

---

## 🆘 Rollback (Si Algo Sale Mal)

### Plan B: Volver a DigitalOcean

```bash
# 1. Cambiar DNS de vuelta
# tu-dominio.com → IP_ANTIGUA_DO

# 2. En VPS DO, verificar que todo funciona
ssh root@161.35.108.164
supervisorctl status

# 3. Esperar propagación DNS
```

**Mantener VPS Hetzner activo 1 semana como backup antes de cancelar DO.**

---

## 📝 Notas Finales

### Ventajas de Hetzner vs DigitalOcean

✅ **Performance:**
- 2x CPU/RAM
- Latencia 30x mejor entre VPS y Storage
- 3-4x más capacidad de procesamiento

✅ **Costo:**
- Precio similar o menor
- Mejor relación precio/rendimiento

✅ **Ubicación:**
- Europa (GDPR compliance)
- Baja latencia para usuarios europeos

### Consideraciones

⚠️ **Latencia Upload desde Chile:**
- Hetzner (Alemania): ~200-250ms
- DigitalOcean SFO3: ~150ms
- **Diferencia:** ~50ms (marginal para uploads grandes)

⚠️ **Alternativa Cloudflare R2:**
- Mejor para uploads (edge global)
- Más caro para storage grande (>250GB)
- Considerar si velocidad de upload es crítica

### Recomendación Final

**Para RugbyHub con compresión de video:**
- ✅ **Hetzner VPS CPX31/CPX41** (procesamiento)
- ✅ **Hetzner Object Storage** (storage económico)
- 🔄 **Considerar Cloudflare R2** si velocidad de upload es crítica

---

**Autor:** Claude Sonnet 4.5
**Fecha:** 2026-02-04
**Última actualización:** 2026-02-04
