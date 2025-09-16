# 🏉 CLAUDE.md - Sistema de Análisis Rugby "Los Troncos"

## 📅 Última actualización: 2025-09-16

---

## 🎯 ESTADO ACTUAL DEL PROYECTO

### ✅ COMPLETADO RECIENTEMENTE:

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

## 🚧 **PRÓXIMA IMPLEMENTACIÓN - SISTEMA CATEGORÍAS DE USUARIO**

### 🎯 **PROBLEMA IDENTIFICADO (2025-09-16):**
- Videos por categoría (Juveniles, Adulta Primera, etc.) son visibles para TODOS los usuarios
- Falta identificar categoría del usuario en el registro
- Necesario filtro combinado: user_category + visibility_type

### 📋 **PLAN DE IMPLEMENTACIÓN:**

#### **FASE 1: Database Migration**
```sql
-- Archivo: database/migrations/YYYY_MM_DD_add_user_category_id_to_user_profiles_table.php
ALTER TABLE user_profiles ADD COLUMN user_category_id INT AFTER division_category;
ALTER TABLE user_profiles ADD FOREIGN KEY (user_category_id) REFERENCES categories(id);
```

#### **FASE 2: Models Update**
```php
// app/Models/UserProfile.php
protected $fillable = [..., 'user_category_id'];
public function category() { return $this->belongsTo(Category::class, 'user_category_id'); }

// app/Models/Video.php - scopeVisibleForUser() actualizado
// Filtro combinado: category_id + visibility_type
```

#### **FASE 3: Registration Form**
```html
<!-- resources/views/auth/register.blade.php -->
<select name="user_category_id" required>
  <option value="1">Juveniles</option>
  <option value="2">Adulta Primera</option>
  <!-- etc -->
</select>
```

#### **FASE 4: Controller Updates**
```php
// app/Http/Controllers/Auth/RegisterController.php
// Validación + storage de user_category_id
// Pasar $categories a vista register
```

### 🎯 **LÓGICA FINAL ESPERADA:**
```
Video "Juveniles" + "Forwards" =
├─ Usuario categoría "Juveniles" + posición Forward: ✅ Lo ve
├─ Usuario categoría "Juveniles" + posición Back: ❌ No lo ve
├─ Usuario categoría "Adulta" + cualquier posición: ❌ No lo ve
└─ Analistas/Entrenadores: ✅ Lo ven (sin filtro)
```

### ⚠️ **RIESGOS Y CONSIDERACIONES:**
- **24 jugadores existentes** tendrán user_category_id = NULL
- **Migración nullable** inicialmente para no romper sistema
- **Fallback logic** para usuarios sin categoría asignada
- **Data migration manual** necesaria para usuarios existentes

### 📁 **ARCHIVOS A MODIFICAR:**
- ✅ `database/migrations/` - Nueva migración user_category_id
- ✅ `app/Models/UserProfile.php` - Agregar fillable + relación
- ✅ `app/Models/Video.php` - Actualizar scopeVisibleForUser()
- ✅ `app/Http/Controllers/Auth/RegisterController.php` - Validación + storage
- ✅ `resources/views/auth/register.blade.php` - Selector de categoría
- ❌ `app/Http/Controllers/VideoController.php` - NO TOCAR (ya funciona)
- ❌ `resources/views/videos/` - NO TOCAR (filtro transparente)

---

## 🚀 PRÓXIMAS TAREAS (Para mañana):

### 1. **Perfil del Jugador** 
- [ ] Crear vista detallada del perfil de jugador
- [ ] Mostrar información personal, posiciones, estadísticas
- [ ] Dashboard personalizado para jugadores
- [ ] Videos asignados y completados

### 2. **Perfiles de Entrenadores**
- [ ] Vista de entrenador para analizar jugadores
- [ ] Filtros por jugador específico
- [ ] Panel de análisis con métricas por jugador
- [ ] Comparación entre jugadores

### 3. **Sistema de Filtros**
- [ ] Filtros por jugador en videos
- [ ] Filtros por categoría y posición
- [ ] Búsqueda avanzada de análisis

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

### **Nuevos:**
- `app/Http/Controllers/VideoStreamController.php` - Range requests para video
- `database/migrations/2025_09_15_050000_add_player_fields_to_user_profiles_table.php`
- `database/migrations/2025_09_15_060000_remove_experience_level_from_user_profiles_table.php`
- `public/test-video.html` - Página de diagnóstico de video

### **Modificados:**
- `resources/views/videos/show.blade.php` - Timeline funcional completo
- `resources/views/auth/register.blade.php` - Formulario optimizado con CSS
- `app/Models/UserProfile.php` - Campos actualizados
- `app/Http/Controllers/Auth/RegisterController.php` - Sin experience_level
- `routes/web.php` - Rutas de streaming agregadas
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