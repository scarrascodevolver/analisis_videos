# Despliegue - Optimización VPS 2 CPU / 4GB RAM

**Rama:** `optimize/vps-2cpu-4gb`
**Fecha:** 2026-01-27

---

## Instrucciones de Despliegue en VPS

### 1. Conectarse al VPS

```bash
ssh root@tu-vps-ip
cd /var/www/analisis_videos
```

### 2. Descargar los Cambios

```bash
# Ver rama actual
git branch --show-current

# Guardar cambios locales si hay alguno
git stash

# Obtener los cambios de la nueva rama
git fetch origin

# Cambiar a la rama optimizada
git checkout optimize/vps-2cpu-4gb

# Actualizar con los últimos cambios
git pull origin optimize/vps-2cpu-4gb
```

### 3. Detener el Queue Worker Actual

```bash
# Ver procesos corriendo
ps aux | grep "queue:work" | grep analisis_videos

# Matar el proceso del queue worker (reemplaza <PID> con el número real)
kill <PID>

# Verificar que se detuvo
ps aux | grep "queue:work" | grep analisis_videos
```

### 4. Actualizar Configuración de Supervisor (CRÍTICO)

Si tienes Supervisor configurado (recomendado para producción), actualiza su configuración:

```bash
# Editar configuración de supervisor
nano /etc/supervisor/conf.d/rugby-queue-worker.conf
```

Actualiza estas líneas:

```ini
[program:rugby-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/analisis_videos/artisan queue:work database --sleep=3 --tries=1 --max-time=3600 --timeout=14400
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/analisis_videos/storage/logs/queue-worker.log
stopwaitsecs=14400
```

**Cambios clave:**
- `--tries=1` (antes era 3)
- `--timeout=14400` (antes era 7200)
- `stopwaitsecs=14400` (antes era 7200)

Luego aplica los cambios:

```bash
# Recargar configuración de supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Reiniciar el worker con nueva configuración
sudo supervisorctl restart rugby-queue-worker:*

# Verificar estado
sudo supervisorctl status rugby-queue-worker:*
ps aux | grep "queue:work" | grep analisis_videos
```

Deberías ver: `--tries=1 --timeout=14400` en los parámetros del proceso.

### 5. Iniciar Manualmente (Si NO usas Supervisor)

```bash
# Método rápido con script incluido
bash start-queue-worker.sh

# O manualmente:
nohup php artisan queue:work database \
  --sleep=3 \
  --tries=1 \
  --max-time=3600 \
  --timeout=14400 \
  > storage/logs/queue-worker.log 2>&1 &

# Verificar que está corriendo
ps aux | grep "queue:work" | grep analisis_videos
```

### 6. Verificar que Todo Funciona

```bash
# Ver logs del worker
tail -f storage/logs/queue-worker.log

# Ver logs de compresión
tail -50 storage/logs/laravel.log | grep CompressVideoJob

# Ver estado del sistema
free -h
uptime
```

---

## Probar la Compresión

### 1. Subir un Video de Prueba

- Ve a la aplicación web
- Sube un video de ~2-4GB
- Observa el proceso en los logs

### 2. Monitorear el Proceso

```bash
# En una terminal, ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E "CompressVideoJob|FFmpeg"

# En otra terminal, ver uso de recursos
watch -n 2 'ps aux | grep -E "(ffmpeg|queue:work)" | grep -v grep'
```

### 3. Verificar el Resultado

Cuando termine, deberías ver en el log:

```
[2026-01-27 XX:XX:XX] production.INFO: CompressVideoJob: FFmpeg completed in XXXXs.
Output: XXX.XXMB (preset: veryfast, CRF: 22/24)

[2026-01-27 XX:XX:XX] production.INFO: CompressVideoJob: Completed successfully
for video XXX. Compression: XX.XX%
```

---

## Rollback (Si Algo Sale Mal)

Si necesitas volver a la versión anterior:

```bash
# 1. Volver a la rama anterior
git checkout feature/adaptive-compression

# 2. Si usas Supervisor, revertir su configuración
nano /etc/supervisor/conf.d/rugby-queue-worker.conf
```

Cambiar de vuelta a:
```ini
command=php /var/www/analisis_videos/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=7200
stopwaitsecs=7200
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart rugby-queue-worker:*

# 3. Si NO usas Supervisor, matar y reiniciar manualmente
ps aux | grep "queue:work" | grep analisis_videos
kill <PID>

nohup php artisan queue:work database \
  --sleep=3 \
  --tries=3 \
  --max-time=3600 \
  --timeout=7200 \
  > storage/logs/queue-worker.log 2>&1 &
```

---

## Cambios Aplicados en Esta Rama

### CompressVideoJob.php

**Antes:**
```php
public $tries = 3;
public $timeout = 7200; // 2 hours

// Solo 3 rangos de compresión
```

**Después:**
```php
public $tries = 1; // Sin reintentos
public $timeout = 14400; // 4 hours

// 4 rangos de compresión:
// < 500MB      → medium, CRF 23
// 500MB - 2GB  → fast, CRF 23
// 2GB - 4GB    → veryfast, CRF 22
// > 4GB        → veryfast, CRF 24 ⭐ NUEVO
```

### Ventajas de los Cambios

✅ Videos de 4GB+ ahora se procesan sin timeout
✅ CRF 24 para archivos muy grandes = procesamiento ~30% más rápido
✅ Sin reintentos automáticos = no bloquea el queue con jobs fallidos
✅ Sistema optimizado para VPS con recursos limitados

### Desventajas Temporales

⚠️ Solo puede procesar 1 video a la vez (hardware limitado)
⚠️ 9 usuarios simultáneos = esperas de hasta 16 horas
⚠️ Requiere migración a servidor más potente para escalabilidad

---

## Próximos Pasos (Migración a Hetzner)

1. **Contratar VPS Hetzner CPX31 o CPX41**
   - 4-8 vCPUs
   - 8-16GB RAM
   - ~€12-24/mes

2. **Migrar Sistema**
   - Base de datos
   - Archivos de código
   - Configuración de Spaces

3. **Configurar Workers Múltiples**
   - 3-8 procesos simultáneos
   - Supervisor para reinicio automático
   - Tiempos de espera aceptables

---

## Soporte

Si tienes problemas durante el despliegue:

1. Revisa los logs:
   ```bash
   tail -100 storage/logs/laravel.log
   tail -100 storage/logs/queue-worker.log
   ```

2. Verifica que FFmpeg está instalado:
   ```bash
   ffmpeg -version
   ```

3. Verifica permisos:
   ```bash
   ls -la storage/logs/
   ls -la storage/app/temp/
   ```

---

## Configuración de Supervisor (Producción)

**¿Por qué usar Supervisor?**

Supervisor garantiza que el queue worker:
- Se inicie automáticamente al arrancar el servidor
- Se reinicie si se cuelga o muere inesperadamente
- Respete los timeouts configurados antes de matar procesos
- Registre salidas en logs centralizados

**Instalación de Supervisor (si no lo tienes):**

```bash
# Instalar supervisor
sudo apt update
sudo apt install supervisor -y

# Crear archivo de configuración
sudo nano /etc/supervisor/conf.d/rugby-queue-worker.conf

# Pegar la configuración del paso 4

# Iniciar supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rugby-queue-worker:*

# Habilitar supervisor al inicio del sistema
sudo systemctl enable supervisor
```

**Comandos útiles de Supervisor:**

```bash
# Ver estado de todos los workers
sudo supervisorctl status

# Ver logs en tiempo real
sudo supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00

# Reiniciar worker
sudo supervisorctl restart rugby-queue-worker:*

# Detener worker
sudo supervisorctl stop rugby-queue-worker:*

# Iniciar worker
sudo supervisorctl start rugby-queue-worker:*
```

---

*Cualquier duda, revisa la documentación completa en `docs/VPS_OPTIMIZATION.md`*
