# CHECKLIST RÁPIDO - Batch Upload Refactor

## Antes de testear

- [ ] Servidor Laravel corriendo (`php artisan serve` o XAMPP)
- [ ] Navegador moderno (Chrome/Firefox)
- [ ] DevTools abierto (F12)
- [ ] Usuario autenticado con organización activa

---

## Test 1: Carga Inicial (30 segundos)

### Acciones:
1. Navegar a `http://localhost/rugbyhub/videos/create`

### Verificar:
- [ ] Página carga sin errores
- [ ] Toggle "Un Video" / "Múltiples Videos" visible
- [ ] Modo "Un Video" seleccionado por defecto
- [ ] **Consola (F12):** Sin errores JavaScript
- [ ] **Network:** `batch-upload.js` cargado (200)
- [ ] **Network:** `sweetalert2` cargado (200)

**Status:** ✅ PASS / ❌ FAIL

---

## Test 2: Cambio de Modo (15 segundos)

### Acciones:
1. Click en botón "Múltiples Videos"

### Verificar:
- [ ] Formulario single upload se oculta
- [ ] Drop zone aparece
- [ ] Texto: "Arrastra videos aquí o haz click para seleccionar"
- [ ] Botón "Seleccionar Videos" visible
- [ ] **Consola:** Sin errores

**Status:** ✅ PASS / ❌ FAIL

---

## Test 3: Selección de Archivos (1 minuto)

### Acciones:
1. Click en "Seleccionar Videos"
2. Seleccionar 3 archivos de video (.mp4)

### Verificar:
- [ ] Sección "Videos Seleccionados" aparece
- [ ] Badge muestra "3" videos
- [ ] 3 cards aparecen en grilla
- [ ] Primer video tiene checkbox "Master" marcado
- [ ] Títulos auto-generados (sin extensión, espacios en vez de _)
- [ ] Tamaño de archivo visible (ej: "125.5 MB")
- [ ] **Consola:** Sin errores

**Status:** ✅ PASS / ❌ FAIL

---

## Test 4: Auto-detección Master (1 minuto)

### Acciones:
1. Limpiar todos los videos
2. Renombrar archivos de test:
   - `master_partido.mp4`
   - `lateral_camara.mp4`
   - `detras_view.mp4`
3. Seleccionar los 3 archivos

### Verificar:
- [ ] `master_partido.mp4` tiene checkbox "Master" marcado
- [ ] Otros 2 videos NO tienen checkbox marcado
- [ ] Card de master tiene borde dorado
- [ ] Indicador "Grupo Multi-Cámara Detectado" visible
- [ ] Badge Master: "master_partido.mp4"
- [ ] Badges Slaves: "lateral_camara.mp4", "detras_view.mp4"

**Status:** ✅ PASS / ❌ FAIL

---

## Test 5: Toggle Video Independiente (1 minuto)

### Acciones:
1. En el segundo video (slave), buscar toggle "Configuración"
2. Click en "Video independiente"

### Verificar:
- [ ] Campos adicionales aparecen inline (NO modal)
- [ ] Animación slideDown suave
- [ ] Fondo gris claro en campos
- [ ] Select "Equipo Rival" visible
- [ ] Input "Fecha" visible
- [ ] Select "Situación" visible
- [ ] Select "Visibilidad" visible

### Acciones:
3. Click en "Usar común"

### Verificar:
- [ ] Campos se ocultan con animación slideUp
- [ ] Fondo vuelve a normal
- [ ] **Consola:** Sin errores

**Status:** ✅ PASS / ❌ FAIL

---

## Test 6: Select2 Rival (1 minuto)

### Acciones:
1. En un video independiente, click en Select "Equipo Rival"
2. Escribir "Uru" en el campo de búsqueda

### Verificar:
- [ ] Dropdown Select2 aparece
- [ ] Muestra resultados de autocomplete
- [ ] Se puede escribir nuevo nombre
- [ ] Opción "(crear nuevo)" aparece

### Acciones:
3. Seleccionar "Uruguay (crear nuevo)"

### Verificar:
- [ ] Valor seleccionado aparece
- [ ] **Consola:** Sin errores
- [ ] **Network:** Request a `/api/rival-teams/autocomplete?q=Uru`

**Status:** ✅ PASS / ❌ FAIL

---

## Test 7: Validación de Formulario (1 minuto)

### Acciones:
1. Agregar 2 videos
2. NO completar "Fecha del Partido"
3. Click en "Subir Todos (2 videos)"

### Verificar:
- [ ] SweetAlert aparece
- [ ] Icono de error rojo
- [ ] Texto: "Por favor selecciona la fecha del partido"
- [ ] Formulario NO se envía
- [ ] Videos siguen en la grilla

### Acciones:
4. Cerrar SweetAlert
5. Completar fecha
6. NO completar "Categoría"
7. Click en "Subir Todos"

### Verificar:
- [ ] SweetAlert con error de categoría
- [ ] Formulario NO se envía

**Status:** ✅ PASS / ❌ FAIL

---

## Test 8: Drag & Drop (1 minuto)

### Acciones:
1. Limpiar todos los videos
2. Arrastrar 2 archivos desde explorador de archivos al drop zone

### Verificar:
- [ ] Drop zone cambia de color al arrastrar (borde sólido)
- [ ] Drop zone vuelve a normal al soltar
- [ ] 2 videos aparecen en grilla
- [ ] Primer video marcado como master
- [ ] **Consola:** Sin errores

**Status:** ✅ PASS / ❌ FAIL

---

## Test 9: Responsive (2 minutos)

### Acciones:
1. Agregar 6 videos
2. DevTools (F12) > Toggle Device Toolbar
3. Seleccionar "Responsive" y ajustar ancho

### Verificar Desktop (1200px):
- [ ] 3 columnas de cards (2 filas de 3)

### Verificar Tablet (768px):
- [ ] 2 columnas de cards (3 filas de 2)

### Verificar Mobile (375px):
- [ ] 1 columna de cards (6 filas)
- [ ] Todos los elementos legibles
- [ ] Sin overflow horizontal

**Status:** ✅ PASS / ❌ FAIL

---

## Test 10: Quitar Videos (30 segundos)

### Acciones:
1. Agregar 3 videos
2. Click en botón "×" (cerrar) del segundo video

### Verificar:
- [ ] Video desaparece con fadeOut
- [ ] Badge contador actualiza a "2"
- [ ] Grilla se reorganiza

### Acciones:
3. Click en "Limpiar Todo"

### Verificar:
- [ ] Confirmación "¿Estás seguro de quitar todos los videos?"
- [ ] Click en "Aceptar"
- [ ] Todos los videos desaparecen
- [ ] Sección "Videos Seleccionados" se oculta
- [ ] Badge contador en "0"

**Status:** ✅ PASS / ❌ FAIL

---

## Test 11: Modo Single Upload (1 minuto)

### Acciones:
1. Click en toggle "Un Video"
2. Verificar formulario single upload

### Verificar:
- [ ] Formulario single visible
- [ ] Batch upload oculto
- [ ] Select2 de rival funciona
- [ ] File input funcional
- [ ] Todos los campos presentes

**Status:** ✅ PASS / ❌ FAIL

---

## RESULTADO FINAL

### Resumen
```
Tests Passed:    ____ / 11
Tests Failed:    ____ / 11
Success Rate:    _____%
```

### Errores Encontrados
1. _______________________________________
2. _______________________________________
3. _______________________________________

### Notas Adicionales
```
_______________________________________________
_______________________________________________
_______________________________________________
```

---

## GO / NO-GO

### Si 11/11 tests PASS:
✅ **GO** - Listo para commit y merge

### Si 9-10/11 tests PASS:
⚠️ **CAUTION** - Revisar errores menores antes de merge

### Si <9/11 tests PASS:
❌ **NO-GO** - Corregir errores críticos antes de continuar

---

## Comandos Post-Test

### Si GO:
```bash
git add .
git commit -m "feat: Refactor batch upload with grid UI and inline toggle"
git push origin feature/upload-reliability-improvements
```

### Si NO-GO:
```bash
# Revisar errores en consola
# Verificar logs de Laravel
tail -f storage/logs/laravel.log

# Debug JavaScript
console.log('BatchUploadConfig:', window.BatchUploadConfig);
console.log('batchManager:', window.batchManager);
```

---

*Checklist creado: 2026-02-02*
*Estimado: 10-15 minutos para completar todos los tests*
