# 🏉 CLAUDE.md - Sistema de Análisis Rugby "Los Troncos"

## 📅 Última actualización: 2025-09-17

---

## 🎯 ESTADO ACTUAL DEL PROYECTO

### ✅ COMPLETADO RECIENTEMENTE:

#### 9. **Sistema de Perfil de Usuario con Avatar - COMPLETADO** (2025-09-17)
- ✅ **Migración avatar**: Campo `avatar` agregado a `user_profiles` table
- ✅ **ProfileController**: CRUD completo con upload/eliminación de avatar
- ✅ **Rutas de perfil**: `/profile`, `/profile/edit`, eliminación de avatar
- ✅ **Vistas completas**: `profile/show.blade.php` y `profile/edit.blade.php`
- ✅ **UI actualizada**: Sidebar con avatar clickeable + dropdown menú superior
- ✅ **Validación**: Imágenes JPEG/PNG/JPG/GIF, máx 2MB, preview instantáneo
- ✅ **Storage**: Gestión segura con eliminación de archivos anteriores

#### 8. **Sistema de Categorías de Usuario - COMPLETADO** (2025-09-17)
- ✅ **VPS sincronizado**: Rama `funcionalidad/categorias-usuario` desplegada en producción
- ✅ **Migraciones ejecutadas**: user_category_id, visibility_type, thumbnails funcionando
- ✅ **UserSeeder ejecutado**: Staff completo creado (Jeremías, Juan Cruz, Valentín, Víctor)
- ✅ **Datos limpios**: Solo 3 jugadores esenciales (uno por categoría)
- ✅ **Sistema funcionando**: Login con categorías, filtros de video por categoría
- ✅ **Credenciales activas**: Jeremías Rodríguez (jere@clublostroncos.cl / jere2025)

#### 7. **Sistema de Visibilidad por Categorías - Frontend** (2025-09-16)

#### 1. **Timeline de Comentarios Funcional** (2025-09-15)
- ✅ **VideoStreamController**: Implementado con soporte Range requests HTTP para seeking perfecto
- ✅ **Timeline interactivo**: Barra de progreso verde (#1e4d2b), marcadores de comentarios clickeables
- ✅ **Notificaciones**: Aparecen automáticamente cuando hay comentarios en el tiempo actual
- ✅ **Seeking nativo**: Los controles HTML5 funcionan perfectamente ahora

#### 2. **Formulario de Registro Optimizado** (2025-09-15)
- ✅ **Campo experience_level eliminado**: Migración completa sin romper la DB
- ✅ **Posición secundaria agregada**: Campo opcional para jugadores
- ✅ **CSS mejorado**: Selects completamente visibles, sin texto cortado
- ✅ **Campos simplificados**: Solo lo esencial (posiciones, peso, altura, fecha nacimiento)
- ✅ **Ícono rugby**: Cambió de fútbol soccer a fútbol americano/rugby 🏈

#### 3. **Base de Datos Actualizada**
- ✅ **Migración 2025_09_15_050000**: Agregó campos faltantes (secondary_position, player_number, weight, height, etc.)
- ✅ **Migración 2025_09_15_060000**: Eliminó experience_level completamente
- ✅ **Seeders actualizados**: UserSeeder, PlayersSeeder, DatabaseSeeder sin experience_level
- ✅ **Modelo UserProfile**: Actualizado con todos los campos correctos

#### 4. **Nuevos Usuarios Staff "Los Troncos"** (2025-09-15)
- ✅ **Jeremías** (jere@clublostroncos.cl / jere2025) - Analista de Video
- ✅ **Juan Cruz Fleitas** (juancruz@clublostroncos.cl / juancruz2025) - Entrenador Principal
- ✅ **Valentín Dapena** (valentin@clublostroncos.cl / valentin2025) - Entrenador Asistente
- ✅ **Víctor Escobar** (victor@clublostroncos.cl / victor2025) - Entrenador de Forwards
- ✅ **Juan Carlos Rodríguez** (juancarlos@clublostroncos.cl / juancarlos2025) - Director de Club

#### 5. **Sistema de Visibilidad por Categorías - Video Thumbnails** (2025-09-16)
- ✅ **Video Thumbnails HTML5+Canvas**: Generación automática de miniaturas reales del video
- ✅ **Cards optimizadas**: Tamaño reducido (120px), sin efectos de carga molestos
- ✅ **Título overflow**: Limitado a 2 líneas con ellipsis automático
- ✅ **Rama feature/video-thumbnails**: Implementación completa y funcional

#### 6. **Sistema de Visibilidad por Categorías - Backend** (2025-09-16)
- ✅ **Campo visibility_type**: Enum('public', 'forwards', 'backs', 'specific') en videos
- ✅ **Modelo Video actualizado**: Scope visibleForUser() y getPlayerCategory() helper
- ✅ **Validación y storage**: VideoController maneja nuevos tipos de visibilidad
- ✅ **Filtrado automático**: Index aplica filtros según rol y posición del usuario

#### 7. **Sistema de Visibilidad por Categorías - Frontend** (2025-09-16)
- ✅ **Radio buttons visibilidad**: 4 opciones claras en formulario de subida
- ✅ **JavaScript condicional**: Selector de jugadores aparece solo si "Específicos"
- ✅ **Estilos rugby**: Tema verde con hover y estados activos
- ✅ **Bug corregido**: getPlayerCategory() maneja posiciones de texto correctamente

---

## 🎉 **SISTEMA COMPLETADO - CATEGORÍAS DE USUARIO FUNCIONANDO**

### ✅ **IMPLEMENTACIÓN EXITOSA (2025-09-17):**

El sistema de categorías de usuario está **100% funcional** tanto en desarrollo como en producción:

#### **🔧 ARQUITECTURA IMPLEMENTADA:**
```php
// Filtro combinado: user_category_id + visibility_type
Video::visibleForUser($user)
    ->where('category_id', $user->profile->user_category_id)  // Solo su categoría
    ->where(function($q) use ($user) {
        $q->where('visibility_type', 'public')                // Videos públicos
          ->orWhere('visibility_type', $playerCategory)        // Su posición (forwards/backs)
          ->orWhereHas('assignments', fn($q) => $q->where('assigned_to', $user->id)); // Específicos
    });
```

#### **👥 USUARIOS DE PRODUCCIÓN:**
- **Analistas**: Jeremías Rodríguez (jere@clublostroncos.cl) - Ve todos los videos
- **Entrenadores**: Juan Cruz, Valentín, Víctor - Ven todos los videos
- **Jugadores**: 3 usuarios (uno por categoría) - Ven solo SU categoría

#### **🎯 LÓGICA FINAL FUNCIONANDO:**
```
✅ Usuario "Juveniles" + Video "Juveniles Forwards" = Lo ve
❌ Usuario "Juveniles" + Video "Adulta Forwards" = No lo ve
❌ Usuario "Adulta" + Video "Juveniles Backs" = No lo ve
✅ Analistas/Entrenadores = Ven todos sin filtro
```

---

## 🛠️ **FIX IMPLEMENTADO - STREAMING DE VIDEO** (2025-09-20)

### ✅ **PROBLEMA:**
Videos subidos desde un dispositivo no se veían en el otro (PC ↔ móvil)

### 🔍 **CAUSA:**
VideoStreamController hacía **redirect** a URLs firmadas de DigitalOcean Spaces que fallan entre dispositivos

### 💡 **SOLUCIÓN:**
Streaming directo a través de Laravel sin redirects externos

```php
// VideoStreamController.php
// ANTES: return redirect($signedUrl);
// AHORA: return $this->streamFromSpaces($video, $request);
```

### 🎯 **RESULTADO:**
- ✅ Compatibilidad PC ↔ móvil
- ✅ Seeking/timeline funciona
- ✅ Fallback automático local

---

## 🚀 PRÓXIMAS TAREAS (Inmediatas):

### 1. **Avatar en Cards de Jugadores - RAMA: feature/player-avatar-cards**
- [ ] **Actualizar vista coach/users**: Mostrar avatar del jugador en cada card
- [ ] **Modificar cards verticales**: Integrar foto de perfil como header
- [ ] **Placeholder por defecto**: Ícono cuando no hay avatar subido
- [ ] **Responsive design**: Asegurar que funcione en móvil
- [ ] **Commit y merge**: Una vez implementado, mergear a main

### 2. **Sistema de Filtros Avanzado**
- [ ] Filtros por jugador en videos
- [ ] Filtros por categoría y posición
- [ ] Búsqueda avanzada de análisis

### 3. **Dashboard Mejorado**
- [ ] Panel de análisis con métricas por jugador
- [ ] Comparación entre jugadores
- [ ] Estadísticas de progreso

---

## 🗂️ ESTRUCTURA ACTUAL

### **Rutas Principales:**
```php
// Video streaming con Range support
Route::get('videos/{video}/stream', [VideoStreamController::class, 'stream']);

// Rutas de jugador
Route::middleware(['role:jugador'])->prefix('player')->group(function () {
    Route::get('/videos', [DashboardController::class, 'playerVideos']);
    Route::get('/upload', [VideoController::class, 'playerUpload']);
});

// Rutas de entrenador  
Route::middleware(['role:entrenador'])->prefix('coach')->group(function () {
    Route::get('/videos', [DashboardController::class, 'coachVideos']);
    Route::get('/users', [DashboardController::class, 'coachUsers']);
});
```

### **Modelos Actualizados:**
```php
// UserProfile - Campos actuales
protected $fillable = [
    'user_id', 'position', 'secondary_position', 'player_number',
    'weight', 'height', 'date_of_birth', 'goals', 'coaching_experience',
    'certifications', 'specializations', 'club_team_organization', 'division_category'
];
```

### **Base de Datos:**
- ✅ **user_profiles**: Con todos los campos necesarios para jugadores y entrenadores
- ✅ **videos**: Con sistema de comentarios y timeline
- ✅ **video_assignments**: Sistema de asignación a jugadores
- ✅ **video_comments**: Comentarios con timestamp para timeline

---

## 🎨 CARACTERÍSTICAS TÉCNICAS

### **Video System:**
- **Range Requests**: VideoStreamController maneja HTTP 206 para seeking perfecto
- **Timeline**: Marcadores verdes para comentarios, barra de progreso rugby
- **Notificaciones**: Auto-show cuando el video está en tiempo de comentario
- **Formatos**: MP4, MOV, AVI, WEBM, MKV hasta 2GB (analistas), 1GB (jugadores)

### **Autenticación:**
- **Roles**: jugador, entrenador, analista, staff
- **Middleware**: role-based access control
- **Registro**: Formulario en 2 pasos con perfil rugby específico

### **UI/UX:**
- **Tema**: Verde rugby (#1e4d2b) - "Los Troncos"
- **Framework**: Laravel 12 + AdminLTE + Bootstrap 4
- **Responsive**: Mobile-friendly con CSS mejorado

---

## 🔧 COMANDOS ÚTILES

### **Migraciones:**
```bash
php artisan migrate:status
php artisan migrate
php artisan migrate:rollback
```

### **Seeders:**
```bash
php artisan db:seed --class=PlayersSeeder
php artisan db:seed --class=UserSeeder
```

### **Testing:**
- **Página de prueba**: `/test-video.html` para diagnosticar video issues
- **Lint/Type check**: `npm run lint`, `npm run typecheck` (si disponibles)

---

## 📁 ARCHIVOS CLAVE MODIFICADOS HOY

### **Sistema de Perfil con Avatar (2025-09-17):**
- `database/migrations/2025_09_17_120000_add_avatar_to_user_profiles_table.php` - Campo avatar
- `app/Http/Controllers/ProfileController.php` - CRUD perfil con avatar
- `resources/views/profile/show.blade.php` - Vista de perfil completa
- `resources/views/profile/edit.blade.php` - Formulario con upload de imagen
- `resources/views/layouts/app.blade.php` - Sidebar y menú con avatar
- `routes/web.php` - Rutas de perfil agregadas

### **Anteriores:**
- `app/Http/Controllers/VideoStreamController.php` - Range requests para video
- `database/migrations/2025_09_15_050000_add_player_fields_to_user_profiles_table.php`
- `database/migrations/2025_09_15_060000_remove_experience_level_from_user_profiles_table.php`
- `public/test-video.html` - Página de diagnóstico de video

### **Modificados:**
- `resources/views/videos/show.blade.php` - Timeline funcional completo
- `resources/views/auth/register.blade.php` - Formulario optimizado con CSS
- `app/Models/UserProfile.php` - Campos actualizados
- `app/Http/Controllers/Auth/RegisterController.php` - Sin experience_level
- Todos los seeders actualizados

---

## 🐛 ISSUES RESUELTOS

1. **Video seeking no funcionaba**: ✅ Resuelto con VideoStreamController
2. **Texto cortado en selects**: ✅ CSS mejorado con altura y padding adecuados  
3. **Error experience_level null**: ✅ Campo eliminado completamente
4. **Layout roto de comentarios**: ✅ HTML duplicado eliminado
5. **Timeline no aparecía**: ✅ JavaScript restaurado y optimizado

---

## 💡 NOTAS PARA EL DESARROLLO

### **Avatar en Cards de Jugadores:**
```blade
{{-- Estructura básica para cards con avatar --}}
<div class="card h-100">
    <div class="card-header text-center p-2">
        @if($player->profile && $player->profile->avatar)
            <img src="{{ asset('storage/' . $player->profile->avatar) }}"
                 alt="Avatar"
                 class="img-circle elevation-2"
                 style="width: 60px; height: 60px; object-fit: cover;">
        @else
            <i class="fas fa-user-circle fa-3x text-muted"></i>
        @endif
    </div>
    <div class="card-body">
        <h6 class="card-title">{{ $player->name }}</h6>
        {{-- resto del contenido --}}
    </div>
</div>
```

### **Implementación Técnica:**
- **Archivo a modificar**: `resources/views/dashboards/coach-users.blade.php`
- **Relación necesaria**: `$players->load('profile')` en controller
- **Placeholder**: Usar `fa-user-circle` cuando no hay avatar
- **Tamaño imagen**: 60px x 60px para cards, object-fit: cover

### **Actualización del VPS:**
```bash
# 1. Conectar al VPS por SSH
ssh usuario@ip_del_vps

# 2. Ir al directorio del proyecto
cd /var/www/html/rugby-analysis-system

# 3. Hacer pull de los cambios
git pull origin main

# 4. Ejecutar migraciones nuevas (CRÍTICO para avatar)
php artisan migrate

# 5. Limpiar caché de Laravel
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 6. Verificar permisos de storage (para avatares)
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/

# 7. Asegurar que el symlink de storage existe
php artisan storage:link
```

### **Verificación Post-Actualización:**
- ✅ Login con usuarios existentes funciona
- ✅ Acceso a `/profile` y `/profile/edit`
- ✅ Upload de avatar y preview funcionando
- ✅ Sidebar y menú muestran avatares
- ✅ Migraciones aplicadas sin errores

### **Otras Notas:**
- **No usar experience_level**: Campo eliminado permanentemente
- **Usar rutas de streaming**: Para videos usar `/videos/{id}/stream`
- **CSS rugby**: Clase `.rugby-select` para selects optimizados
- **Colors**: Verde principal `#1e4d2b`, secundario `#28a745`
- **Testing**: Siempre probar video seeking después de cambios de JS

---

## 🎯 OBJETIVOS PRINCIPALES PENDIENTES

1. **Dashboard jugador completo**
2. **Análisis por entrenadores**  
3. **Métricas y estadísticas**
4. **Comparación de jugadores**
5. **Exportación de reportes**

---

*📝 Documentado por Claude Code - Sistema en desarrollo activo*