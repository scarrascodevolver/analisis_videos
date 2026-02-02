# Batch Upload - Implementación Completa

## Fecha de implementación: 2026-02-02

## Resumen

Se ha completado la implementación de **Batch Upload** para subir múltiples videos simultáneamente en RugbyHub, incluyendo soporte completo para configuración multi-cámara.

---

## Funcionalidades Implementadas

### 1. UI/UX (Frontend)

#### Toggle Modo Upload
- Switch entre "Un Video" y "Múltiples Videos" en la parte superior de la página
- Muestra/oculta la interfaz correspondiente según la selección

#### Modo Batch - Selección de Videos
- Input file con atributo `multiple` para seleccionar varios archivos
- Zona de Drag & Drop para arrastrar múltiples archivos
- Lista de videos seleccionados mostrados en cards individuales

#### Por Cada Video Individual
- **Título**: Auto-generado desde el nombre del archivo (editable)
- **Ángulo de cámara**: Campo de texto libre (opcional)
- **Botón "Seleccionar XML"**: Para importar clips de LongoMatch (opcional)
- **Checkbox "Master"**: Para marcar como video master en configuración multi-cámara
- **Botón "Quitar"**: Para eliminar del batch antes de subir

#### Configuración Común (Se Aplica a Todos)
- Fecha del partido
- Equipo rival (opcional)
- Categoría (con división si es Adultas)
- Situación de rugby
- Visibilidad (pública/forwards/backs/específica)
- Jugadores asignados (si visibilidad = específica)
- Descripción general

#### Progress Tracking Durante Upload
Por cada video:
- Nombre del archivo con tamaño
- Barra de progreso individual
- Estado: "En cola" / "Subiendo" / "Subiendo parte X/Y" / "Finalizando" / "Completado" / "Error"
- Mensaje descriptivo del progreso

#### Limitaciones
- Máximo 10 videos por batch
- Solo un video puede ser Master
- XML solo se puede subir para el video Master
- Todos los títulos deben ser únicos y no vacíos

---

## 2. Arquitectura Técnica

### JavaScript - Clase `BatchUploadManager`

**Propiedades:**
```javascript
- videos: []              // Array de objetos de video
- maxConcurrent: 2        // Máximo 2 uploads simultáneos
- activeUploads: 0        // Contador de uploads activos
- uploadQueue: []         // Cola de uploads pendientes
- isUploading: false      // Estado de subida
```

**Métodos Principales:**
- `addVideos(files)`: Agregar archivos al batch
- `renderVideoCard(video)`: Renderizar card de video en el DOM
- `validateAll()`: Validar toda la configuración antes de subir
- `startUpload()`: Iniciar proceso de subida de todos los videos
- `uploadNext()`: Procesar siguiente video en la cola
- `uploadSingleVideo(video)`: Subir un video individual
- `uploadVideoSingle(video, formData)`: Upload directo para archivos <100MB
- `uploadVideoMultipart(video, formData)`: Upload multipart para archivos >100MB
- `generateGroupKey(commonData)`: Generar clave única para agrupar videos multi-cámara
- `checkCompletion()`: Verificar si todos los uploads terminaron

**Detección Automática Multi-Cámara:**
- Si un video está marcado como "Master", se muestra el campo "Ángulo de cámara" para todos los demás videos
- Al desmarcar Master, los campos de ángulo se ocultan
- Solo puede haber un Master por batch

---

## 3. Backend - Modificaciones

### Archivo: `app/Http/Controllers/DirectUploadController.php`

#### Campos Agregados a Validación

**Método `confirmUpload()` (línea ~127):**
```php
'is_master' => 'nullable|boolean',
'camera_angle' => 'nullable|string|max:255',
'group_key' => 'nullable|string|max:255',
```

**Método `completeMultipartUpload()` (línea ~495):**
```php
'is_master' => 'nullable|boolean',
'camera_angle' => 'nullable|string|max:255',
'group_key' => 'nullable|string|max:255',
```

#### Nuevo Método: `handleMultiCameraGroup()`

**Lógica:**
1. Si `is_master = true`: Crea un nuevo `VideoGroup` y asocia el video como master
2. Si `is_master = false`: Espera hasta 60 segundos a que se cree el grupo por el master, luego se asocia como slave
3. Usa cache de Laravel con clave `batch_upload_group_{group_key}` para coordinar la creación del grupo entre uploads concurrentes
4. Registra todos los eventos en el log

**Flujo:**
```
Video Master (is_master=true)
    └─> Crear VideoGroup
    └─> Attach master con is_master=true, camera_angle="Master"
    └─> Guardar group_id en cache

Video Slave (is_master=false)
    └─> Esperar hasta encontrar group_id en cache (max 60s)
    └─> Attach slave con is_master=false, camera_angle="{valor ingresado}"
```

---

## 4. Flujo Completo de Batch Upload

### Paso 1: Usuario Selecciona Videos
1. Usuario cambia a modo "Múltiples Videos"
2. Arrastra o selecciona 3 archivos de video
3. Se muestran 3 cards editables

### Paso 2: Configuración
1. Usuario edita títulos individuales
2. Marca uno como "Master"
3. Llena ángulo de cámara para los otros 2
4. Llena configuración común una sola vez
5. Click "Subir Todos los Videos"

### Paso 3: Validación
- Verifica que hay al menos 1 video
- Verifica que todos tienen título
- Verifica que solo hay 1 Master (si existe alguno)
- Verifica configuración común completa

### Paso 4: Upload Concurrente
1. Batch manager inicia uploads (máximo 2 simultáneos)
2. Por cada video:
   - Genera `group_key` único basado en fecha + rival + categoría + timestamp
   - Si archivo >100MB: usa multipart upload (chunks de 50MB)
   - Si archivo <100MB: usa upload directo
   - Muestra progreso individual en tiempo real

### Paso 5: Backend Procesamiento
1. Video se guarda en DigitalOcean Spaces
2. Se crea registro en tabla `videos`
3. Si hay `group_key`:
   - Master: Crea `VideoGroup` y se asocia
   - Slave: Espera group_id del master y se asocia
4. Se despacha job de compresión según estrategia de la organización
5. Se procesan asignaciones de jugadores
6. Se importa XML si existe

### Paso 6: Finalización
- Al completar todos: redirect a lista de videos
- Muestra mensaje: "Subida completada: X exitosos, Y con errores"

---

## 5. Archivos Modificados

### Frontend
- `resources/views/videos/create.blade.php`
  - Agregado toggle modo single/batch (líneas 15-38)
  - Agregada interfaz batch upload (líneas 387-588)
  - Implementada clase `BatchUploadManager` (líneas 1382-1960)
  - Agregados event handlers (líneas 1970-2050)
  - Agregados estilos CSS (líneas 2071-2260)

### Backend
- `app/Http/Controllers/DirectUploadController.php`
  - Agregados campos a validación en `confirmUpload()` (línea ~140)
  - Agregados campos a validación en `completeMultipartUpload()` (línea ~512)
  - Agregado método `handleMultiCameraGroup()` (línea ~820)
  - Integración en ambos métodos para llamar `handleMultiCameraGroup()`

---

## 6. Casos de Uso

### Caso 1: Subir 3 Videos Sin Multi-Cámara
1. Seleccionar 3 videos
2. No marcar ninguno como Master
3. Llenar configuración común
4. Subir
5. **Resultado**: 3 videos independientes sin agrupación

### Caso 2: Subir 3 Videos Con Multi-Cámara
1. Seleccionar 3 videos
2. Marcar 1 como Master (ej: "Tribuna Central")
3. Llenar ángulo de cámara para los otros 2 (ej: "End Zone", "Lateral")
4. Llenar configuración común
5. Subir
6. **Resultado**: 1 VideoGroup con 3 videos asociados, 1 master y 2 slaves

### Caso 3: Subir 1 Solo Video en Modo Batch
1. Seleccionar 1 video
2. Llenar configuración
3. Subir
4. **Resultado**: Funciona igual que modo single

### Caso 4: Error en Subida
1. Si un video falla, los demás continúan
2. Al finalizar se muestra: "Subida completada: 2 exitosos, 1 con errores"
3. Videos exitosos ya están guardados
4. Usuario puede reintentar los que fallaron

---

## 7. Consideraciones de Rendimiento

### Concurrencia
- **Máximo 2 uploads simultáneos** para evitar saturar el servidor VPS
- Queue automático para videos adicionales

### Multipart Upload
- Archivos >100MB usan multipart (chunks de 50MB)
- Uploads secuenciales por video para evitar overhead
- Progress tracking por parte

### Timeouts
- Upload simple: 60 segundos
- Multipart complete: 300 segundos (5 minutos)
- Coordinación grupo: 60 segundos max espera para master

---

## 8. Testing Recomendado

### Test Manual
1. **Batch simple**: Subir 3 videos sin master
2. **Multi-cámara**: Subir 3 videos con 1 master
3. **Archivos grandes**: Subir videos >100MB (verificar multipart)
4. **Error handling**: Cancelar navegador durante upload
5. **Límites**: Intentar subir 11 videos (debe rechazar)
6. **Validaciones**: Intentar subir con 2 masters (debe rechazar)

### Test Backend
- Verificar que VideoGroup se crea correctamente
- Verificar relaciones pivot en `video_group_video`
- Verificar que slaves esperan al master
- Verificar logs en `storage/logs/laravel.log`

---

## 9. Próximas Mejoras (Futuro)

### Funcionalidades Pendientes
- [ ] Auto-sincronización de videos multi-cámara
- [ ] Preview de videos antes de subir
- [ ] Reordenar videos en el batch (drag & drop)
- [ ] Pausar/reanudar uploads individuales
- [ ] Guardar configuración común como template
- [ ] Upload desde URLs externas
- [ ] Integración con Dropbox/Google Drive

### Optimizaciones
- [ ] Comprimir en cliente antes de subir (WebAssembly FFmpeg)
- [ ] Upload paralelo de chunks en multipart
- [ ] Retry automático en caso de error
- [ ] Estimación de tiempo restante más precisa

---

## 10. Troubleshooting

### Videos no se agrupan correctamente
- **Verificar**: Que uno esté marcado como Master
- **Verificar**: Que `group_key` sea el mismo para todos
- **Solución**: Revisar logs en `storage/logs/laravel.log`

### Upload se queda en "Finalizando"
- **Causa**: Timeout del servidor
- **Solución**: Aumentar timeout en DirectUploadController
- **Temporal**: Reducir tamaño de archivo o usar compresión previa

### Cache de grupo expira antes de que slave lo encuentre
- **Causa**: Upload master muy lento
- **Solución**: Aumentar TTL del cache en `handleMultiCameraGroup()`
- **Actual**: 30 minutos debería ser suficiente

### Solo se sube el primer video
- **Causa**: Error en `uploadNext()` que no llama recursivamente
- **Solución**: Verificar que el bloque `finally` llame a `uploadNext()`

---

## Documentado por Claude Code
**Branch**: `feature/upload-reliability-improvements`
**Commit**: Pending (implementación completa)
**Estado**: LISTO PARA TESTING
