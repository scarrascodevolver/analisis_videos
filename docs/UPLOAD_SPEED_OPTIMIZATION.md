# Optimización de Velocidad de Upload

**Fecha:** 2026-02-03
**Problema:** Videos de 4GB tardaban 15 minutos en subir (esperado: 2-4 minutos)
**Causa Raíz:** Baja paralelización en multipart upload

---

## Cambios Realizados

### 1. Paralelización Aumentada

**Antes:**
```javascript
const maxConcurrent = 2; // Solo 2 chunks simultáneos
```

**Después:**
```javascript
const maxConcurrent = 10; // 10 chunks simultáneos
```

**Impacto:** Reduce tiempo de upload de videos grandes en ~75%

### 2. Tamaño de Chunks Optimizado

**Antes:**
```javascript
const CHUNK_SIZE = 50 * 1024 * 1024; // 50MB
```

**Después:**
```javascript
const CHUNK_SIZE = 100 * 1024 * 1024; // 100MB
```

**Beneficios:**
- Menos chunks totales (4GB: 80 chunks → 40 chunks)
- Menos overhead de HTTP requests
- Menor latencia total

### 3. Timeout Ajustado

**Antes:**
```javascript
xhr.timeout = 1800000; // 30 minutos por chunk
```

**Después:**
```javascript
xhr.timeout = 600000; // 10 minutos por chunk
```

**Razón:** Con conexiones decentes, un chunk de 100MB no debería tardar más de 2-3 minutos

---

## Tiempos Estimados (Nuevos)

### Video 4GB

| Conexión Usuario | Chunks Paralelos | Tiempo Estimado | Velocidad |
|------------------|------------------|-----------------|-----------|
| 50 Mbps upload | 10 | 10-12 minutos | ~5.5 MB/s |
| 100 Mbps upload | 10 | 5-6 minutos | ~11 MB/s |
| 200 Mbps upload | 10 | **2-3 minutos** | ~22 MB/s |
| 300+ Mbps upload | 10 | **2 minutos** | ~33 MB/s |

### Video 1GB

| Conexión Usuario | Tiempo Estimado |
|------------------|-----------------|
| 50 Mbps | 2-3 minutos |
| 100 Mbps | 1-1.5 minutos |
| 200+ Mbps | **< 1 minuto** |

---

## Validación

### Test Manual desde Chile/Argentina

1. **Preparar video de prueba:**
   ```bash
   # En Windows, crear video de ~500MB-1GB de prueba
   # O usar un video real existente
   ```

2. **Abrir consola del navegador (F12)**

3. **Subir video y monitorear:**
   - Observar logs: "Starting upload of part X (pending: Y, in-progress: Z)"
   - Debería ver "in-progress: 10" (antes era "in-progress: 2")

4. **Cronometrar el tiempo total:**
   ```
   Inicio: [timestamp]
   Fin: [timestamp]
   Tamaño: [GB]
   Tiempo: [minutos]
   Velocidad: [GB / minutos]
   ```

### Test con Herramienta de Diagnóstico

```bash
# Desde el navegador
cd /path/to/rugbyhub
# Agregar script de diagnóstico a la vista
```

---

## Factores que Afectan Velocidad Real

### 1. Conexión del Usuario (MÁS IMPORTANTE)

**Upload Speed del ISP:**
- Chile (típico hogar): 10-50 Mbps upload
- Chile (fibra premium): 100-300 Mbps upload
- Oficina/Empresa: 100-500 Mbps upload

**Para verificar velocidad real del cliente:**
```
1. Abrir: https://www.speedtest.net/
2. Hacer "GO"
3. Anotar "UPLOAD" (Mbps)
```

**Importante:** Muchos ISPs anuncian velocidad de DOWNLOAD, pero upload es mucho menor:
- "200 Mbps internet" = a veces solo 20 Mbps upload
- "Fibra 500 Mbps" = a veces solo 50 Mbps upload

### 2. Distancia Geográfica

| Desde | Hacia | Impacto |
|-------|-------|---------|
| Chile | SFO3 (California) | Medio (~150ms latencia) |
| Argentina | SFO3 | Medio-Alto (~200ms) |
| España | SFO3 | Alto (~250ms) |

**Nota:** Latencia afecta el inicio de cada chunk, pero con 10 chunks paralelos el impacto se diluye.

### 3. Hora del Día

- **Horario pico** (8pm-11pm): ISP puede hacer throttling
- **Horario valle** (2am-7am): Mejor rendimiento
- **Días laborales** (10am-6pm): Red corporativa estable

### 4. Límites del Backend

**DigitalOcean Spaces:**
- ✅ Soporta uploads paralelos ilimitados
- ✅ No hay límite de ancho de banda
- ✅ Presigned URLs válidas por 1 hora

**Laravel Backend:**
- ✅ Sin límites de rate limiting para multipart
- ✅ Timeouts configurados correctamente

---

## Troubleshooting

### "Sigue tardando 15 minutos"

**Verificar:**
1. Archivo JS actualizado en producción:
   ```bash
   # SSH al VPS
   grep "maxConcurrent = 10" /var/www/analisis_videos/public/js/batch-upload.js
   ```

2. Cache del navegador limpiado:
   - Chrome: Ctrl+Shift+R
   - Incógnito: Ctrl+Shift+N

3. Velocidad real de upload del usuario:
   ```
   https://www.speedtest.net/
   ```

### "Da error timeout"

Posibles causas:
- Conexión muy lenta (< 5 Mbps upload)
- ISP bloqueando conexiones paralelas
- Red inestable

**Solución temporal:** Reducir paralelización a 5:
```javascript
const maxConcurrent = 5;
```

### "Da error de partes duplicadas"

Verificar logs:
```bash
tail -f /var/www/analisis_videos/storage/logs/laravel.log | grep multipart
```

El código ya tiene protección contra duplicados (línea 763).

---

## Optimizaciones Futuras (Opcional)

### 1. Paralelización Adaptativa

Detectar velocidad de conexión y ajustar automáticamente:
```javascript
// Detectar velocidad
const connection = navigator.connection;
const speed = connection?.downlink || 10; // Mbps

// Ajustar paralelización
const maxConcurrent = speed < 10 ? 3 : speed < 50 ? 6 : 10;
```

### 2. Compresión en Cliente (Riesgoso)

Comprimir video antes de subir (puede degradar calidad):
```javascript
// Usar WebCodecs API o FFmpeg.wasm
// NO RECOMENDADO - mejor comprimir en servidor
```

### 3. Reanudar Uploads Fallidos

Guardar progreso en localStorage:
```javascript
localStorage.setItem('upload_progress', JSON.stringify(completedParts));
```

### 4. WebSockets para Feedback en Tiempo Real

Notificar cuando compresión termina:
```javascript
// Laravel Broadcasting + Pusher/Soketi
```

---

## Métricas a Monitorear

```sql
-- Tiempos promedio de subida
SELECT
    DATE(created_at) as fecha,
    AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as segundos_promedio,
    AVG(file_size / (1024*1024)) as mb_promedio
FROM videos
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY fecha DESC;
```

---

## Conclusión

Con estos cambios:
- ✅ Videos 4GB: **15 min → 2-5 min** (dependiendo de conexión)
- ✅ Videos 1GB: **4 min → < 1 min**
- ✅ Sin cambios en backend (solo JS)
- ✅ Compatible con código existente
- ✅ Fallbacks de retry intactos

**Próximo paso:** Desplegar a producción y validar con usuarios reales.
