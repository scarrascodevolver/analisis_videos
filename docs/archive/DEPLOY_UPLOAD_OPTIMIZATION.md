# 🚀 Despliegue de Optimización de Upload

**Fecha:** 2026-02-03
**Rama para desplegar:** `main` (producción)
**NO desplegar:** `feature/batch-video-upload` (aún no aprobado)

---

## ✅ Situación

He creado **dos commits separados**:

### 1. Commit en `main` (LISTO PARA DESPLEGAR)
```
commit 34707bc1
perf: Optimize multipart upload speed in production

Archivo modificado: resources/views/videos/create.blade.php
Cambios:
  - maxConcurrent: 2 → 10
  - chunkSize: 50MB → 100MB
```

Este commit optimiza el **upload actual en producción** sin agregar nuevas funcionalidades.

### 2. Commit en `feature/batch-video-upload` (NO DESPLEGAR TODAVÍA)
```
commit e20fa0e3
perf: Optimize multipart upload speed with increased parallelization

Archivo nuevo: public/js/batch-upload.js
Archivos docs: docs/UPLOAD_SPEED_OPTIMIZATION.md
```

Este commit es parte del **batch upload** que aún no está aprobado.

---

## 📋 Pasos para Desplegar en VPS

### 1. Conectar al VPS

```bash
ssh root@161.35.108.164
```

### 2. Navegar al directorio

```bash
cd /var/www/analisis_videos
```

### 3. Verificar rama actual (debe ser `main`)

```bash
git branch
```

**Debe mostrar:** `* main`

**Si no estás en main:**
```bash
git checkout main
```

### 4. Hacer pull SOLO de main

```bash
git pull origin main
```

**⚠️ IMPORTANTE:** NO ejecutes `git pull origin feature/batch-video-upload`

### 5. Verificar que el cambio se aplicó

```bash
grep "var maxConcurrent = 10" resources/views/videos/create.blade.php
```

**Debe mostrar:**
```javascript
var maxConcurrent = 10; // ✅ Increased from 2 to 10 for faster uploads (5x parallelization)
```

Si NO muestra nada o muestra `= 2`, el cambio no se aplicó correctamente.

### 6. Limpiar cache (opcional, no es necesario)

```bash
php artisan view:clear
php artisan cache:clear
```

**Nota:** Como el cambio es en un archivo Blade (no compilado), no es estrictamente necesario.

### 7. Verificar permisos (si hay problemas)

```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

---

## ✅ Validación

### Desde el Servidor

```bash
# Ver último commit
git log --oneline -1

# Debe mostrar:
# 34707bc1 perf: Optimize multipart upload speed in production
```

### Desde el Navegador

1. **Abrir en modo Incógnito** (Ctrl+Shift+N)
   - Esto asegura que no haya cache del navegador

2. **Ir a:** https://tu-dominio.com/videos/create

3. **Abrir consola del navegador** (F12)

4. **Subir un video de 500MB-1GB:**
   - Seleccionar video
   - Llenar formulario
   - Hacer clic en "Subir"

5. **Verificar en logs de consola:**
   ```
   Starting upload of part 1 (pending: X, in-progress: 1)
   Starting upload of part 2 (pending: X, in-progress: 2)
   ...
   Starting upload of part 10 (pending: X, in-progress: 10)
   ```

   **Antes mostraba:** `in-progress: 2` (máximo)
   **Ahora debe mostrar:** `in-progress: 10` (máximo)

6. **Cronometrar el tiempo total**

---

## 📊 Resultados Esperados

| Tamaño Video | Antes | Después | Mejora |
|--------------|-------|---------|--------|
| 500MB | 2 min | **< 1 min** | 50% |
| 1GB | 4 min | **1 min** | 75% |
| 2GB | 8 min | **2 min** | 75% |
| 4GB | 15 min | **3-5 min** | 66-80% |

\* Tiempos asumen conexión de ~100 Mbps upload

**Importante:** La velocidad real depende de la conexión del usuario, no del VPS.

---

## 🔍 Troubleshooting

### "No veo cambios en la velocidad"

**Verificar:**

1. **¿El cambio se aplicó?**
   ```bash
   grep "maxConcurrent = 10" /var/www/analisis_videos/resources/views/videos/create.blade.php
   ```

2. **¿Cache del navegador?**
   - Abrir en modo Incógnito (Ctrl+Shift+N)
   - O limpiar cache: Ctrl+Shift+Delete

3. **¿Conexión del usuario realmente es rápida?**
   - Ir a: https://www.speedtest.net/
   - Ver valor de "UPLOAD" (Mbps)
   - Si es < 50 Mbps, el upload seguirá siendo lento

### "Git dice que hay conflictos"

```bash
# Ver qué archivos tienen conflictos
git status

# Si hay archivos modificados localmente que no quieres
git stash

# Luego hacer pull
git pull origin main
```

### "Sigue mostrando maxConcurrent = 2"

Posibles causas:
- No hiciste pull correctamente
- Estás viendo un archivo de otra rama
- Cache del navegador

**Solución:**
```bash
cd /var/www/analisis_videos
git checkout main
git pull origin main
git log --oneline -1  # Debe mostrar: 34707bc1
```

---

## 🎯 Resumen

**Comando único para desplegar:**
```bash
cd /var/www/analisis_videos && git pull origin main
```

**Verificación única:**
```bash
grep "maxConcurrent = 10" resources/views/videos/create.blade.php
```

**Si muestra la línea con "10":** ✅ Desplegado correctamente

---

## 📝 Notas Adicionales

### ¿Por qué no desplegar feature/batch-video-upload?

Esa rama contiene:
- Nueva UI de batch upload (múltiples videos a la vez)
- Sistema de CRUD de rival teams
- Cambios en la estructura de vistas

**Todavía no está aprobado para producción.**

### ¿Cuándo desplegar el batch upload?

Cuando:
1. La funcionalidad esté probada completamente
2. El usuario apruebe los cambios de UI
3. Se valide que no rompe nada en producción

**Por ahora:** Solo desplegamos la optimización de velocidad al upload actual.

---

## 🆘 Si algo sale mal

**Rollback:**
```bash
cd /var/www/analisis_videos
git log --oneline -5  # Ver commits anteriores
git revert 34707bc1  # Revertir el cambio de optimización
```

Esto volverá a `maxConcurrent = 2` sin perder otros cambios.

---

**¿Listo para desplegar? Ejecuta:**
```bash
ssh root@161.35.108.164
cd /var/www/analisis_videos
git pull origin main
```

¡Y listo! 🎉
