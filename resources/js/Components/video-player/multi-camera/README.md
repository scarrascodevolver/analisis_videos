# Multi-Camera System Components

Sistema de multi-cámara para reproducción sincronizada de múltiples ángulos de video.

## Arquitectura

### Composable: `useMultiCamera.ts`
**Responsabilidad**: Lógica central de sincronización de videos

**Características**:
- Crea elementos `<video>` dinámicamente para cada slave video
- Sincroniza play/pause/seek/rate entre master y slaves
- Aplica `sync_offset` individual a cada slave
- Tolerancia de drift: ±0.3s antes de re-sincronizar
- Cleanup automático con AbortController

**API**:
```typescript
const {
  isLoaded,           // Ref<boolean> - todos los slaves cargados
  slaveElements,      // Ref<SlaveVideoElement[]> - elementos con estado
  swapMaster,         // (slaveId) => SlaveVideo | null
  adjustSyncOffset,   // (slaveId, offset) => void
  getSyncStatus,      // (slaveId) => 'synced' | 'syncing' | 'out-of-sync'
  syncAllSlaves,      // () => void
} = useMultiCamera({
  masterVideoRef: Ref<HTMLVideoElement | null>,
  slaveVideos: Ref<SlaveVideo[]>,
  syncTolerance?: number  // default: 0.3s
});
```

---

## Componentes

### 1. `MultiCameraLayout.vue`
**Layout principal** que organiza master y slaves

**Props**:
- `getSyncStatus: (slaveId: number) => SyncStatus`

**Slots**:
- `master` - Contenedor del video principal

**Emits**:
- `swapMaster: (slaveId: number)` - Usuario clickea slave para intercambiar

**Comportamiento**:
- Solo se muestra si `video.is_part_of_group` o tiene `slave_videos`
- Grid responsive: 1 columna mobile, auto-fit desktop (min 280px)

---

### 2. `SlaveVideo.vue`
**Tarjeta individual** de cada video slave

**Props**:
- `slave: SlaveVideo` - Datos del video
- `syncStatus: 'synced' | 'syncing' | 'out-of-sync'` - Estado de sincronización

**Emits**:
- `click: []` - Click para intercambiar con master

**Características**:
- Indicador de sync con color:
  - Verde: sincronizado
  - Amarillo: sincronizando
  - Rojo: desincronizado
- Overlay con título y offset
- Hint "Cambiar a principal" en hover
- Aspect ratio 16:9 fijo

---

### 3. `LoadingOverlay.vue`
**Overlay de carga** durante preparación de slaves

**Props**:
- `isLoading: boolean`
- `loadedCount: number`
- `totalCount: number`

**Características**:
- Backdrop blur oscuro
- Ícono de cámara con animación pulse
- Barra de progreso con gradiente
- Contador "X de Y listos"
- Bloquea interacción hasta que cargue

---

### 4. `AssociateAngleModal.vue`
**Modal para asociar** nuevo ángulo de cámara

**Props**:
- `show: boolean`
- `videoId: number` - Video master al que asociar

**Emits**:
- `close: []`
- `associated: (videoId, cameraAngleName, syncOffset)`

**Flujo**:
1. Carga videos disponibles de la organización
2. Usuario busca y selecciona video
3. Configura nombre del ángulo y offset inicial
4. Asocia vía API

**Pendiente**:
- Endpoint API: `GET /api/videos/{id}/available-for-multi-camera`
- Endpoint API: `POST /api/videos/{id}/associate-camera-angle`

---

### 5. `SyncModal.vue`
**Modal para ajustar** offsets de sincronización

**Props**:
- `show: boolean`
- `slaveVideos: SlaveVideo[]`

**Emits**:
- `close: []`
- `saved: (offsets: Record<number, number>)`

**Características**:
- Slider por slave: rango ±30s, step 0.1s
- Input numérico sincronizado con slider
- Botón "Vista previa" (por implementar)
- Botón "Resetear" a offset original
- Guarda todos los offsets simultáneamente

**Pendiente**:
- Endpoint API: `PUT /api/videos/{id}/sync-offsets`
- Funcionalidad preview (reproduce 3-5s de ambos videos)

---

### 6. `MobileFullscreen.vue` (UI)
**Botón flotante** de pantalla completa para móviles

**Props**:
- `targetElement?: HTMLElement` - Elemento a poner fullscreen (default: documentElement)

**Emits**:
- `fullscreenChange: (isFullscreen: boolean)`

**Características**:
- Solo visible en móviles/tablets (≤768px)
- Posición: fixed bottom-right
- Detecta cambios de fullscreen automáticamente
- Cross-browser: webkit/moz/ms prefixes

---

## Uso Típico

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useVideoStore } from '@/stores/videoStore';
import { useMultiCamera } from '@/composables/useMultiCamera';
import MultiCameraLayout from './multi-camera/MultiCameraLayout.vue';
import LoadingOverlay from './multi-camera/LoadingOverlay.vue';
import AssociateAngleModal from './multi-camera/AssociateAngleModal.vue';
import SyncModal from './multi-camera/SyncModal.vue';
import MobileFullscreen from './ui/MobileFullscreen.vue';

const videoStore = useVideoStore();
const masterVideoRef = computed(() => videoStore.videoRef);
const slaveVideos = computed(() => videoStore.video?.slave_videos || []);

const {
  isLoaded,
  getSyncStatus,
  swapMaster,
  adjustSyncOffset
} = useMultiCamera({
  masterVideoRef,
  slaveVideos
});

const showAssociateModal = ref(false);
const showSyncModal = ref(false);

function handleSwapMaster(slaveId: number) {
  const newSlave = swapMaster(slaveId);
  if (newSlave) {
    // Actualizar store y recargar desde backend
  }
}

function handleSyncSaved(offsets: Record<number, number>) {
  Object.entries(offsets).forEach(([id, offset]) => {
    adjustSyncOffset(Number(id), offset);
  });
}
</script>

<template>
  <div class="video-player-container">
    <LoadingOverlay
      :is-loading="!isLoaded"
      :loaded-count="loadedSlaves"
      :total-count="slaveVideos.length"
    />

    <MultiCameraLayout
      :get-sync-status="getSyncStatus"
      @swap-master="handleSwapMaster"
    >
      <template #master>
        <video ref="videoRef" />
        <!-- Controles, etc -->
      </template>
    </MultiCameraLayout>

    <MobileFullscreen :target-element="containerRef" />

    <AssociateAngleModal
      :show="showAssociateModal"
      :video-id="video.id"
      @close="showAssociateModal = false"
      @associated="handleAssociated"
    />

    <SyncModal
      :show="showSyncModal"
      :slave-videos="slaveVideos"
      @close="showSyncModal = false"
      @saved="handleSyncSaved"
    />
  </div>
</template>
```

---

## Integración con Backend

### Endpoints Necesarios

```php
// VideoController.php

// GET /api/videos/{video}/available-for-multi-camera
// Retorna videos de la misma org que no están ya asociados
public function availableForMultiCamera(Video $video) {
    $videos = Video::where('organization_id', $video->organization_id)
        ->where('id', '!=', $video->id)
        ->whereDoesntHave('masterVideo') // No es slave de otro
        ->where('status', 'active')
        ->get(['id', 'title', 'duration', 'match_date']);

    return response()->json(['videos' => $videos]);
}

// POST /api/videos/{video}/associate-camera-angle
// Body: { slave_video_id, camera_angle_name, sync_offset }
public function associateCameraAngle(Request $request, Video $video) {
    $validated = $request->validate([
        'slave_video_id' => 'required|exists:videos,id',
        'camera_angle_name' => 'nullable|string|max:255',
        'sync_offset' => 'required|numeric'
    ]);

    // Crear relación en tabla multi_camera_videos
    MultiCameraVideo::create([
        'master_video_id' => $video->id,
        'slave_video_id' => $validated['slave_video_id'],
        'camera_angle_name' => $validated['camera_angle_name'],
        'sync_offset' => $validated['sync_offset']
    ]);

    return response()->json(['success' => true]);
}

// PUT /api/videos/{video}/sync-offsets
// Body: { offsets: { slave_id: offset, ... } }
public function updateSyncOffsets(Request $request, Video $video) {
    $validated = $request->validate([
        'offsets' => 'required|array',
        'offsets.*' => 'numeric'
    ]);

    foreach ($validated['offsets'] as $slaveId => $offset) {
        MultiCameraVideo::where('master_video_id', $video->id)
            ->where('slave_video_id', $slaveId)
            ->update(['sync_offset' => $offset]);
    }

    return response()->json(['success' => true]);
}
```

### Migración

```php
Schema::create('multi_camera_videos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('master_video_id')->constrained('videos')->onDelete('cascade');
    $table->foreignId('slave_video_id')->constrained('videos')->onDelete('cascade');
    $table->string('camera_angle_name')->nullable();
    $table->decimal('sync_offset', 10, 2)->default(0);
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['master_video_id', 'slave_video_id']);
});
```

### Actualizar Video Model

```php
class Video extends Model {
    public function slaveVideos() {
        return $this->belongsToMany(Video::class, 'multi_camera_videos', 'master_video_id', 'slave_video_id')
            ->withPivot('camera_angle_name', 'sync_offset', 'sort_order')
            ->orderBy('sort_order');
    }

    public function masterVideo() {
        return $this->belongsToMany(Video::class, 'multi_camera_videos', 'slave_video_id', 'master_video_id');
    }

    public function getIsPartOfGroupAttribute() {
        return $this->slaveVideos()->exists() || $this->masterVideo()->exists();
    }
}
```

---

## Notas Técnicas

### Sincronización
- **Tolerancia**: ±0.3s de drift antes de re-sync
- **Check periódico**: Cada 2s durante reproducción
- **Eventos**: play, pause, seeked, ratechange

### Performance
- Videos slaves se crean `display: none` en DOM
- Preload: `metadata` (no `auto`)
- AbortController cleanup en unmount

### Mobile
- Grid responsive: 1 columna en mobile
- Botón fullscreen flotante solo en ≤768px
- Touch-friendly: áreas de toque grandes

### Accesibilidad
- Botones con `title` descriptivos
- Estados visuales claros (color + ícono)
- Contraste WCAG AA compliant

---

## TODO Backend
- [ ] Crear tabla `multi_camera_videos`
- [ ] Implementar endpoints en `VideoController`
- [ ] Agregar relaciones en `Video` model
- [ ] Incluir `slave_videos` en VideoResource
- [ ] Agregar `useVideoApi` methods:
  - `getAvailableVideosForMultiCamera()`
  - `associateCameraAngle()`
  - `updateSyncOffsets()`

## TODO Frontend
- [ ] Integrar composable en página principal
- [ ] Conectar modales con botones en UI
- [ ] Implementar preview en SyncModal
- [ ] Testing de sincronización
- [ ] Documentar hotkeys para multi-camera
