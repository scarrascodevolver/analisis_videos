# Rugby Analysis System - Arquitectura del Proyecto

## 1. Estructura de Carpetas

```
rugby-analysis-system/
├── app/
│   ├── Console/Commands/        # Comandos Artisan personalizados
│   ├── Http/
│   │   ├── Controllers/         # Controladores principales
│   │   │   └── Auth/            # Autenticación (Login, Register, etc.)
│   │   └── Middleware/          # Middleware (RoleMiddleware, etc.)
│   ├── Jobs/                    # Jobs de cola (CompressVideoJob)
│   ├── Mail/                    # Plantillas de correo
│   ├── Models/                  # Modelos Eloquent (14 modelos)
│   ├── Notifications/           # Notificaciones Laravel
│   └── Providers/               # Service Providers
├── config/                      # Configuración (filesystems, etc.)
├── database/
│   ├── factories/               # Factories para testing
│   ├── migrations/              # 37 migraciones
│   └── seeders/                 # Seeders de datos
├── public/
│   ├── build/assets/            # Assets compilados (Vite)
│   └── vendor/                  # Vendor público (AdminLTE, etc.)
├── resources/
│   ├── views/
│   │   ├── admin/               # Vistas de administración
│   │   ├── assignments/         # Asignaciones de videos
│   │   ├── auth/                # Login, Register, Passwords
│   │   ├── dashboards/          # Dashboards por rol
│   │   ├── evaluations/         # Sistema de evaluaciones
│   │   ├── layouts/             # Layout principal (app.blade.php)
│   │   ├── my-videos/           # Videos asignados al usuario
│   │   ├── profile/             # Perfil de usuario
│   │   └── videos/              # CRUD de videos
│   └── lang/es/                 # Traducciones español
├── routes/
│   └── web.php                  # Rutas principales (~246 líneas)
└── storage/
    └── app/public/
        ├── avatars/             # Fotos de perfil
        └── thumbnails/          # Miniaturas de videos
```

---

## 2. Modelos y Relaciones

### User (Usuario principal)
```php
// Campos: name, email, password, phone, role
// Roles: 'jugador', 'entrenador', 'analista', 'staff', 'director_tecnico'

hasOne(UserProfile::class)           // Perfil extendido
hasMany(Video::class, 'uploaded_by') // Videos subidos
hasMany(VideoComment::class)         // Comentarios
hasMany(VideoAssignment::class, 'assigned_to')  // Videos asignados a mí
hasMany(VideoAssignment::class, 'assigned_by')  // Videos que yo asigné
hasMany(VideoAnnotation::class)      // Anotaciones en videos
hasMany(PlayerEvaluation::class, 'evaluated_player_id')  // Evaluaciones recibidas
hasMany(PlayerEvaluation::class, 'evaluator_id')         // Evaluaciones dadas
```

### UserProfile (Perfil extendido)
```php
// Campos: position, secondary_position, player_number, weight, height,
//         date_of_birth, user_category_id, avatar, can_receive_assignments

belongsTo(User::class)
belongsTo(Category::class, 'user_category_id')  // Categoría del jugador (Juvenil, Adulta, etc.)
```

### Video
```php
// Campos: title, description, file_path, thumbnail_path, file_size, duration,
//         category_id, visibility_type, division, match_date, processing_status

belongsTo(User::class, 'uploaded_by')     // Quien lo subió
belongsTo(Team::class, 'analyzed_team_id') // Equipo analizado
belongsTo(Team::class, 'rival_team_id')    // Equipo rival
belongsTo(Category::class)                 // Categoría (Juvenil, Adulta, etc.)
belongsTo(RugbySituation::class)           // Situación de juego
hasMany(VideoComment::class)               // Comentarios
hasMany(VideoAssignment::class)            // Asignaciones a jugadores
hasMany(VideoAnnotation::class)            // Anotaciones/dibujos
hasMany(VideoView::class)                  // Tracking de vistas

// Scopes importantes:
scopeVisibleForUser($user)    // Filtro por rol + categoría + visibilidad
scopeTeamVisible($user)       // Videos del equipo (sin específicos)
scopeMyAssignedVideos($user)  // Solo mis asignaciones
scopeCoachVisible($user)      // Filtro para entrenadores
```

### VideoAssignment
```php
// Campos: video_id, assigned_to, assigned_by, notes, comment_id

belongsTo(Video::class)
belongsTo(User::class, 'assigned_to')   // Jugador asignado
belongsTo(User::class, 'assigned_by')   // Quien asignó
belongsTo(VideoComment::class, 'comment_id')  // Si vino de mención
```

### VideoComment
```php
// Campos: video_id, user_id, parent_id, comment, timestamp_seconds, category, priority

belongsTo(Video::class)
belongsTo(User::class)
belongsTo(VideoComment::class, 'parent_id')  // Respuesta a otro comentario
hasMany(VideoComment::class, 'parent_id')    // Respuestas
hasMany(CommentMention::class)               // Menciones @usuario
belongsToMany(User::class, 'comment_mentions')  // Usuarios mencionados
```

### VideoAnnotation
```php
// Campos: video_id, user_id, timestamp, annotation_data (JSON),
//         annotation_type (arrow, circle, rectangle, freehand, text, canvas),
//         duration_seconds, is_permanent, is_visible

belongsTo(Video::class)
belongsTo(User::class)
```

### Category (Categorías de edad)
```php
// Campos: name, description
// Ejemplos: "Juveniles", "Adulta", "M19"

hasMany(Video::class)
hasMany(UserProfile::class, 'user_category_id')
```

### Team
```php
// Campos: name, abbreviation, is_own_team

hasMany(Video::class, 'analyzed_team_id')
hasMany(Video::class, 'rival_team_id')
scopeOwnTeam()    // Nuestro equipo
scopeRivalTeams() // Rivales
```

### PlayerEvaluation
```php
// Campos: evaluator_id, evaluated_player_id, evaluation_period_id,
//         + ~30 campos de habilidades (resistencia, velocidad, tackle, etc.)

belongsTo(User::class, 'evaluator_id')
belongsTo(User::class, 'evaluated_player_id')
belongsTo(EvaluationPeriod::class)
```

### Otros Modelos
- **RugbySituation**: Situaciones de juego (Scrum, Lineout, Ataque, etc.)
- **VideoView**: Tracking de reproducciones
- **CommentMention**: Menciones @usuario en comentarios
- **EvaluationPeriod**: Períodos de evaluación
- **Setting**: Configuraciones del sistema

---

## 3. Tablas Principales (Migraciones)

### users
```
id, name, email, password, phone, role, email_verified_at, timestamps
```

### user_profiles
```
id, user_id (FK), position, secondary_position, player_number,
weight, height, date_of_birth, user_category_id (FK→categories),
avatar, can_receive_assignments, timestamps
```

### videos
```
id, title, description, file_path, thumbnail_path, file_name, file_size (bigint),
mime_type, duration, uploaded_by (FK→users), analyzed_team_id (FK→teams),
rival_team_id (FK→teams), category_id (FK→categories), division,
rugby_situation_id, match_date, status, visibility_type (enum: public/forwards/backs/specific),
processing_status, original_file_size, compressed_file_size, compression_ratio, timestamps
```

### video_assignments
```
id, video_id (FK), assigned_to (FK→users), assigned_by (FK→users),
notes, comment_id (FK→video_comments nullable), timestamps
```

### video_comments
```
id, video_id (FK), user_id (FK), parent_id (FK→video_comments nullable),
comment, timestamp_seconds, category, priority, status, timestamps
```

### video_annotations
```
id, video_id (FK), user_id (FK), timestamp (decimal),
annotation_data (JSON), annotation_type (enum), duration_seconds,
is_permanent, is_visible, timestamps
```

### categories
```
id, name, description, timestamps
```

### teams
```
id, name, abbreviation, is_own_team (boolean), timestamps
```

### player_evaluations
```
id, evaluator_id (FK), evaluated_player_id (FK), evaluation_period_id (FK),
resistencia, velocidad, musculatura, tackle, ruck, ... (30+ campos),
total_score, timestamps
```

---

## 4. Storage de Videos

### Configuración (config/filesystems.php)
```php
'disks' => [
    'local'  => ['driver' => 'local', 'root' => storage_path('app/private')],
    'public' => ['driver' => 'local', 'root' => storage_path('app/public')],

    // DigitalOcean Spaces (S3-compatible) - PRODUCCIÓN
    'spaces' => [
        'driver' => 's3',
        'key' => env('DO_SPACES_KEY'),
        'secret' => env('DO_SPACES_SECRET'),
        'endpoint' => env('DO_SPACES_ENDPOINT'),
        'region' => env('DO_SPACES_REGION', 'sfo3'),
        'bucket' => env('DO_SPACES_BUCKET'),
        'url' => env('DO_SPACES_CDN_URL'),
    ],
]
```

### Flujo de Upload (VideoController@store)
```php
// 1. Validar archivo (max 8GB, formatos: mp4, mov, avi, webm, mkv)
// 2. Sanitizar nombre de archivo
$filename = time() . '_' . $sanitizedName;

// 3. Intentar subir a DigitalOcean Spaces
try {
    $path = $file->storeAs('videos', $filename, 'spaces');
    Storage::disk('spaces')->setVisibility($path, 'public');
} catch (Exception $e) {
    // Fallback a storage local
    $path = $file->storeAs('videos', $filename, 'public');
}

// 4. Crear registro en DB
// 5. Dispatch job de compresión: CompressVideoJob::dispatch($video->id)
```

### Streaming (VideoStreamController)
```php
// Streaming directo a través de Laravel (no redirects)
// Soporta HTTP Range requests para seeking
Route::get('videos/{video}/stream', [VideoStreamController::class, 'stream']);
```

### Ubicaciones de Archivos
- **Producción**: DigitalOcean Spaces (`videos/`)
- **Local/Fallback**: `storage/app/public/videos/`
- **Avatares**: `storage/app/public/avatars/`
- **Thumbnails**: `storage/app/public/thumbnails/`

---

## 5. Rutas Principales (routes/web.php)

### Públicas
```php
Route::redirect('/', '/login');
Auth::routes();  // Login, Register, Password Reset
Route::get('videos/{video}/stream', ...);  // Streaming de video
```

### Autenticadas (middleware: auth)
```php
// Dashboard
Route::get('/home', [HomeController::class, 'index']);
Route::get('/dashboard', [HomeController::class, 'index']);

// Videos CRUD
Route::resource('videos', VideoController::class);
Route::post('videos/{video}/comments', ...);

// APIs AJAX
Route::get('api/players/search', ...);
Route::prefix('api/annotations')->group(...);  // CRUD anotaciones
Route::prefix('api/videos')->group(...);       // Tracking de vistas

// Mis Videos
Route::get('my-videos', ...);
Route::patch('assignments/{assignment}/complete', ...);

// Notificaciones
Route::prefix('notifications')->group(...);

// Perfil
Route::get('/profile', ...);
Route::put('/profile', ...);
```

### Por Rol

#### Analistas/Entrenadores (middleware: role:analista,entrenador)
```php
Route::prefix('analyst')->group(function() {
    Route::get('/dashboard', ...);
    Route::resource('assignments', ...);  // Gestión de asignaciones
});

Route::prefix('admin')->group(function() {
    Route::resource('categories', ...);   // CRUD categorías
    Route::resource('teams', ...);        // CRUD equipos
    Route::resource('situations', ...);   // CRUD situaciones rugby
    Route::resource('users', ...);        // CRUD usuarios
});

Route::prefix('coach')->group(function() {
    Route::get('/videos', ...);
    Route::get('/users', ...);            // Lista de jugadores
    Route::get('/player/{user}', ...);    // Perfil de jugador
});
```

#### Jugadores (middleware: role:jugador)
```php
Route::prefix('player')->group(function() {
    Route::get('/videos', ...);      // Videos asignados
    Route::get('/completed', ...);   // Videos completados
    Route::get('/pending', ...);     // Videos pendientes
    Route::get('/upload', ...);      // Subir video propio
});
```

### Evaluaciones
```php
Route::get('/evaluacion', ...);                    // Lista de evaluaciones
Route::get('/evaluacion/wizard/{player}', ...);   // Evaluar a un jugador
Route::post('/evaluacion/store', ...);            // Guardar evaluación
Route::get('/evaluacion/resultados', ...);        // Dashboard resultados
Route::get('/evaluacion/periodos', ...);          // Gestión de períodos
```

---

## 6. Sistema de Visibilidad de Videos

### Tipos de Visibilidad (visibility_type)
```php
'public'   → Todos los jugadores de la categoría lo ven
'forwards' → Solo jugadores con posición de Forward
'backs'    → Solo jugadores con posición de Back
'specific' → Solo jugadores específicamente asignados
```

### Lógica de Filtrado (Video::scopeVisibleForUser)
```php
// Staff (analista, entrenador, director_tecnico) → Ve TODO
// Jugador → Filtra por:
//   1. category_id = user_category_id (misma categoría)
//   2. visibility_type = 'public' OR
//      visibility_type = playerCategory (forwards/backs) OR
//      tiene asignación directa
```

### Determinación Forward/Back
```php
Video::getPlayerCategory($position)
// Forward: posiciones 1-8 (props, hooker, locks, flankers, N°8)
// Back: posiciones 9-15 (medio, apertura, centros, wings, fullback)
```

---

## 7. Autenticación y Roles

### Roles Disponibles
- `jugador` - Ve videos asignados y de su categoría
- `entrenador` - Ve videos de su categoría asignada, gestiona jugadores
- `analista` - Acceso completo, sube y asigna videos
- `staff` - Acceso limitado según configuración
- `director_tecnico` - Acceso completo

### Middleware de Roles
```php
// app/Http/Middleware/RoleMiddleware.php
Route::middleware(['role:analista,entrenador'])->group(...);
```

---

## 8. Tecnologías

- **Framework**: Laravel 12
- **Frontend**: Blade + AdminLTE + Bootstrap 4
- **Base de Datos**: MySQL
- **Storage**: DigitalOcean Spaces (S3) + Local fallback
- **Cola de Jobs**: Laravel Queue (compresión de videos)
- **Video Player**: HTML5 + controles personalizados
- **Anotaciones**: Fabric.js (canvas)
- **Build**: Vite
