# 🏉 CLAUDE.md - Sistema de Análisis Rugby "Los Troncos"

## 📅 Última actualización: 2025-09-15

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