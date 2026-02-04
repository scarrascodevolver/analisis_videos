# Plan de Implementación: Sistema de Clips de Video (Estilo LongoMatch)

## Resumen

Implementar un sistema de **clips virtuales** con botonera personalizable para marcar momentos importantes en videos de rugby, similar a LongoMatch.

---

## ARQUITECTURA DE BASE DE DATOS

### Nueva Tabla: `clip_categories` (Botonera personalizable)

```sql
clip_categories
├── id (PK)
├── organization_id (FK) -- Multi-tenant
├── name               -- "Try", "Scrum", "Lineout", etc.
├── slug               -- "try", "scrum", "lineout"
├── color              -- "#FF5733" para UI
├── icon               -- "fas fa-football-ball" (opcional)
├── hotkey             -- "t", "s", "l" (tecla rápida)
├── lead_seconds       -- 5 (segundos ANTES de la acción)
├── lag_seconds        -- 3 (segundos DESPUÉS de la acción)
├── sort_order         -- Orden en botonera
├── is_active          -- Boolean
├── created_by (FK)    -- Usuario que la creó
├── timestamps
```

### Nueva Tabla: `video_clips` (Clips virtuales)

```sql
video_clips
├── id (PK)
├── video_id (FK)
├── clip_category_id (FK)
├── organization_id (FK)  -- Multi-tenant
├── created_by (FK)       -- Usuario que marcó
├── start_time            -- Decimal(10,2): segundo inicio
├── end_time              -- Decimal(10,2): segundo fin
├── title                 -- Título opcional del clip
├── notes                 -- Notas/observaciones
├── players               -- JSON: IDs de jugadores involucrados
├── tags                  -- JSON: tags adicionales ["defensivo", "error"]
├── rating                -- 1-5 estrellas (calidad de la jugada)
├── is_highlight          -- Boolean: marcar como destacado
├── timestamps
```

### Nueva Tabla: `clip_playlists` (Listas de reproducción)

```sql
clip_playlists
├── id (PK)
├── organization_id (FK)
├── name                  -- "Mejores Tries 2026"
├── description
├── created_by (FK)
├── is_public             -- Visible para todos en org
├── timestamps

clip_playlist_items (pivot)
├── id (PK)
├── playlist_id (FK)
├── video_clip_id (FK)
├── sort_order
├── timestamps
```

---

## ESTRUCTURA DE ARCHIVOS (Sin archivos grandes)

### Concepto Clave: NO cortar videos físicamente

```
┌─────────────────────────────────────────────────────────┐
│  VIDEO ORIGINAL (intacto en storage)                    │
│  partido_vs_pucara_2026-01-14.mp4 (2GB)                │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  BASE DE DATOS (solo metadatos, sin archivos nuevos)    │
│                                                         │
│  video_clips:                                           │
│  ┌─────────────────────────────────────────────────┐   │
│  │ id:1, video_id:5, category:"Try"                │   │
│  │ start_time: 1234.50, end_time: 1245.00          │   │
│  │ notes: "Try de Juan por banda derecha"          │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │ id:2, video_id:5, category:"Scrum"              │   │
│  │ start_time: 315.00, end_time: 345.00            │   │
│  │ notes: "Scrum defensivo, perdimos"              │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Exportación Opcional (solo si usuario lo pide)

```
storage/exports/{org-slug}/clips/
├── try_juan_perez_20260114.mp4      (solo si se exporta)
├── scrum_defensivo_20260114.mp4     (solo si se exporta)
└── playlist_mejores_tries.mp4       (compilación exportada)
```

---

## FLUJO DE USO

### 1. Configurar Botonera (una vez por organización)

```
Analista → Configuración → Categorías de Clips
    │
    ├── [+] Try         (tecla: T, color: verde,  lead: 5s, lag: 3s)
    ├── [+] Scrum       (tecla: S, color: azul,   lead: 3s, lag: 5s)
    ├── [+] Lineout     (tecla: L, color: naranja, lead: 3s, lag: 3s)
    ├── [+] Penal       (tecla: P, color: rojo,   lead: 2s, lag: 5s)
    ├── [+] Tackle      (tecla: K, color: morado, lead: 2s, lag: 2s)
    └── [+] Error       (tecla: E, color: gris,   lead: 3s, lag: 3s)
```

### 2. Marcar Clips (mientras ve el video)

```
┌─────────────────────────────────────────────────────────┐
│  VIDEO PLAYER                                           │
│  ▶ ─────────────────●──────────────────────── 45:00    │
│                    23:45                                │
├─────────────────────────────────────────────────────────┤
│  BOTONERA (aparece al activar modo análisis)           │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐   │
│  │  TRY  │ │ SCRUM │ │LINEOUT│ │ PENAL │ │TACKLE │   │
│  │  [T]  │ │  [S]  │ │  [L]  │ │  [P]  │ │  [K]  │   │
│  └───────┘ └───────┘ └───────┘ └───────┘ └───────┘   │
│                                                         │
│  Al presionar [T] en 23:45:                            │
│  → Crea clip: start=23:40 (lead 5s), end=23:48 (lag 3s)│
└─────────────────────────────────────────────────────────┘
```

### 3. Revisar/Editar Clips

```
Panel lateral derecho:
┌─────────────────────────┐
│ CLIPS DE ESTE VIDEO (12)│
├─────────────────────────┤
│ 🏉 Try      │ 23:40    │ ← click para ir
│ 🔵 Scrum    │ 05:15    │
│ 🔵 Scrum    │ 12:30    │
│ 🟠 Lineout  │ 18:22    │
│ 🏉 Try      │ 35:10    │
│ ...                     │
└─────────────────────────┘

Filtros:
[Todos] [Try] [Scrum] [Lineout] [Penal]
```

### 4. Reproducir Solo Clips de una Categoría

```
Usuario selecciona: "Ver solo TRIES"
    │
    ▼
Reproductor salta automáticamente:
    → Clip 1: 23:40 - 23:48
    → Clip 2: 35:10 - 35:18
    → Clip 3: 67:22 - 67:30
    → FIN

(No reproduce el resto del video, solo los segmentos marcados)
```

---

## MODELOS LARAVEL

### ClipCategory.php

```php
class ClipCategory extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name', 'slug', 'color', 'icon', 'hotkey',
        'lead_seconds', 'lag_seconds', 'sort_order',
        'is_active', 'created_by', 'organization_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_seconds' => 'integer',
        'lag_seconds' => 'integer',
    ];

    public function clips() {
        return $this->hasMany(VideoClip::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### VideoClip.php

```php
class VideoClip extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'video_id', 'clip_category_id', 'organization_id',
        'created_by', 'start_time', 'end_time', 'title',
        'notes', 'players', 'tags', 'rating', 'is_highlight'
    ];

    protected $casts = [
        'start_time' => 'decimal:2',
        'end_time' => 'decimal:2',
        'players' => 'array',
        'tags' => 'array',
        'rating' => 'integer',
        'is_highlight' => 'boolean',
    ];

    public function video() {
        return $this->belongsTo(Video::class);
    }

    public function category() {
        return $this->belongsTo(ClipCategory::class, 'clip_category_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Duración en segundos
    public function getDurationAttribute() {
        return $this->end_time - $this->start_time;
    }

    // Formato MM:SS para mostrar
    public function getFormattedStartAttribute() {
        return gmdate('i:s', (int) $this->start_time);
    }
}
```

---

## MIGRACIONES

### 1. create_clip_categories_table.php

```php
Schema::create('clip_categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->string('name', 50);
    $table->string('slug', 50);
    $table->string('color', 7)->default('#007bff');
    $table->string('icon', 50)->nullable();
    $table->char('hotkey', 1)->nullable();
    $table->unsignedTinyInteger('lead_seconds')->default(5);
    $table->unsignedTinyInteger('lag_seconds')->default(3);
    $table->unsignedTinyInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->unique(['organization_id', 'slug']);
    $table->unique(['organization_id', 'hotkey']);
});
```

### 2. create_video_clips_table.php

```php
Schema::create('video_clips', function (Blueprint $table) {
    $table->id();
    $table->foreignId('video_id')->constrained()->cascadeOnDelete();
    $table->foreignId('clip_category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->decimal('start_time', 10, 2);
    $table->decimal('end_time', 10, 2);
    $table->string('title', 100)->nullable();
    $table->text('notes')->nullable();
    $table->json('players')->nullable();
    $table->json('tags')->nullable();
    $table->unsignedTinyInteger('rating')->nullable();
    $table->boolean('is_highlight')->default(false);
    $table->timestamps();

    $table->index(['video_id', 'clip_category_id']);
    $table->index(['video_id', 'start_time']);
    $table->index(['organization_id', 'clip_category_id']);
});
```

### 3. create_clip_playlists_table.php

```php
Schema::create('clip_playlists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->boolean('is_public')->default(false);
    $table->timestamps();
});

Schema::create('clip_playlist_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('playlist_id')->constrained('clip_playlists')->cascadeOnDelete();
    $table->foreignId('video_clip_id')->constrained('video_clips')->cascadeOnDelete();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['playlist_id', 'video_clip_id']);
});
```

---

## CONTROLADORES

### ClipCategoryController.php

```php
// CRUD para categorías de clips (botonera)
Route::resource('clip-categories', ClipCategoryController::class);

// Métodos:
// - index(): Lista categorías de la org
// - create(): Form para nueva categoría
// - store(): Guardar categoría
// - edit(): Form edición
// - update(): Actualizar
// - destroy(): Eliminar (solo si no tiene clips)
// - reorder(): Cambiar orden (AJAX)
```

### VideoClipController.php

```php
// CRUD para clips
Route::prefix('videos/{video}')->group(function () {
    Route::get('clips', [VideoClipController::class, 'index']);
    Route::post('clips', [VideoClipController::class, 'store']);
    Route::put('clips/{clip}', [VideoClipController::class, 'update']);
    Route::delete('clips/{clip}', [VideoClipController::class, 'destroy']);
});

// API para el player
Route::prefix('api/videos/{video}')->group(function () {
    Route::get('clips', [VideoClipController::class, 'apiIndex']);
    Route::post('clips/quick', [VideoClipController::class, 'quickStore']); // Desde botonera
});

// Filtrar clips por categoría
Route::get('clips/category/{category}', [VideoClipController::class, 'byCategory']);

// Exportar clip como archivo (futuro)
Route::post('clips/{clip}/export', [VideoClipController::class, 'export']);
```

---

## JAVASCRIPT: clip-manager.js

### Funcionalidades principales

```javascript
class ClipManager {
    constructor(videoElement, categories) {
        this.video = videoElement;
        this.categories = categories; // Desde servidor
        this.clips = [];
        this.isAnalysisMode = false;
        this.playingClipsOnly = false;
        this.currentClipIndex = 0;

        this.init();
    }

    init() {
        this.setupHotkeys();
        this.setupButtonPanel();
        this.loadClips();
    }

    // Teclas rápidas para marcar
    setupHotkeys() {
        document.addEventListener('keydown', (e) => {
            if (!this.isAnalysisMode) return;

            const category = this.categories.find(c =>
                c.hotkey.toLowerCase() === e.key.toLowerCase()
            );

            if (category) {
                e.preventDefault();
                this.createClip(category);
            }
        });
    }

    // Crear clip con lead/lag automático
    createClip(category) {
        const currentTime = this.video.currentTime;
        const startTime = Math.max(0, currentTime - category.lead_seconds);
        const endTime = Math.min(this.video.duration, currentTime + category.lag_seconds);

        fetch(`/api/videos/${this.videoId}/clips/quick`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                clip_category_id: category.id,
                start_time: startTime,
                end_time: endTime
            })
        })
        .then(r => r.json())
        .then(clip => {
            this.clips.push(clip);
            this.renderClipMarker(clip);
            this.showNotification(`Clip "${category.name}" creado`);
        });
    }

    // Reproducir solo clips de una categoría
    playClipsOnly(categoryId = null) {
        this.playingClipsOnly = true;
        this.filteredClips = categoryId
            ? this.clips.filter(c => c.clip_category_id === categoryId)
            : this.clips;
        this.filteredClips.sort((a, b) => a.start_time - b.start_time);
        this.currentClipIndex = 0;
        this.playNextClip();
    }

    playNextClip() {
        if (this.currentClipIndex >= this.filteredClips.length) {
            this.playingClipsOnly = false;
            this.showNotification('Fin de clips');
            return;
        }

        const clip = this.filteredClips[this.currentClipIndex];
        this.video.currentTime = clip.start_time;
        this.video.play();

        // Cuando llegue al end_time, saltar al siguiente
        this.clipEndHandler = () => {
            if (this.video.currentTime >= clip.end_time) {
                this.currentClipIndex++;
                this.playNextClip();
            }
        };
        this.video.addEventListener('timeupdate', this.clipEndHandler);
    }

    // Detener modo clips
    stopClipsMode() {
        this.playingClipsOnly = false;
        this.video.removeEventListener('timeupdate', this.clipEndHandler);
    }
}
```

---

## INTERFAZ DE USUARIO

### Vista del reproductor con clips

```
┌─────────────────────────────────────────────────────────────────────────┐
│ VIDEO PLAYER                                                             │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │                                                                     │ │
│ │                         [VIDEO]                                     │ │
│ │                                                                     │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│ ▶ ──●────────────────────────────────────────────────────── 90:00      │
│     │  │     │        │   │              │        │                     │
│     └──┴─────┴────────┴───┴──────────────┴────────┴── (markers clips)  │
│                                                                          │
│ ┌────────────────────────────────────────────────────────────────────┐  │
│ │ 🎬 MODO ANÁLISIS [ON]                                              │  │
│ │ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐            │  │
│ │ │ TRY  │ │SCRUM │ │LINE- │ │PENAL │ │TACKLE│ │ERROR │            │  │
│ │ │  T   │ │  S   │ │OUT L │ │  P   │ │  K   │ │  E   │            │  │
│ │ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘            │  │
│ └────────────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────────┤
│ CLIPS (15)                    Filtrar: [Todos ▼] [⭐ Destacados]        │
│ ┌───────────────────────────────────────────────────────────────────┐  │
│ │ 🟢 Try      │ 12:34  │ Try de Juan Pérez           │ ⭐ │ ✏️ │ 🗑️ │ │
│ │ 🔵 Scrum    │ 05:15  │ Scrum ganado                │    │ ✏️ │ 🗑️ │ │
│ │ 🔵 Scrum    │ 18:30  │ Scrum perdido               │    │ ✏️ │ 🗑️ │ │
│ │ 🟠 Lineout  │ 23:45  │ Lineout propio              │ ⭐ │ ✏️ │ 🗑️ │ │
│ │ 🟢 Try      │ 45:20  │ Segundo try                 │ ⭐ │ ✏️ │ 🗑️ │ │
│ └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│ [▶ Ver solo TRIES] [▶ Ver todos los clips] [📥 Exportar seleccionados] │
└─────────────────────────────────────────────────────────────────────────┘
```

### Vista de configuración de categorías

```
┌─────────────────────────────────────────────────────────────────────────┐
│ ⚙️ CONFIGURAR CATEGORÍAS DE CLIPS                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│ Arrastra para reordenar:                                                │
│ ┌───────────────────────────────────────────────────────────────────┐  │
│ │ ≡  🟢 Try      │ Tecla: T │ Lead: 5s │ Lag: 3s │ [Editar] [🗑️]  │  │
│ │ ≡  🔵 Scrum    │ Tecla: S │ Lead: 3s │ Lag: 5s │ [Editar] [🗑️]  │  │
│ │ ≡  🟠 Lineout  │ Tecla: L │ Lead: 3s │ Lag: 3s │ [Editar] [🗑️]  │  │
│ │ ≡  🔴 Penal    │ Tecla: P │ Lead: 2s │ Lag: 5s │ [Editar] [🗑️]  │  │
│ │ ≡  🟣 Tackle   │ Tecla: K │ Lead: 2s │ Lag: 2s │ [Editar] [🗑️]  │  │
│ │ ≡  ⚫ Error    │ Tecla: E │ Lead: 3s │ Lag: 3s │ [Editar] [🗑️]  │  │
│ └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│ [+ Agregar nueva categoría]                                             │
│                                                                          │
│ ┌─ Nueva categoría ─────────────────────────────────────────────────┐  │
│ │ Nombre: [______________]  Color: [🎨]  Tecla: [_]                │  │
│ │ Lead (segundos antes): [5]  Lag (segundos después): [3]          │  │
│ │                                            [Cancelar] [Guardar]   │  │
│ └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## ORDEN DE IMPLEMENTACIÓN

### Fase 1: Base de datos y modelos
1. Crear migración `clip_categories`
2. Crear migración `video_clips`
3. Crear modelo `ClipCategory`
4. Crear modelo `VideoClip`
5. Agregar relaciones a modelo `Video`

### Fase 2: Backend - Categorías
1. `ClipCategoryController` con CRUD
2. Vista de configuración de categorías
3. Seed con categorías default de rugby

### Fase 3: Backend - Clips
1. `VideoClipController` con CRUD
2. API endpoints para el player
3. Endpoint para crear clip rápido (botonera)

### Fase 4: Frontend - Botonera
1. Agregar panel de botonera al reproductor
2. Implementar hotkeys
3. Crear clip al presionar botón/tecla
4. Feedback visual (notificación)

### Fase 5: Frontend - Lista de clips
1. Panel lateral con lista de clips
2. Click para ir al timestamp
3. Filtros por categoría
4. Editar/eliminar clips

### Fase 6: Reproducción filtrada
1. Modo "Ver solo clips de X categoría"
2. Saltar automáticamente entre clips
3. Indicador de progreso en modo clips

### Fase 7 (Futuro): Playlists
1. Crear/editar playlists
2. Agregar clips de varios videos
3. Compartir playlists

### Fase 8 (Futuro): Exportación
1. Job para extraer clips como archivos MP4
2. Compilar playlist como video único
3. Watermark de la organización

---

## CATEGORÍAS DEFAULT PARA RUGBY

```php
// Seeder: ClipCategorySeeder.php
$categories = [
    ['name' => 'Try', 'slug' => 'try', 'color' => '#28a745', 'hotkey' => 't', 'lead' => 5, 'lag' => 3],
    ['name' => 'Scrum', 'slug' => 'scrum', 'color' => '#007bff', 'hotkey' => 's', 'lead' => 3, 'lag' => 5],
    ['name' => 'Lineout', 'slug' => 'lineout', 'color' => '#fd7e14', 'hotkey' => 'l', 'lead' => 3, 'lag' => 4],
    ['name' => 'Penal', 'slug' => 'penal', 'color' => '#dc3545', 'hotkey' => 'p', 'lead' => 2, 'lag' => 5],
    ['name' => 'Tackle', 'slug' => 'tackle', 'color' => '#6f42c1', 'hotkey' => 'k', 'lead' => 2, 'lag' => 2],
    ['name' => 'Ruck', 'slug' => 'ruck', 'color' => '#17a2b8', 'hotkey' => 'r', 'lead' => 2, 'lag' => 3],
    ['name' => 'Maul', 'slug' => 'maul', 'color' => '#e83e8c', 'hotkey' => 'm', 'lead' => 2, 'lag' => 4],
    ['name' => 'Knock-on', 'slug' => 'knock-on', 'color' => '#6c757d', 'hotkey' => 'n', 'lead' => 2, 'lag' => 2],
    ['name' => 'Forward Pass', 'slug' => 'forward-pass', 'color' => '#343a40', 'hotkey' => 'f', 'lead' => 2, 'lag' => 2],
    ['name' => 'Break', 'slug' => 'break', 'color' => '#20c997', 'hotkey' => 'b', 'lead' => 3, 'lag' => 4],
];
```

---

## RUTAS COMPLETAS

```php
// routes/web.php

// Configuración de categorías (analistas/admins)
Route::middleware(['auth', 'role:analista,entrenador,super_admin'])->group(function () {
    Route::resource('clip-categories', ClipCategoryController::class);
    Route::post('clip-categories/reorder', [ClipCategoryController::class, 'reorder'])
        ->name('clip-categories.reorder');
});

// Clips de video
Route::middleware(['auth'])->group(function () {
    Route::get('videos/{video}/clips', [VideoClipController::class, 'index'])
        ->name('videos.clips.index');
    Route::post('videos/{video}/clips', [VideoClipController::class, 'store'])
        ->name('videos.clips.store');
    Route::put('videos/{video}/clips/{clip}', [VideoClipController::class, 'update'])
        ->name('videos.clips.update');
    Route::delete('videos/{video}/clips/{clip}', [VideoClipController::class, 'destroy'])
        ->name('videos.clips.destroy');
});

// API para el player (AJAX)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('videos/{video}/clips', [VideoClipController::class, 'apiIndex']);
    Route::post('videos/{video}/clips/quick', [VideoClipController::class, 'quickStore']);
    Route::get('clip-categories', [ClipCategoryController::class, 'apiIndex']);
});

// Playlists (futuro)
Route::middleware(['auth'])->group(function () {
    Route::resource('clip-playlists', ClipPlaylistController::class);
    Route::post('clip-playlists/{playlist}/add-clip', [ClipPlaylistController::class, 'addClip']);
    Route::delete('clip-playlists/{playlist}/remove-clip/{clip}', [ClipPlaylistController::class, 'removeClip']);
});
```

---

## ESTIMACIÓN DE TAMAÑO

### Base de datos (muy pequeño)
- 1000 clips = ~500KB de datos
- Sin archivos de video adicionales
- Solo metadatos y timestamps

### Storage (solo si exporta)
- Exportación es OPCIONAL
- Solo se crean archivos si el usuario lo pide
- Se pueden borrar después de descargar

---

## RESUMEN

| Componente | Descripción |
|------------|-------------|
| `clip_categories` | Botonera personalizable por org |
| `video_clips` | Clips virtuales (timestamps) |
| `clip_playlists` | Listas de reproducción (futuro) |
| No corta videos | Solo guarda start/end time |
| Exportar | Opcional, bajo demanda |
| Hotkeys | Teclas rápidas para marcar |
| Filtros | Ver solo clips de X categoría |

**Ventajas de esta arquitectura:**
- No duplica videos (ahorra storage)
- Rápido (solo lee timestamps)
- Escalable (miles de clips sin problema)
- Flexible (categorías personalizables)
- Integrado con sistema existente
