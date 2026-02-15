# 🚀 Instrucciones de Deployment a VPS

**Última actualización:** 2026-02-04
**VPS Actual:** DigitalOcean (2 vCPU, 4GB RAM)
**Rama de producción:** `main`

---

## 📋 Deployment Estándar (Cualquier Cambio)

Esta guía sirve para deployar cualquier cambio desde `main` al VPS de producción.

---

## 🖥️ COMANDOS PARA VPS

### Paso 0: Pre-verificación Local

```bash
# Antes de hacer deploy, asegúrate de que:
# 1. Los cambios están en main
git branch
# Debe mostrar: * main

# 2. Todo está commiteado
git status
# Debe mostrar: "nothing to commit, working tree clean"

# 3. Push a GitHub
git push origin main
```

---

### Paso 1: Conectar y Backup

```bash
# Conectar al VPS
ssh root@161.35.108.164

# Navegar al proyecto
cd /var/www/analisis_videos

# Crear backup de seguridad (opcional pero recomendado)
git branch backup-$(date +%Y%m%d-%H%M%S)

# Verificar rama actual
git branch
# Debe mostrar: * main (u optimize/vps-2cpu-4gb si aún usas esa)
```

---

### Paso 2: Pull y Actualizar

```bash
# Pull de main con todos los cambios
git pull origin main

# Si hay cambios en composer.json
composer install --no-dev --optimize-autoloader

# Si hay cambios en package.json
npm install

# Si hay cambios en JS/CSS (SIEMPRE recomendado)
npm run build
```

---

### Paso 3: Migraciones y Cache

```bash
# Ejecutar migraciones (si hay nuevas)
php artisan migrate --force

# Limpiar cache de Laravel
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Verificar permisos de storage (si hay errores de permisos)
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

---

### Paso 4: Reiniciar Servicios (si es necesario)

```bash
# Si hay cambios en queue workers
sudo supervisorctl restart rugby-queue-worker:*

# Si hay cambios en configuración de Nginx
sudo nginx -t
sudo systemctl reload nginx

# Si hay cambios en PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

### Paso 5: Verificar Deployment

```bash
# Ver últimos commits
git log --oneline -5

# Verificar estado de servicios críticos
sudo supervisorctl status

# Ver logs recientes
tail -50 storage/logs/laravel.log
```

---

## ✅ Testing en Producción

### Pruebas Básicas (SIEMPRE)

1. **Login funciona**
   - https://tu-dominio.com/login
   - Probar con usuario de prueba

2. **Dashboard carga**
   - Sin errores 500
   - Sin errores en consola (F12)

3. **Funcionalidad principal**
   - Videos reproducen
   - Upload funciona
   - Comentarios funcionan

### Pruebas Específicas (según el cambio)

**Si modificaste JS/CSS:**
- Hard refresh (Ctrl+Shift+R)
- Verificar que assets nuevos se cargaron
- Revisar consola por errores

**Si modificaste compresión/queue:**
- Subir video de prueba
- Verificar logs: `tail -f storage/logs/queue-worker.log`
- Confirmar que comprime correctamente

**Si modificaste base de datos:**
- Verificar migraciones: `php artisan migrate:status`
- Probar funcionalidad afectada

---

## 🐛 Troubleshooting

### Problema: "Cambios no se reflejan en el sitio"

**Causas comunes:**
1. Cache del navegador
2. Cache de Laravel
3. Assets no compilados
4. Cambios no pushed a GitHub

**Solución:**
```bash
# En VPS
git pull origin main
npm run build
php artisan config:clear
php artisan cache:clear

# En navegador
# Hard refresh: Ctrl+Shift+R (Chrome/Firefox)
```

---

### Problema: "Error 500 después de deployment"

**Verificar:**
```bash
# Ver logs de error
tail -50 storage/logs/laravel.log

# Permisos de storage
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/

# .env configurado correctamente
cat .env | grep -E "APP_ENV|APP_DEBUG|DB_"

# Caché de configuración corrupta
php artisan config:clear
```

---

### Problema: "Queue workers no procesan jobs"

**Verificar:**
```bash
# Estado de workers
sudo supervisorctl status

# Si están detenidos, reiniciar
sudo supervisorctl restart rugby-queue-worker:*

# Ver logs
sudo supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00

# Verificar jobs en BD
php artisan tinker --execute="DB::table('jobs')->count()"
```

---

### Problema: "Migraciones fallan"

**Verificar:**
```bash
# Ver estado de migraciones
php artisan migrate:status

# Ver error específico
php artisan migrate --force

# Si necesitas rollback
php artisan migrate:rollback --step=1
```

---

## 🔄 Rollback (Si Algo Sale Mal)

### Opción A: Volver a Commit Anterior

```bash
# Ver commits recientes
git log --oneline -10

# Volver a commit específico
git reset --hard [commit-hash-anterior]

# Rebuild
npm run build
php artisan config:clear
php artisan cache:clear

# Reiniciar servicios
sudo supervisorctl restart rugby-queue-worker:*
```

### Opción B: Usar Backup de Git

```bash
# Listar backups
git branch | grep backup

# Volver a backup
git checkout backup-[fecha]

# Rebuild y limpiar
npm run build
php artisan config:clear
```

---

## 📋 Checklist de Deployment

Antes de dar por completado:

- [ ] `git pull origin main` ejecutado
- [ ] Dependencias actualizadas (`composer install`, `npm install`)
- [ ] `npm run build` ejecutado (si hay cambios JS/CSS)
- [ ] Migraciones ejecutadas (si hay nuevas)
- [ ] Cache limpiado
- [ ] Servicios reiniciados (si es necesario)
- [ ] Login funciona
- [ ] Dashboard carga sin errores
- [ ] Funcionalidad principal probada
- [ ] Sin errores en logs (`storage/logs/laravel.log`)
- [ ] Sin errores en consola del navegador (F12)

---

## 📊 Monitoreo Post-Deployment

### Logs a Revisar

```bash
# Logs de aplicación
tail -f storage/logs/laravel.log

# Logs de queue workers
sudo supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00

# Logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Logs de PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log
```

### Métricas de Sistema

```bash
# Uso de recursos
htop

# Espacio en disco
df -h

# Estado de servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo supervisorctl status
```

---

## 📚 Documentación Relacionada

- **Migración a Hetzner:** Ver `MIGRATION_HETZNER.md`
- **Optimización VPS:** Ver `docs/VPS_OPTIMIZATION.md`
- **Documentación del proyecto:** Ver `CLAUDE.md`
- **Archivos históricos:** Ver `docs/archive/`

---

## 🚀 Deployment Rápido (Resumen)

**Comando único para deployment estándar:**

```bash
ssh root@161.35.108.164 "cd /var/www/analisis_videos && git pull origin main && npm run build && php artisan config:clear && php artisan cache:clear && sudo supervisorctl restart rugby-queue-worker:*"
```

**Verificación rápida:**
- Abrir https://tu-dominio.com
- Login funciona ✅
- Dashboard carga ✅
- Sin errores en consola (F12) ✅

---

**Autor:** Claude Sonnet 4.5
**Última actualización:** 2026-02-04
