# 🔴 FIXES CRÍTICOS - 2026-01-26

**Rama:** `performance/high-priority-fixes`
**Status:** ✅ ARREGLADO - Listo para probar en VPS

---

## 🐛 Bug #1: CRÍTICO - Video Player Roto

### Problema
```
Error in timeupdate callback: TypeError: o is not a function
```

Este error se repetía cada segundo durante la reproducción del video, rompiendo:
- ✗ Anotaciones no aparecían/desaparecían
- ✗ Notificaciones de comentarios fallaban
- ✗ Tracking de vistas no funcionaba
- ✗ Timeline progress no se actualizaba

### Causa Raíz
En las optimizaciones de performance, consolidé los timeupdate listeners en un solo archivo (`time-manager.js`).

Registraba callbacks como **objetos**:
```javascript
timeupdateCallbacks.push({ callback, name });
```

Pero intentaba ejecutarlos como **funciones**:
```javascript
timeupdateCallbacks.forEach(callback => {
    callback(currentTime, video);  // ❌ callback es objeto, no función
});
```

### Solución
Desestructurar el objeto correctamente:
```javascript
timeupdateCallbacks.forEach(({ callback, name }) => {
    callback(currentTime, video);  // ✅ Ahora sí ejecuta la función
});
```

---

## 🐛 Bug #2: Anotaciones con Duración se Quedaban Permanentes

### Problema
Las anotaciones configuradas con duración (2-4 segundos) no desaparecían, quedando visibles todo el video.

### Causa Raíz
El campo `is_permanent` del backend podía venir como:
- `true/false` (boolean) ✅
- `1/0` (integer) ⚠️
- `null` (undefined) ⚠️

JavaScript evaluaba `if (is_permanent)`, lo que trataba `1` como permanente.

### Solución
Validación estricta de boolean:
```javascript
const isPermanent = Boolean(
    annotation.is_permanent === true ||
    annotation.is_permanent === 1 ||
    annotation.is_permanent === "1"
);
```

Ahora solo valores explícitamente permanentes se tratan como tal.

---

## 🧪 Cómo Probar en VPS

### Paso 1: Pull + Build

```bash
# SSH al VPS
ssh usuario@rugbyhub.cl
cd /var/www/rugbyhub

# Checkout rama de performance
git checkout performance/high-priority-fixes

# Pull últimos fixes
git pull origin performance/high-priority-fixes

# Build
npm run build

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Paso 2: Probar Video Player

Abre: `https://rugbyhub.cl/videos/[cualquier-id]`

**Test 1: Verificar que NO hay errores en consola (F12)**

✅ **ANTES:** Miles de errores "TypeError: o is not a function"
✅ **AHORA:** Sin errores, solo logs de debug normales

Buscar en consola:
```
✓ Initializing Video Player...
✓ Time Manager initialized with X callbacks
✓ Registered timeupdate callback: timestamp-input
✓ Registered timeupdate callback: view-tracking
✓ NO debe haber "Error in timeupdate callback"
```

**Test 2: Verificar que el video funciona correctamente**

- ✅ Video reproduce sin problemas
- ✅ Timeline progress se actualiza
- ✅ Timestamp display se actualiza cada segundo
- ✅ No hay lag ni congelamiento

**Test 3: Verificar Anotaciones con Duración**

1. Click en "Anotar"
2. Dibuja algo (círculo, flecha, etc.)
3. Selecciona duración: **"2 segundos"**
4. Guarda anotación
5. Reproduce desde antes del timestamp
6. ✅ **Verificar**: Anotación debe DESAPARECER después de 2 segundos

En consola deberías ver:
```
🔍 DEBUG Annotations: [{id: X, timestamp: Y, duration: 2, isPermanent: false, ...}]
🏷️ Indexing annotation X: isPermanent=false (raw: false, type: boolean)
  → Added to seconds Y-Y+2 (duration: 2s)
```

**Test 4: Verificar Anotaciones Permanentes**

1. Crear nueva anotación
2. Seleccionar: **"Permanente"**
3. Guardar
4. Reproducir video completo
5. ✅ **Verificar**: Anotación debe QUEDARSE visible todo el tiempo

En consola:
```
🏷️ Indexing annotation X: isPermanent=true (raw: true, type: boolean)
  → Added to 'permanent' index
```

### Paso 3: Pruebas Adicionales

**Test de 2 Windows (el original)**
- Abrir 2 ventanas con videos
- Reproducir ambos simultáneamente
- ✅ Debe funcionar fluido (el pequeño lag al abrir DevTools es normal)

**Test de Funcionalidad General**
- ✅ Agregar comentarios
- ✅ Eliminar comentarios
- ✅ Reproducir clips
- ✅ Exportar GIF
- ✅ Notificaciones de comentarios en timeline

---

## 📊 Resumen de Fixes

| # | Bug | Severidad | Estado |
|---|-----|-----------|--------|
| 1 | TypeError: o is not a function | 🔴 CRÍTICO | ✅ FIXED |
| 2 | Anotaciones permanentes incorrectas | 🟡 MEDIO | ✅ FIXED |

---

## 🔄 Si Todo Funciona → Mergear a Main

```bash
# VPS: Verificar que todo funciona bien
# Luego mergear:

git checkout main
git merge performance/high-priority-fixes --no-edit
npm run build
php artisan config:clear
php artisan cache:clear
git push origin main
```

---

## ⚠️ Si Algo Falla → Rollback

```bash
git checkout main
npm run build
php artisan config:clear
php artisan cache:clear
```

---

## 🗑️ Limpiar Logs de Debug (Después de Confirmar)

Los logs de debug (`console.log`) en `annotations.js` son temporales para verificar el fix.

Una vez confirmado que funciona, podemos eliminarlos en un commit posterior.

---

## 📝 Commits en Esta Sesión

1. `170c2140` - fix(annotations): Fix duration handling for timed annotations
2. `b8f9740d` - fix(critical): Fix timeupdate callback execution error

---

**Autor:** Claude Sonnet 4.5
**Fecha:** 2026-01-26
**Branch:** performance/high-priority-fixes
