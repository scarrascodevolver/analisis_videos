# Optimización VPS 2 CPU / 4GB RAM

**Fecha:** 2026-01-27
**Rama:** `optimize/vps-2cpu-4gb`
**Estado:** Configuración temporal hasta migración a Hetzner

---

## Resumen de Cambios

El sistema ha sido optimizado para funcionar en un VPS con recursos limitados (2 CPU, 4GB RAM) mientras se planea la migración a un servidor más potente.

### Modificaciones Realizadas

1. **Timeout aumentado a 4 horas** (14400 segundos)
   - Permite procesar videos de 4GB+ sin cancelación automática
   - Línea modificada: `CompressVideoJob.php:31`

2. **Reintentos reducidos a 1** (antes 3)
   - Evita bloquear el queue con reintentos fallidos
   - Línea modificada: `CompressVideoJob.php:24`

3. **Compresión adaptativa mejorada** con 4 rangos:
   ```
   < 500MB      → preset: medium,   CRF 23  (~2h)
   500MB - 2GB  → preset: fast,     CRF 23  (~2h)
   2GB - 4GB    → preset: veryfast, CRF 22  (~2.5h)
   > 4GB        → preset: veryfast, CRF 24  (~3h) ⭐ NUEVO
   ```

---

## Configuración del Queue Worker

### 1. Detener el Worker Actual (si está corriendo)

```bash
# Ver procesos del queue worker
ps aux | grep "queue:work" | grep analisis_videos

# Matar el proceso (reemplaza PID con el número real)
kill <PID>
```

### 2. Iniciar el Nuevo Worker (con timeout de 4 horas)

```bash
# En el servidor VPS como root
cd /var/www/analisis_videos

# Iniciar worker con nuevo timeout
nohup php artisan queue:work database \
  --sleep=3 \
  --tries=1 \
  --max-time=3600 \
  --timeout=14400 \
  > storage/logs/queue-worker.log 2>&1 &

# Verificar que está corriendo
ps aux | grep "queue:work" | grep analisis_videos
```

### 3. Configurar para Reinicio Automático (Opcional)

Crear archivo `/etc/systemd/system/rugbyhub-queue.service`:

```ini
[Unit]
Description=RugbyHub Video Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/analisis_videos
ExecStart=/usr/bin/php /var/www/analisis_videos/artisan queue:work database --sleep=3 --tries=1 --max-time=3600 --timeout=14400
Restart=always
RestartSec=10
StandardOutput=append:/var/www/analisis_videos/storage/logs/queue-worker.log
StandardError=append:/var/www/analisis_videos/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
```

Activar el servicio:

```bash
# Recargar systemd
sudo systemctl daemon-reload

# Iniciar servicio
sudo systemctl start rugbyhub-queue

# Habilitar inicio automático
sudo systemctl enable rugbyhub-queue

# Ver estado
sudo systemctl status rugbyhub-queue
```

---

## Capacidad del Sistema

### Hardware Actual
- **CPU:** 2 cores (DigitalOcean Regular)
- **RAM:** 3.8GB (3.3GB disponible)
- **Swap:** 2GB

### Limitaciones

**1 proceso FFmpeg a la vez:**
- RAM usada: ~650MB (sobra 2.6GB)
- CPU usada: ~180% (deja 20% libre)
- ✅ Sistema estable, sin saturación

**2 procesos FFmpeg simultáneos:**
- RAM usada: ~1.3GB (sobra 2GB)
- CPU usada: ~360% (⚠️ **SATURACIÓN** - solo hay 200% disponible)
- ❌ Videos se procesan 50-100% más lento por competencia

### Escenario con 9 Usuarios Activos

Con 1 proceso simultáneo (configuración actual):

```
Usuario 1: Empieza inmediatamente
Usuario 2: Espera ~2 horas
Usuario 3: Espera ~4 horas
Usuario 4: Espera ~6 horas
Usuario 5: Espera ~8 horas
Usuario 6: Espera ~10 horas
Usuario 7: Espera ~12 horas
Usuario 8: Espera ~14 horas
Usuario 9: Espera ~16 horas (❌ INACEPTABLE)
```

**Conclusión:** El VPS actual **NO es viable** para 9 usuarios activos simultáneamente.

---

## Recomendaciones de Hardware

### Hetzner VPS Mínimo Recomendado
- **Modelo:** CPX31
- **vCPU:** 4 cores
- **RAM:** 8GB
- **Precio:** ~€12/mes
- **Capacidad:** 3-4 procesos simultáneos
- **Usuario 9 espera:** ~4-5 horas

### Hetzner VPS Ideal
- **Modelo:** CPX41
- **vCPU:** 8 cores
- **RAM:** 16GB
- **Precio:** ~€24/mes
- **Capacidad:** 6-8 procesos simultáneos
- **Usuario 9 espera:** ~2-3 horas

---

## Monitoreo del Sistema

### Ver Estado de Compresión

```bash
# Ver últimos 50 logs de compresión
tail -50 storage/logs/laravel.log | grep -E "CompressVideoJob|FFmpeg"

# Ver videos en procesamiento
php artisan tinker --execute="Video::whereIn('processing_status', ['pending', 'processing'])->get(['id','title','processing_status','created_at'])"

# Ver carga del sistema
uptime
free -h
ps aux | grep ffmpeg
```

### Verificar Tiempos de Procesamiento

```bash
# Ver logs de tiempos FFmpeg
grep "FFmpeg completed" storage/logs/laravel.log | tail -10
```

Ejemplo de output:
```
[2026-01-27 01:25:23] INFO: FFmpeg completed in 4522.68s. Output: 649.26MB (preset: veryfast, CRF: 22)
[2026-01-27 06:11:35] INFO: FFmpeg completed in 6949.22s. Output: 683.12MB (preset: fast, CRF: 23)
```

---

## Notificación a Usuarios

### Agregar Banner en Upload (Próxima Implementación)

```html
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Procesamiento de Videos</strong><br>
    Los videos grandes (>2GB) pueden tardar 2-4 horas en procesarse.
    Recibirás una notificación cuando esté listo.
</div>
```

### Mostrar Tiempo Estimado (Próxima Implementación)

```php
// Calcular tiempo estimado basado en tamaño
$fileSizeMB = $video->file_size / 1024 / 1024;
if ($fileSizeMB < 500) {
    $estimatedTime = '30-60 minutos';
} elseif ($fileSizeMB < 2000) {
    $estimatedTime = '1-2 horas';
} elseif ($fileSizeMB < 4000) {
    $estimatedTime = '2-3 horas';
} else {
    $estimatedTime = '3-4 horas';
}
```

---

## Migración a Hetzner (Pendiente)

### Checklist Pre-Migración

- [ ] Contratar VPS Hetzner (CPX31 o CPX41)
- [ ] Configurar servidor (LEMP stack, PHP 8.2+, FFmpeg)
- [ ] Migrar base de datos (mysqldump + import)
- [ ] Migrar archivos de código (git clone)
- [ ] Configurar DigitalOcean Spaces credentials
- [ ] Configurar queue worker con timeout de 4 horas
- [ ] Configurar Supervisor para reinicio automático
- [ ] Probar subida y procesamiento de video
- [ ] Actualizar DNS para apuntar al nuevo servidor

### Ventajas Post-Migración

- ✅ 3-4x más capacidad de procesamiento
- ✅ 9 usuarios pueden subir videos simultáneamente
- ✅ Tiempos de espera aceptables (<5 horas)
- ✅ Sistema escalable para crecimiento futuro

---

## Notas Técnicas

- **CRF 24 vs CRF 22:** Diferencia imperceptible en calidad, pero ~20-30% más rápido
- **Preset veryfast:** Usa menos CPU por frame, pero más frames/segundo
- **Timeout 4 horas:** Suficiente para videos de 4GB+ con preset veryfast
- **Single attempt (tries=1):** Evita reintentos automáticos que bloquean el queue

---

*Documentado por Claude Code - Optimización temporal para VPS limitado*
