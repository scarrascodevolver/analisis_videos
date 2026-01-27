# 🎬 Análisis: Sistema Multi-Cámara / Multi-Ángulo

**Fecha Análisis:** 2026-01-26
**Fecha Implementación:** 2026-01-27
**Estado:** 🚧 EN DESARROLLO
**Rama:** `feature/multi-camera-sync`
**Objetivo:** Permitir ver múltiples ángulos de cámara del mismo partido simultáneamente

## ✅ Progreso de Implementación

### ✅ Fase 1: Base de Datos (COMPLETADA)
- ✅ Migración con campos multi-cámara
- ✅ Modelo Video con métodos helper
- ✅ Commit: `0a8bc325`

### ✅ Fase 2: Backend API (COMPLETADA)
- ✅ MultiCameraController con endpoints
- ✅ Rutas en web.php
- ✅ Commit: `9f2d640b`

### ✅ Fase 3: UI para Asociar Ángulos (COMPLETADA)
- ✅ Card section en vista de video
- ✅ Modal de búsqueda y asociación
- ✅ Gestión de ángulos (agregar/remover)
- ✅ Commit: `95d19d9c`

### ✅ Fase 4: Herramienta de Sincronización (COMPLETADA)
- ✅ Modal de sincronización lado a lado
- ✅ Controles independientes para cada video
- ✅ Slider de offset ±300 segundos
- ✅ Test de sincronización (5 segundos)
- ✅ Selector de clips como referencia
- ✅ Commit: `8e5d55e1`

### ✅ Fase 5: Vista Multi-Ángulo (COMPLETADA)
- ✅ Player multi-cámara con master + thumbnails
- ✅ Timeline única controlando todos los videos
- ✅ Play/Pause/Seek sincronizado
- ✅ Detección y corrección de drift automática
- ✅ Advertencia para videos no sincronizados
- ✅ Commit: `a6f92c43`

## 🎉 IMPLEMENTACIÓN COMPLETA

**Estado:** ✅ **LISTO PARA TESTING**
**Total de commits:** 6
**Archivos creados:** 3 parciales + 1 migración + 1 controlador
**Líneas de código:** ~1,700+

---

## 🎯 Concepto

### Escenario de Uso

**Partido:** Chile vs Argentina (2024-03-15)

```
Video Group: "Chile vs Argentina - 2024-03-15"
├── 🎥 Video Master (Cámara Principal - Tribuna Central)
│   ├── XML LongoMatch con todos los clips/eventos
│   ├── Comentarios de analistas
│   └── Anotaciones
├── 📹 Ángulo 2 (Cámara Lateral Derecha)
├── 📹 Ángulo 3 (Cámara Try Zone)
└── 📹 Ángulo 4 (Cámara Aérea Drone)
```

**Funcionalidad Deseada:**
1. Analista abre el video master
2. Sistema detecta que hay otros ángulos disponibles
3. Analista puede agregar/mostrar otros ángulos (2-4 videos simultáneos)
4. **Video master controla todo:**
   - Timeline única (del master)
   - Clips definidos en el master
   - Play/Pause sincronizado
   - Seek sincronizado
5. Los demás videos son "slaves" que siguen al master

---

## 🗄️ Diseño de Base de Datos

### Opción A: Tabla `video_groups` (Recomendada)

**Nueva tabla:** `video_groups`

```sql
CREATE TABLE video_groups (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),                    -- "Chile vs Argentina - 2024-03-15"
    description TEXT,
    organization_id BIGINT,               -- Multi-tenant
    match_date DATE,
    analyzed_team_id BIGINT,
    rival_team_id BIGINT,
    category_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Nueva tabla:** `video_group_members`

```sql
CREATE TABLE video_group_members (
    id BIGINT PRIMARY KEY,
    video_group_id BIGINT,
    video_id BIGINT,
    is_master BOOLEAN DEFAULT false,      -- Solo 1 master por grupo
    camera_name VARCHAR(100),             -- "Tribuna Central", "Lateral", "Try Zone"
    camera_position ENUM('center', 'left', 'right', 'aerial', 'try_zone', 'custom'),
    sync_offset_seconds DECIMAL(8,2),     -- Offset de sincronización (si cámaras no empezaron juntas)
    display_order INT,                    -- Orden de visualización (1, 2, 3, 4)
    created_at TIMESTAMP
);
```

**Modificación tabla:** `videos`

```sql
ALTER TABLE videos ADD COLUMN video_group_id BIGINT NULL;
ALTER TABLE videos ADD COLUMN is_group_master BOOLEAN DEFAULT false;
```

**Ventajas:**
- ✅ Múltiples videos pueden compartir metadata (fecha, equipos, categoría)
- ✅ Fácil de consultar todos los ángulos de un partido
- ✅ Escalable: un partido puede tener N ángulos
- ✅ Mantiene videos independientes (pueden verse solos también)

**Desventajas:**
- ❌ Migración compleja
- ❌ UI más complicada para gestionar grupos

---

### Opción B: Relación Simple `parent_video_id` (Más Simple)

**Modificación tabla:** `videos`

```sql
ALTER TABLE videos ADD COLUMN parent_video_id BIGINT NULL;
ALTER TABLE videos ADD COLUMN camera_angle VARCHAR(100) NULL; -- "Tribuna", "Lateral", etc
ALTER TABLE videos ADD COLUMN sync_offset_seconds DECIMAL(8,2) DEFAULT 0;
ALTER TABLE videos ADD COLUMN display_order INT DEFAULT 1;
```

**Lógica:**
- Video con `parent_video_id = NULL` → Es el master
- Videos con `parent_video_id = X` → Son slaves del video X
- Todos comparten misma fecha de partido, equipos, categoría

**Ventajas:**
- ✅ Migración simple (1 columna nueva)
- ✅ Fácil de implementar
- ✅ Consulta simple: `WHERE parent_video_id = $masterVideoId`

**Desventajas:**
- ❌ Metadata duplicada (fecha, equipos) en cada video
- ❌ Si cambias el master, tienes que actualizar todos los slaves
- ❌ Menos flexible

---

### ⭐ Recomendación: Híbrido (Opción C)

**Modificación tabla:** `videos`

```sql
ALTER TABLE videos ADD COLUMN video_group_id BIGINT NULL;      -- Si pertenece a un grupo
ALTER TABLE videos ADD COLUMN is_master BOOLEAN DEFAULT true;  -- Master del grupo
ALTER TABLE videos ADD COLUMN camera_angle VARCHAR(100) NULL;
ALTER TABLE videos ADD COLUMN sync_offset DECIMAL(8,2) DEFAULT 0;
```

**Sin tabla intermedia, pero con agrupación lógica:**
- Videos del mismo partido comparten `video_group_id`
- 1 video tiene `is_master = true` (el resto false)
- Consulta: `Video::where('video_group_id', $groupId)->orderBy('is_master', 'desc')`

**Ventajas:**
- ✅ Más simple que Opción A (no necesita tabla intermedia)
- ✅ Más flexible que Opción B (no hay jerarquía parent-child)
- ✅ Fácil de migrar videos existentes (solo NULL en video_group_id)

**Este sería mi elección** 👍

---

## 🎨 Diseño de UI/UX

### Layout de Pantalla

#### Opción 1: Grid 2x2 (Hasta 4 ángulos)

```
┌─────────────────────────────────────────────────────┐
│ [Navbar]                                             │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌─────────────────┬─────────────────┐   [Sidebar]  │
│  │                 │                 │   ┌─────────┐ │
│  │   Video Master  │   Ángulo 2      │   │ Clips   │ │
│  │   (Tribuna)     │   (Lateral)     │   │         │ │
│  │                 │                 │   │ Lista   │ │
│  └─────────────────┴─────────────────┘   │ de      │ │
│  ┌─────────────────┬─────────────────┐   │ Clips   │ │
│  │   Ángulo 3      │   Ángulo 4      │   │         │ │
│  │   (Try Zone)    │   (Aérea)       │   │         │ │
│  │                 │                 │   │         │ │
│  └─────────────────┴─────────────────┘   └─────────┘ │
│                                                       │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│  [TIMELINE ÚNICA - Controla todos los videos]       │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                       │
└─────────────────────────────────────────────────────┘
```

**Características:**
- Timeline única en la parte inferior
- Controles de play/pause/seek afectan a todos
- Clips del master se muestran en timeline
- Sidebar derecho con lista de clips (como ahora)

---

#### Opción 2: Master Grande + Thumbnails (Hasta 3 adicionales)

```
┌─────────────────────────────────────────────────────┐
│ [Navbar]                                             │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌───────────────────────────────────┐   [Sidebar]  │
│  │                                   │   ┌─────────┐ │
│  │                                   │   │ Clips   │ │
│  │       Video Master (Grande)       │   │         │ │
│  │       Tribuna Central             │   │ Lista   │ │
│  │                                   │   │         │ │
│  │                                   │   │         │ │
│  └───────────────────────────────────┘   └─────────┘ │
│  ┌───────┬───────┬───────┬───────────┐               │
│  │ Áng 2 │ Áng 3 │ Áng 4 │ + Agregar │               │
│  │ 🎥    │ 🎥    │ 🎥    │           │               │
│  └───────┴───────┴───────┴───────────┘               │
│                                                       │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│  [TIMELINE ÚNICA]                                    │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                       │
└─────────────────────────────────────────────────────┘
```

**Características:**
- Master ocupa 70% de la altura
- Ángulos secundarios en thumbnails (30% altura)
- Click en thumbnail → intercambia con master
- Más intuitivo para principiantes

---

#### Opción 3: Picture-in-Picture (PiP)

```
┌─────────────────────────────────────────────────────┐
│ [Navbar]                                             │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌───────────────────────────────────┐   [Sidebar]  │
│  │                                   │   ┌─────────┐ │
│  │                                   │   │ Clips   │ │
│  │       Video Master (Grande)       │   │         │ │
│  │       Tribuna Central             │   │ Lista   │ │
│  │                                   │   │         │ │
│  │  ┌────┐  ┌────┐  ┌────┐          │   │         │ │
│  │  │Áng2│  │Áng3│  │Áng4│          │   │         │ │
│  │  └────┘  └────┘  └────┘          │   │         │ │
│  └───────────────────────────────────┘   └─────────┘ │
│                                                       │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│  [TIMELINE]                                          │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
└─────────────────────────────────────────────────────┘
```

**Características:**
- Ángulos secundarios flotantes sobre el master (esquina inferior)
- Draggable (mover a cualquier esquina)
- Resizable (agrandar/achicar)
- Menos intrusivo

---

### ⭐ Recomendación UI: **Opción 2 (Master Grande + Thumbnails)**

**Por qué:**
- ✅ Familiar (como YouTube PiP)
- ✅ No abruma (master es el foco principal)
- ✅ Fácil de implementar
- ✅ Click para intercambiar ángulos es intuitivo
- ✅ Funciona bien en laptops (no requiere pantalla enorme)

---

## ⚙️ Sincronización de Videos

### Problema: Offset Temporal

**Escenario Real:**
```
Cámara 1 (Master):  Inicia a las 15:00:00
Cámara 2 (Lateral): Inicia a las 15:00:05  (+5 segundos)
Cámara 3 (Aérea):   Inicia a las 14:59:55  (-5 segundos)
```

**Solución: Sync Offset**

Cada video slave tiene un `sync_offset_seconds`:
```javascript
// Cuando el master está en 120 segundos:
video_master.currentTime = 120;

// Calcular currentTime para slaves:
video_slave_2.currentTime = 120 + video_slave_2.sync_offset; // 125
video_slave_3.currentTime = 120 + video_slave_3.sync_offset; // 115
```

**Cómo el usuario define el offset:**

1. **Método Automático (Recomendado):**
   - Usar timestamp del archivo (metadata)
   - Calcular diferencia automáticamente

2. **Método Manual:**
   - UI: "Este video está X segundos adelantado/atrasado"
   - Slider para ajustar en tiempo real
   - Guardar offset en BD

---

### Sincronización de Play/Pause/Seek

```javascript
// Pseudo-código
class MultiCameraManager {
    constructor(masterVideo, slaveVideos) {
        this.master = masterVideo;
        this.slaves = slaveVideos;
        this.setupSync();
    }

    setupSync() {
        // Play
        this.master.addEventListener('play', () => {
            this.slaves.forEach(slave => slave.play());
        });

        // Pause
        this.master.addEventListener('pause', () => {
            this.slaves.forEach(slave => slave.pause());
        });

        // Seek
        this.master.addEventListener('seeked', () => {
            const masterTime = this.master.currentTime;
            this.slaves.forEach(slave => {
                slave.currentTime = masterTime + slave.syncOffset;
            });
        });

        // Timeupdate (cada 1 segundo verificar que estén sincronizados)
        setInterval(() => {
            this.checkSync();
        }, 1000);
    }

    checkSync() {
        const masterTime = this.master.currentTime;
        this.slaves.forEach(slave => {
            const expectedTime = masterTime + slave.syncOffset;
            const diff = Math.abs(slave.currentTime - expectedTime);

            // Si la diferencia es > 0.5 segundos, re-sincronizar
            if (diff > 0.5) {
                slave.currentTime = expectedTime;
            }
        });
    }
}
```

---

## 📊 Performance

### Problema: 4 Videos Simultáneos = 4x Carga

**Consideraciones:**

1. **Ancho de Banda:**
   - 4 videos de 1080p @ 5Mbps = 20 Mbps
   - En conexión de 10 Mbps → buffering constante ❌

2. **CPU/GPU:**
   - Decodificar 4 videos simultáneos
   - En laptops antiguos → lag/stuttering

3. **Memoria:**
   - 4 buffers de video en memoria
   - Puede consumir 500MB - 1GB

---

### Soluciones de Performance

#### 1. Resolución Reducida para Slaves

```
Master (grande):  1080p (calidad original)
Slave 1:          720p  (calidad media)
Slave 2:          720p
Slave 3:          480p  (calidad baja si es thumbnail pequeño)
```

**Implementación:**
- Generar versiones de diferentes calidades al subir
- Servir calidad apropiada según tamaño del player

#### 2. Lazy Loading de Slaves

```javascript
// Solo cargar/reproducir slaves cuando son visibles
if (slave.isVisible && !slave.loaded) {
    slave.load();
    slave.play();
}

// Pausar slaves ocultos
if (!slave.isVisible && slave.playing) {
    slave.pause();
}
```

#### 3. Límite de Ángulos Simultáneos

```
Máximo: 4 ángulos (1 master + 3 slaves)
Recomendado: 2-3 ángulos para laptops
```

---

## 🔐 Gestión de Ángulos (UI para Analistas)

### En la Vista de Video

**Nueva sección:** "Ángulos Disponibles"

```
┌────────────────────────────────────────────────┐
│ 🎥 Ángulos de Cámara                           │
│                                                 │
│ Video Actual: Tribuna Central (Master) ✅      │
│                                                 │
│ Ángulos adicionales disponibles:               │
│                                                 │
│ □ Cámara Lateral Derecha      [Ver] [Config]   │
│ □ Cámara Try Zone              [Ver] [Config]   │
│ □ Cámara Aérea Drone           [Ver] [Config]   │
│                                                 │
│ [+ Asociar Nuevo Ángulo]                       │
└────────────────────────────────────────────────┘
```

**Funcionalidad:**
- ✅ Click en checkbox → muestra/oculta ese ángulo
- ✅ [Ver] → abre el ángulo en el layout multi-cámara
- ✅ [Config] → ajustar offset de sincronización
- ✅ [+ Asociar] → buscar otros videos del mismo partido y asociarlos

---

### En la Creación/Edición de Video

**Nuevo campo:** "¿Este video tiene otros ángulos?"

```
┌────────────────────────────────────────────────┐
│ Información del Video                           │
│                                                 │
│ Título: [Chile vs Argentina - Tribuna]         │
│ Fecha: [2024-03-15]                            │
│ Equipo: [Chile Rugby]                          │
│                                                 │
│ ☑️ Este video es parte de un grupo multi-cámara│
│                                                 │
│ Grupo: [Seleccionar existente ▼]               │
│        └─ O crear nuevo: [Nombre del Grupo]    │
│                                                 │
│ Rol en el grupo:                                │
│ ◉ Video Principal (Master)                     │
│ ○ Ángulo Adicional (Slave)                    │
│                                                 │
│ Si es ángulo adicional:                         │
│   Nombre del ángulo: [Lateral Derecha]         │
│   Offset: [+5.0] segundos                      │
│                                                 │
└────────────────────────────────────────────────┘
```

---

## 🎯 Clips y Timeline - Comportamiento

### Regla Principal: Solo el Master Tiene Clips

**Lógica:**
```
Video Master → tiene XML importado → clips definidos
Videos Slaves → NO tienen clips propios

Cuando reproduces un clip:
1. Master salta al timestamp del clip
2. Slaves automáticamente sincronizan (usando offset)
3. Todos los videos muestran ese momento desde su ángulo
```

**Ventaja:**
- ✅ No hay confusión (1 sola timeline)
- ✅ Clips están sincronizados perfectamente
- ✅ Analista marca eventos una sola vez (en master)

**Alternativa (Más Compleja):**
- Cada video puede tener sus propios clips
- Timeline muestra clips de todos los ángulos (colores diferentes)
- Click en clip → solo ese video salta (los demás siguen)

**Recomendación:** Solo master tiene clips (más simple)

---

## 🚀 Flujo de Trabajo del Analista

### Caso de Uso Completo

1. **Subir Videos**
   ```
   Analista sube 3 archivos:
   - tribuna_central.mp4
   - lateral_derecha.mp4
   - try_zone.mp4
   ```

2. **Crear Grupo Multi-Cámara**
   ```
   En "Editar Video" del master:
   ☑️ Parte de grupo multi-cámara
   Grupo: [Crear nuevo] "Chile vs Argentina - 2024-03-15"
   Rol: ◉ Video Principal (Master)
   ```

3. **Asociar Otros Ángulos**
   ```
   En la vista del video master:
   [+ Asociar Nuevo Ángulo]

   Buscar video:
   - lateral_derecha.mp4 → [Asociar]
   - try_zone.mp4 → [Asociar]

   Sistema automáticamente:
   - Detecta que tienen misma fecha de partido
   - Los marca como slaves
   - Calcula offset si es posible
   ```

4. **Importar XML (Solo en Master)**
   ```
   En video master:
   [Importar XML de LongoMatch]

   Sistema:
   - Crea 800 clips en la BD
   - Asocia clips al video master
   - Renderiza timeline
   ```

5. **Ver Múltiples Ángulos**
   ```
   En vista del video:
   Sección "Ángulos Disponibles"

   ☑️ Lateral Derecha [Ver]
   ☑️ Try Zone [Ver]

   Layout cambia a:
   - Master (arriba grande)
   - Lateral + Try Zone (abajo thumbnails)
   ```

6. **Reproducir y Analizar**
   ```
   - Click en clip "Try minuto 15"
   - Todos los videos saltan al minuto 15
   - Analista ve el try desde 3 ángulos
   - Puede pausar, agregar comentarios, anotaciones
   ```

---

## ⚠️ Desafíos Técnicos

### 1. Sincronización Perfecta Es Difícil

**Problema:**
- Videos capturados por cámaras diferentes
- No hay timestamp común
- Latencia de red variable

**Solución:**
- Permitir ajuste manual de offset
- UI para "afinar" sincronización en tiempo real
- Guardar offset cuando el usuario confirma

---

### 2. Performance con 800 Clips + 4 Videos

**Problema:**
- Timeline con 800 clips ya era pesado
- 4 videos reproduciéndose = más CPU

**Solución:**
- Virtual scrolling ya implementado ✅
- Thumbnails de baja calidad para slaves
- Lazy loading de videos no visibles
- Considerar límite de 2-3 ángulos simultáneos

---

### 3. Storage y Costo

**Problema:**
- 4 videos del mismo partido = 4x storage
- Bandwidth para servir 4 videos simultáneos

**Solución:**
- Opcional: Solo analistas pueden ver multi-cámara
- Jugadores ven solo master
- Compresión más agresiva para slaves
- CDN con buena política de cache

---

### 4. Complejidad de UI

**Problema:**
- Pantalla puede verse abrumadora
- Muchos controles nuevos

**Solución:**
- Modo "simple" por default (solo master)
- Modo "multi-cámara" opt-in (click en botón)
- Onboarding/tutorial para nuevos usuarios

---

## 📝 Resumen de Decisiones Clave

### Base de Datos
**Opción Recomendada:** Híbrido Simple
```sql
ALTER TABLE videos ADD COLUMN video_group_id BIGINT NULL;
ALTER TABLE videos ADD COLUMN is_master BOOLEAN DEFAULT true;
ALTER TABLE videos ADD COLUMN camera_angle VARCHAR(100) NULL;
ALTER TABLE videos ADD COLUMN sync_offset DECIMAL(8,2) DEFAULT 0;
```

### UI/UX
**Opción Recomendada:** Master Grande + Thumbnails
- Master ocupa 70% altura
- Slaves en thumbnails abajo
- Click para intercambiar

### Clips y Timeline
**Regla:** Solo master tiene clips
- 1 sola timeline (del master)
- Slaves se sincronizan automáticamente
- Más simple y menos confuso

### Performance
**Límites:**
- Máximo 4 ángulos simultáneos
- Thumbnails a 720p o menos
- Lazy loading de slaves

### Gestión
**Flujo:**
1. Crear grupo al subir/editar video master
2. Asociar slaves desde vista del master
3. Ajustar offset manualmente si es necesario
4. Importar XML solo en master

---

## 🎯 Implementación Propuesta (Fases)

### Fase 1: Base de Datos + Backend (1-2 días)
- Migración para agregar campos
- Modelo Video: relaciones de grupo
- API endpoints para gestionar grupos
- Lógica de sincronización

### Fase 2: UI Básica (2-3 días)
- Layout multi-cámara (master + thumbnails)
- Sección "Ángulos Disponibles"
- Asociar/desasociar ángulos
- Sincronización básica play/pause/seek

### Fase 3: Sincronización Avanzada (1-2 días)
- Ajuste manual de offset
- UI para "afinar" sincronización
- Verificación periódica de sync

### Fase 4: Performance + Polish (1-2 días)
- Lazy loading de slaves
- Calidades reducidas para thumbnails
- Testing con 800 clips + 4 videos
- UI/UX refinements

**Total Estimado:** 5-9 días de desarrollo

---

## 🤔 Preguntas para Definir Antes de Implementar

1. **¿Cuántos ángulos máximo se necesitan?**
   - 2-3 ángulos? (más común)
   - 4-6 ángulos? (broadcast profesional)

2. **¿Los ángulos siempre están sincronizados al inicio?**
   - Sí → más fácil (offset = 0)
   - No → necesitamos UI para ajustar offset

3. **¿Solo analistas/entrenadores ven multi-cámara?**
   - Sí → menos usuarios, menos carga
   - No → jugadores también (más carga)

4. **¿Los videos slaves también tendrán comentarios/anotaciones?**
   - Sí → más complejo (comentarios por ángulo)
   - No → solo master tiene interacción (más simple)

5. **¿Importancia de la sincronización perfecta?**
   - Crítica (análisis frame-by-frame) → offset preciso al milisegundo
   - Moderada (análisis general) → ±1 segundo está OK

---

**¿Qué te parece este análisis? ¿Hay algo que quieras que profundice más?** 🤔
