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

### 4. Iniciar el Nuevo Queue Worker (Opción Simple)

```bash
# Método rápido con script incluido
bash start-queue-worker.sh
```

### 5. O Iniciar Manualmente (Opción Avanzada)

```bash
# Crear directorio de logs si no existe
mkdir -p storage/logs

# Iniciar worker con nuevo timeout de 4 horas
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
# Detener el nuevo worker
ps aux | grep "queue:work" | grep analisis_videos
kill <PID>

# Volver a la rama anterior
git checkout feature/adaptive-compression

# Iniciar el worker con configuración anterior
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

*Cualquier duda, revisa la documentación completa en `docs/VPS_OPTIMIZATION.md`*
