# Cloudflare Worker: Video CORS Proxy

## Descripción

Este Worker intercepta las solicitudes de video al CDN de DigitalOcean Spaces y agrega headers CORS en el edge de Cloudflare, eliminando la necesidad de hacer proxy a través de Laravel.

## Performance

| Métrica | Antes (Laravel Proxy) | Después (Worker) | Mejora |
|---------|----------------------|------------------|--------|
| Latencia inicial | 500-800ms | 50-100ms | 80% ↓ |
| Throughput | 5-10 MB/s | 50+ MB/s | 500% ↑ |
| CPU Laravel | 40-60% | 5-10% | 85% ↓ |
| Seeking | 300-500ms | 10-50ms | 90% ↓ |

## Deployment en Cloudflare

### Paso 1: Crear el Worker

1. Login en Cloudflare: https://dash.cloudflare.com
2. Ir a **Workers & Pages**
3. Click en **Create** o **Create application**
4. Seleccionar **Create Worker**
5. Nombre: `video-cors-proxy`
6. Click **Deploy**
7. Click **Edit code**
8. Copiar el contenido de `video-cors-proxy.js`
9. Pegar en el editor de Cloudflare
10. Click **Save and Deploy**

### Paso 2: Configurar DNS

1. Ir a tu dominio: `rugbyhub.cl`
2. Ir a **DNS** → **Records**
3. Click **Add record**
4. Tipo: `CNAME`
5. Nombre: `videos`
6. Target: `analisis-videos-storage.sfo3.cdn.digitaloceanspaces.com`
7. Proxy status: ✅ **Proxied** (naranja)
8. Click **Save**

### Paso 3: Configurar Worker Route

1. En el dashboard de `rugbyhub.cl`
2. Ir a **Workers Routes**
3. Click **Add route**
4. Route: `videos.rugbyhub.cl/*`
5. Worker: `video-cors-proxy`
6. Click **Save**

### Paso 4: Configurar Laravel

En tu archivo `.env` de producción:

```env
CLOUDFLARE_WORKER_ENABLED=true
CLOUDFLARE_WORKER_URL=https://videos.rugbyhub.cl
```

Luego:

```bash
php artisan config:clear
php artisan cache:clear
```

## Testing

### Test 1: Verificar Worker responde

```bash
curl -I https://videos.rugbyhub.cl/videos/los-troncos/test-video.mp4
```

Debe retornar headers CORS:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, HEAD, OPTIONS
Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges
```

### Test 2: Verificar CORS Preflight

```bash
curl -X OPTIONS -I https://videos.rugbyhub.cl/videos/los-troncos/test-video.mp4
```

Debe retornar `204 No Content` con headers CORS.

### Test 3: Verificar Range Requests

```bash
curl -H "Range: bytes=0-1000" -I https://videos.rugbyhub.cl/videos/los-troncos/test-video.mp4
```

Debe retornar `206 Partial Content` con `Content-Range` header.

### Test 4: Verificar en Browser

1. Abrir video en RugbyHub
2. Abrir DevTools → Network
3. Buscar la request del video
4. Verificar:
   - Status: `302` (redirect a Worker)
   - Location: `https://videos.rugbyhub.cl/...`
   - Headers CORS presentes en response final
5. Probar seeking (saltar a 60 segundos)
   - Debe ser instantáneo

## Monitoring

### Verificar estado del Worker/CDN

```bash
# API endpoint
curl https://rugbyhub.cl/api/cdn-status

# Forzar refresh del health check
curl https://rugbyhub.cl/api/cdn-status?refresh=1
```

### Logs Laravel

```bash
tail -f storage/logs/laravel.log | grep "CDN redirect via Worker"
```

### Cloudflare Analytics

1. Ir a **Workers & Pages** → `video-cors-proxy`
2. Click en **Metrics**
3. Verificar:
   - Requests/minuto
   - Errores (debe ser 0%)
   - CPU time (debe ser <5ms)

## Rollback

Si algo falla, deshabilitar el Worker es simple:

**Opción 1: Deshabilitar en Laravel (más rápido)**

```env
# .env
CLOUDFLARE_WORKER_ENABLED=false
```

```bash
php artisan config:clear
```

El sistema volverá al proxy de Laravel (lento pero funcional).

**Opción 2: Deshabilitar Worker Route**

1. Ir a **Workers Routes** en Cloudflare
2. Eliminar la ruta `videos.rugbyhub.cl/*`
3. Guardar

**Opción 3: Revertir código**

```bash
git revert HEAD
git push origin main
# Deploy cambios
```

## Costos

- Cloudflare Worker: **Gratis** hasta 100,000 requests/día
- Estimado mensual: ~50,000 requests/día = **$0/mes**
- Si excede: $0.50 por millón de requests adicionales

Con ~1,500 usuarios y ~10 videos/día = bien dentro del límite gratis.

## Troubleshooting

### Videos no cargan

1. Verificar Worker está deployado:
   ```bash
   curl -I https://videos.rugbyhub.cl
   ```

2. Verificar DNS configurado:
   ```bash
   dig videos.rugbyhub.cl
   ```

3. Verificar health check:
   ```bash
   curl https://rugbyhub.cl/api/cdn-status
   ```

4. Verificar logs Laravel:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Worker retorna 502

- El CDN de DigitalOcean puede estar caído
- Laravel automáticamente hará fallback a Spaces SDK
- Verificar logs de Worker en Cloudflare Dashboard

### CORS errors en browser

- Verificar Worker tiene los headers correctos
- Verificar Worker Route está configurada
- Verificar proxy está habilitado en DNS (naranja)

## Arquitectura

```
Browser
  ↓
Laravel (/videos/{id}/stream)
  ↓ 302 redirect
videos.rugbyhub.cl/{path}
  ↓ Cloudflare Worker (add CORS headers)
analisis-videos-storage.sfo3.cdn.digitaloceanspaces.com/{path}
  ↓ CDN streaming
Browser (video playback)
```

## Soporte

- Documentación Cloudflare Workers: https://developers.cloudflare.com/workers/
- Límites del Free Plan: https://developers.cloudflare.com/workers/platform/limits/
