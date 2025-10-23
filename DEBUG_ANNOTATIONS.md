# 🔍 GUÍA DE DEBUGGING - ELIMINACIÓN DE ANOTACIONES

## Síntomas del Problema:
1. ✅ Primera anotación se elimina correctamente
2. ❌ Anotaciones subsiguientes NO se pueden eliminar
3. ❌ Consola muestra anotaciones que ya fueron eliminadas

---

## 🐛 Problemas Identificados:

### 1. **CONDICIÓN DE CARRERA EN currentDisplayedAnnotations**
- `deleteAnnotation()` modifica el array directamente (línea 1422-1441)
- `checkAndShowAnnotations()` lo SOBRESCRIBE completamente (línea 1933)
- `timeupdate` event interfiere disparando checkAndShowAnnotations() continuamente

### 2. **DOBLE ACTUALIZACIÓN DEL ESTADO**
```javascript
// deleteAnnotation() línea 1425-1427
currentDisplayedAnnotations.splice(displayIndex, 1);

// checkAndShowAnnotations() línea 1933
currentDisplayedAnnotations = activeAnnotations; // ❌ Sobrescribe
```

### 3. **setTimeout NO PREVIENE RACE CONDITION**
```javascript
// Línea 1457-1459
setTimeout(() => {
    checkAndShowAnnotations(); // Puede ejecutarse DESPUÉS de otro timeupdate
}, 100);
```

---

## 🧪 PASOS DE DEBUGGING:

### PASO 1: Abrir la consola y ejecutar estos comandos

#### A. Verificar estado de arrays:
```javascript
// Ejecuta en consola mientras ves el video
console.log('📦 savedAnnotations:', window.savedAnnotations);
console.log('🎯 currentDisplayedAnnotations:', window.currentDisplayedAnnotations);
console.log('📊 Counts:', window.savedAnnotations?.length, window.currentDisplayedAnnotations?.length);
```

#### B. Monitorear eliminación en tiempo real:
```javascript
// Ejecuta ANTES de eliminar una anotación
window.debugDeleteMode = true;
```

#### C. Ver evento timeupdate:
```javascript
// Ejecuta para ver cuántas veces se dispara
let timeupdateCount = 0;
document.querySelector('#videoPlayer').addEventListener('timeupdate', () => {
    timeupdateCount++;
    if (timeupdateCount % 10 === 0) {
        console.log(`⏱️ timeupdate #${timeupdateCount}, time: ${document.querySelector('#videoPlayer').currentTime.toFixed(2)}s`);
    }
});
```

---

### PASO 2: Aplicar logs mejorados al código

Reemplaza la función `deleteAnnotation()` (línea 1394) con esta versión con logs:

```javascript
function deleteAnnotation(annotationId) {
    if (!confirm('¿Estás seguro de eliminar esta anotación?')) {
        return;
    }

    console.log('🗑️ ===== INICIO ELIMINACIÓN =====');
    console.log('🎯 ID a eliminar:', annotationId);
    console.log('📦 savedAnnotations ANTES:', savedAnnotations.length, [...savedAnnotations.map(a => a.id)]);
    console.log('🎨 currentDisplayedAnnotations ANTES:', currentDisplayedAnnotations.length, [...currentDisplayedAnnotations.map(a => a.id)]);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.ajax({
        url: `/api/annotations/${annotationId}`,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                console.log('✅ Servidor confirmó eliminación:', annotationId);

                // 1. Remover de savedAnnotations
                const index = savedAnnotations.findIndex(a => a.id == annotationId);
                console.log('🔍 Index en savedAnnotations:', index);

                if (index !== -1) {
                    savedAnnotations.splice(index, 1);
                    window.savedAnnotations = savedAnnotations;
                    console.log('✅ Removida de savedAnnotations, quedan:', savedAnnotations.length, savedAnnotations.map(a => a.id));
                } else {
                    console.warn('⚠️ NO ENCONTRADA en savedAnnotations!');
                }

                // 2. Remover de currentDisplayedAnnotations
                const displayIndex = currentDisplayedAnnotations.findIndex(a => a.id == annotationId);
                console.log('🔍 Index en currentDisplayedAnnotations:', displayIndex);

                if (displayIndex !== -1) {
                    console.log('📦 currentDisplayedAnnotations ANTES de splice:', [...currentDisplayedAnnotations.map(a => a.id)]);
                    currentDisplayedAnnotations.splice(displayIndex, 1);
                    window.currentDisplayedAnnotations = currentDisplayedAnnotations;
                    console.log('✅ Removida de currentDisplayedAnnotations, quedan:', currentDisplayedAnnotations.length, currentDisplayedAnnotations.map(a => a.id));

                    if (currentDisplayedAnnotations.length > 0) {
                        console.log('♻️ Redesplegando anotaciones restantes:', currentDisplayedAnnotations.length);
                        displayMultipleAnnotations(currentDisplayedAnnotations);
                    } else {
                        console.log('🧹 No quedan anotaciones, limpiando canvas');
                        clearDisplayedAnnotation();
                        const deleteBtn = document.getElementById('deleteAnnotationBtn');
                        if (deleteBtn) {
                            deleteBtn.style.display = 'none';
                        }
                    }
                } else {
                    console.warn('⚠️ NO ENCONTRADA en currentDisplayedAnnotations!');
                }

                // 3. Actualizar lista en sidebar
                console.log('📝 Actualizando lista del sidebar...');
                renderAnnotationsList();

                // 4. Mostrar mensaje
                if (typeof toastr !== 'undefined') {
                    toastr.success('Anotación eliminada exitosamente');
                }

                // 5. CRÍTICO: Forzar recalculo
                console.log('⏱️ Programando checkAndShowAnnotations en 100ms...');
                setTimeout(() => {
                    console.log('🔄 Ejecutando checkAndShowAnnotations después de eliminación');
                    console.log('📦 savedAnnotations en setTimeout:', savedAnnotations.length, savedAnnotations.map(a => a.id));
                    console.log('🎨 currentDisplayedAnnotations en setTimeout:', currentDisplayedAnnotations.length, currentDisplayedAnnotations.map(a => a.id));
                    checkAndShowAnnotations();
                    console.log('🎨 currentDisplayedAnnotations DESPUÉS de check:', currentDisplayedAnnotations.length, currentDisplayedAnnotations.map(a => a.id));
                }, 100);

                console.log('🗑️ ===== FIN ELIMINACIÓN (success callback) =====');
            }
        },
        error: function(xhr) {
            console.error('❌ ===== ERROR EN ELIMINACIÓN =====');
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);

            if (xhr.status === 500 || xhr.status === 404) {
                console.log('⚠️ Error 500/404, recargando desde servidor...');
                loadExistingAnnotations();
                if (typeof toastr !== 'undefined') {
                    toastr.warning('La anotación ya no existe. Lista actualizada.');
                }
            } else if (xhr.status === 403) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('No tienes permisos para eliminar esta anotación');
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error al eliminar la anotación');
                }
            }
        }
    });
}
```

---

### PASO 3: Agregar logs a checkAndShowAnnotations()

Reemplaza `checkAndShowAnnotations()` (línea 1903) con:

```javascript
function checkAndShowAnnotations() {
    if (annotationMode || !fabricCanvas) {
        return;
    }

    if (hasTemporaryDrawing) {
        return;
    }

    const currentTime = video.currentTime;

    console.log('🔄 checkAndShowAnnotations() - tiempo:', currentTime.toFixed(2) + 's');
    console.log('📦 savedAnnotations disponibles:', savedAnnotations.length, savedAnnotations.map(a => `${a.id}@${a.timestamp}s`));

    const activeAnnotations = savedAnnotations.filter(annotation => {
        const startTime = parseFloat(annotation.timestamp);
        const durationSeconds = parseInt(annotation.duration_seconds) || 4;
        const endTime = annotation.is_permanent ? Infinity : startTime + durationSeconds;

        const isActive = currentTime >= startTime && currentTime <= endTime;

        if (isActive) {
            console.log(`✅ Anotación ${annotation.id} ACTIVA (${startTime}-${endTime})`);
        }

        return isActive;
    });

    console.log('🎯 activeAnnotations encontradas:', activeAnnotations.length, activeAnnotations.map(a => a.id));

    const activeIds = activeAnnotations.map(a => a.id).sort().join(',');
    const displayedIds = currentDisplayedAnnotations.map(a => a.id).sort().join(',');

    console.log('🔍 Comparación - Active IDs:', activeIds, 'vs Displayed IDs:', displayedIds);

    if (activeIds !== displayedIds) {
        console.log('⚡ CAMBIO DETECTADO en anotaciones activas');

        if (activeAnnotations.length > 0) {
            console.log('📺 Desplegando', activeAnnotations.length, 'anotaciones');
            displayMultipleAnnotations(activeAnnotations);

            // ⚠️ LÍNEA CRÍTICA - Aquí se SOBRESCRIBE currentDisplayedAnnotations
            console.log('🎨 ANTES de asignar - currentDisplayedAnnotations:', currentDisplayedAnnotations.map(a => a.id));
            currentDisplayedAnnotations = activeAnnotations;
            console.log('🎨 DESPUÉS de asignar - currentDisplayedAnnotations:', currentDisplayedAnnotations.map(a => a.id));

            const deleteBtn = document.getElementById('deleteAnnotationBtn');
            if (deleteBtn) {
                deleteBtn.style.display = 'block';

                if (activeAnnotations.length === 1) {
                    deleteBtn.setAttribute('data-annotation-id', activeAnnotations[0].id);
                    deleteBtn.innerHTML = '<i class="fas fa-times-circle"></i> Eliminar Anotación';
                    console.log('🔘 Botón eliminar configurado para ID:', activeAnnotations[0].id);
                } else {
                    deleteBtn.removeAttribute('data-annotation-id');
                    deleteBtn.innerHTML = `<i class="fas fa-times-circle"></i> ${activeAnnotations.length} Anotaciones`;
                    console.log('🔘 Botón eliminar configurado para múltiples:', activeAnnotations.length);
                }
            }
        } else {
            console.log('🧹 No hay anotaciones activas, limpiando...');
            clearDisplayedAnnotation();
            currentDisplayedAnnotations = [];

            const deleteBtn = document.getElementById('deleteAnnotationBtn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
                deleteBtn.removeAttribute('data-annotation-id');
            }
        }
    } else {
        console.log('✅ No hay cambios en anotaciones activas');
    }
}
```

---

## 📋 QUÉ BUSCAR EN LOS LOGS:

### ✅ Funcionamiento CORRECTO:
```
🗑️ ===== INICIO ELIMINACIÓN =====
🎯 ID a eliminar: 123
📦 savedAnnotations ANTES: 3 [123, 124, 125]
✅ Removida de savedAnnotations, quedan: 2 [124, 125]
✅ Removida de currentDisplayedAnnotations, quedan: 2 [124, 125]
📝 Actualizando lista del sidebar...
⏱️ Programando checkAndShowAnnotations en 100ms...
🗑️ ===== FIN ELIMINACIÓN =====
🔄 checkAndShowAnnotations() - tiempo: 10.50s
📦 savedAnnotations disponibles: 2 [124@11s, 125@12s]
🎯 activeAnnotations encontradas: 2 [124, 125]
🎨 currentDisplayedAnnotations: 2 [124, 125]
```

### ❌ PROBLEMA (Race Condition):
```
🗑️ ===== INICIO ELIMINACIÓN =====
🎯 ID a eliminar: 123
✅ Removida de savedAnnotations, quedan: 2 [124, 125]
✅ Removida de currentDisplayedAnnotations, quedan: 2 [124, 125]
🔄 checkAndShowAnnotations() - tiempo: 10.50s  ⚠️ LLAMADO POR timeupdate ANTES del setTimeout
📦 savedAnnotations disponibles: 3 [123, 124, 125]  ⚠️ TODAVÍA TIENE LA VIEJA!
🎯 activeAnnotations encontradas: 3 [123, 124, 125]
🎨 currentDisplayedAnnotations: 3 [123, 124, 125]  ⚠️ SOBRESCRIBIÓ EL ARRAY!
🔄 Ejecutando checkAndShowAnnotations después de eliminación
📦 savedAnnotations en setTimeout: 2 [124, 125]
🎨 currentDisplayedAnnotations: 3 [123, 124, 125]  ⚠️ YA ESTÁ MAL!
```

---

## 🛠️ SOLUCIÓN PROPUESTA (si se confirma el problema):

### Opción 1: DESHABILITAR timeupdate durante eliminación
```javascript
let isDeletingAnnotation = false;

// En timeupdate listener:
video.addEventListener('timeupdate', function() {
    if (isDeletingAnnotation) {
        console.log('⏸️ timeupdate bloqueado durante eliminación');
        return;
    }
    checkAndShowAnnotations();
});

// En deleteAnnotation():
isDeletingAnnotation = true;
$.ajax({
    success: function() {
        // ... código de eliminación ...
        setTimeout(() => {
            checkAndShowAnnotations();
            isDeletingAnnotation = false; // Rehabilitar
        }, 100);
    }
});
```

### Opción 2: NO MODIFICAR currentDisplayedAnnotations en deleteAnnotation()
```javascript
// En deleteAnnotation() - ELIMINAR líneas 1422-1441
// Dejar que SOLO checkAndShowAnnotations() maneje currentDisplayedAnnotations

// Solo remover de savedAnnotations:
const index = savedAnnotations.findIndex(a => a.id == annotationId);
if (index !== -1) {
    savedAnnotations.splice(index, 1);
}

// Actualizar sidebar
renderAnnotationsList();

// Dejar que checkAndShowAnnotations haga el resto
checkAndShowAnnotations();
```

### Opción 3: FORZAR RECARGA COMPLETA
```javascript
// En deleteAnnotation() success:
loadExistingAnnotations(); // Recargar desde servidor
setTimeout(() => {
    checkAndShowAnnotations();
}, 200);
```

---

## 🎯 PLAN DE ACCIÓN:

1. **Aplicar los logs mejorados** a `show.blade.php`
2. **Subir a producción** (https://clublostroncos.cl)
3. **Reproducir el bug** creando 2-3 anotaciones y eliminándolas
4. **Copiar los logs de consola** completos
5. **Analizar qué opción de solución aplicar**

¿Quieres que aplique los logs mejorados ahora?
