# Control de Compresión de Videos - Instrucciones

## Problema Resuelto

Con **2 CPU / 4GB RAM**, el proceso de compresión FFmpeg consume:
- **100% de 1 CPU**
- **2-3GB de RAM**

Esto causa que el sistema se congele cuando se usa multi-cámara con videos grandes (4GB+).

---

## Solución: Compresión Nocturna Automática

El script `queue-control.sh` gestiona automáticamente la compresión:
- **Día (8 AM - 10 PM):** Queue worker PAUSADO → CPU libre para multi-cámara
- **Noche (10 PM - 8 AM):** Queue worker ACTIVO → Comprime videos

---

## Uso Manual (Inmediato)

### Comandos Básicos

```bash
# En el VPS
cd /var/www/analisis_videos

# Pausar compresión (cuando uses multi-cámara)
./queue-control.sh stop

# Reanudar compresión
./queue-control.sh start

# Ver estado actual
./queue-control.sh status
```

### Ejemplo de Uso

```bash
# Antes de agregar ángulos de cámara
./queue-control.sh stop

# Trabajar con multi-cámara...

# Cuando termines, reanudar compresión
./queue-control.sh start
```

---

## Configuración Automática con Cron

### Paso 1: Editar crontab

```bash
sudo crontab -e
```

### Paso 2: Agregar línea de cron

Agrega esta línea al final del archivo:

```cron
# Auto-control de queue worker (compresión nocturna)
0 * * * * cd /var/www/analisis_videos && ./queue-control.sh auto >> storage/logs/queue-control.log 2>&1
```

**Explicación:**
- `0 * * * *` = Ejecutar cada hora en punto (8:00, 9:00, 10:00...)
- `auto` = Modo automático (decide según la hora)
- Log en `storage/logs/queue-control.log`

### Paso 3: Guardar y salir

- Nano: `Ctrl+X`, luego `Y`, luego `Enter`
- Vim: `Esc`, luego `:wq`, luego `Enter`

### Paso 4: Verificar cron activo

```bash
sudo crontab -l
```

Deberías ver la línea que agregaste.

---

## Horario Predeterminado

```
 8 AM ──────────────────────── 10 PM
  ↓                              ↓
[PAUSADO] Trabajo con videos  [ACTIVO]
          Multi-cámara fluido  Compresión
```

### Cambiar Horario

Edita `queue-control.sh` líneas 12-13:

```bash
WORK_START_HOUR=8   # Cambiar a tu hora de inicio
WORK_END_HOUR=22    # Cambiar a tu hora de fin
```

---

## Monitoreo

### Ver logs del auto-control

```bash
tail -f storage/logs/queue-control.log
```

### Ver estado del queue worker

```bash
./queue-control.sh status
```

### Ver logs de compresión

```bash
tail -f storage/logs/laravel.log | grep CompressVideoJob
```

---

## Escenarios Comunes

### 1. Vas a usar multi-cámara AHORA (día)

```bash
./queue-control.sh stop
# Usa multi-cámara...
# El cron lo reactivará de noche automáticamente
```

### 2. Necesitas comprimir videos urgente (día)

```bash
./queue-control.sh start
# Espera que termine...
./queue-control.sh stop  # Pausa de nuevo
```

### 3. Ver cuántos videos están en queue

```bash
mysql -u root -p rugby_db -e "SELECT COUNT(*) FROM jobs;"
```

### 4. Ver progreso de compresión actual

```bash
ps aux | grep ffmpeg
# Busca "TIME+" para ver cuánto lleva
```

---

## Troubleshooting

### El cron no funciona

```bash
# Verificar que cron esté activo
sudo systemctl status cron

# Ver logs de cron
sudo tail -f /var/log/syslog | grep CRON
```

### El script dice "Permission denied"

```bash
chmod +x queue-control.sh
```

### Quiero desactivar el auto-control

```bash
sudo crontab -e
# Comenta la línea con # al inicio
# 0 * * * * cd /var/www...
```

---

## Migración a Hetzner (Futuro)

Cuando migren a **Hetzner CPX21 (2 vCPU AMD / 8GB RAM)**:

1. **Mantener cron** (funciona mejor con más RAM)
2. **Aumentar workers** a 2 simultáneos:
   ```bash
   # En supervisor config
   numprocs=2
   ```
3. **Reducir timeout** (más CPU = más rápido):
   ```bash
   --timeout=7200  # 2 horas en vez de 4
   ```

Con **CPX31 (4 vCPU / 16GB RAM)**:
- 4 workers simultáneos
- Sin necesidad de cron (suficiente CPU para todo)

---

## Comandos de Referencia Rápida

```bash
# Manual
./queue-control.sh stop      # Pausar
./queue-control.sh start     # Reanudar
./queue-control.sh status    # Estado

# Supervisor directo
sudo supervisorctl status rugby-queue-worker:*
sudo supervisorctl stop rugby-queue-worker:*
sudo supervisorctl start rugby-queue-worker:*
sudo supervisorctl restart rugby-queue-worker:*
sudo supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00

# Logs
tail -f storage/logs/queue-control.log
tail -f storage/logs/laravel.log | grep CompressVideoJob
```

---

## Resumen

✅ **Día:** CPU libre para multi-cámara
✅ **Noche:** Compresión automática
✅ **Control manual:** Cuando necesites
✅ **Sin intervención:** Cron lo gestiona

**Próximo paso:** Migrar a Hetzner para no necesitar restricciones.

---

*Documentado para VPS 2 CPU / 4GB RAM - Enero 2026*
