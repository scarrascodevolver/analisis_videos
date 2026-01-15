# CLAUDE.md - RugbyHub (Sistema Multi-Tenant)

## Ultima actualizacion: 2026-01-12

---

## ESTADO ACTUAL DEL PROYECTO

### Sistema Multi-Tenant COMPLETADO (Enero 2026)

El sistema evolucion de "Los Troncos" a **RugbyHub** - plataforma multi-tenant para clubes de rugby.

#### Fases Implementadas:
- **Fase 1**: Super Admin panel (`bbd8d7e5`)
- **Fase 2**: Crear tablas `organizations` y `organization_user` (`051ef287`)
- **Fase 3**: Agregar `organization_id` a tablas existentes (`c42b3cc3`)
- **Fase 4**: Migrar datos existentes a organizacion default (`eae06706`)
- **Fase 5**: Ejecutar migraciones (`27501497`)
- **Fase 6**: Global Scopes y Trait `BelongsToOrganization` (`0f13bc99`)
- **Fase 7**: Selector de organizacion UI (`e2d093e1`)
- **Fase 8**: Storage por organizacion (`accb26c8`)

#### Funcionalidades Post Multi-Tenancy:
- Sistema de codigos de invitacion para registro de jugadores
- Equipos opcionales con rival como texto libre
- Branding dinamico (logo/nombre por organizacion)
- Comentarios via AJAX sin recarga
- UI actualizada: colores verde rugby (#00B7B5 accent)

---

## ARQUITECTURA MULTI-TENANT

### Modelos Principales:
```
Organization (tenant principal)
├── Users (via organization_user pivot)
├── Videos
├── Teams
├── Categories
├── PlayerEvaluations
└── EvaluationPeriods
```

### Trait BelongsToOrganization:
```php
// Aplica Global Scope automatico por organizacion
use App\Traits\BelongsToOrganization;

class Video extends Model {
    use BelongsToOrganization;
}
```

### Storage por Organizacion:
```php
// Videos se guardan en: videos/{org-slug}/filename.mp4
$orgSlug = auth()->user()->currentOrganization()->slug;
$path = $file->storeAs("videos/{$orgSlug}", $filename, 'spaces');
```

### Comando de Migracion:
```bash
# Simular migracion de videos existentes
php artisan videos:migrate-to-org-folders --dry-run

# Ejecutar migracion real
php artisan videos:migrate-to-org-folders

# Solo una organizacion
php artisan videos:migrate-to-org-folders --org=los-troncos
```

---

## MODELOS DEL SISTEMA

| Modelo | Descripcion |
|--------|-------------|
| `Organization` | Tenant (club/equipo) |
| `User` | Usuario con roles |
| `UserProfile` | Perfil extendido (posicion, avatar, etc.) |
| `Video` | Videos de partidos/entrenamientos |
| `VideoComment` | Comentarios con timestamp |
| `VideoAnnotation` | Dibujos/marcas en video |
| `VideoAssignment` | Asignacion de videos a jugadores |
| `VideoView` | Tracking de visualizaciones |
| `Team` | Equipos de la organizacion |
| `Category` | Categorias (Juveniles, Adulta, etc.) |
| `PlayerEvaluation` | Evaluaciones de jugadores |
| `EvaluationPeriod` | Periodos de evaluacion |
| `CommentMention` | Menciones en comentarios |
| `Setting` | Configuraciones del sistema |

---

## RUTAS PRINCIPALES

```php
// Video streaming
Route::get('videos/{video}/stream', [VideoStreamController::class, 'stream']);

// Selector de organizacion
Route::post('/organization/switch', [OrganizationController::class, 'switch']);

// Super Admin
Route::prefix('super-admin')->middleware('super-admin')->group(...);

// Invitaciones
Route::get('/register/invite/{code}', [RegisterController::class, 'showRegistrationFormWithInvite']);
```

---

## ROLES Y PERMISOS

| Rol | Acceso |
|-----|--------|
| `super_admin` | Todas las organizaciones |
| `analista` | Videos, evaluaciones de su org |
| `entrenador` | Videos, jugadores de su org |
| `jugador` | Sus videos asignados |

---

## COMANDOS UTILES

```bash
# Migraciones
php artisan migrate
php artisan migrate:status

# Cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Storage
php artisan storage:link

# Migrar videos a carpetas por org
php artisan videos:migrate-to-org-folders
```

---

## DESPLIEGUE EN VPS

```bash
# 1. Backup BD
mysqldump -u usuario -p rugby_db > backup.sql

# 2. Pull cambios
git pull origin main

# 3. Migraciones
php artisan migrate

# 4. Limpiar cache
php artisan config:clear && php artisan cache:clear

# 5. Permisos
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/

# 6. Symlink storage
php artisan storage:link
```

---

## COLORES DEL SISTEMA

- **Primary**: `#005461` (teal oscuro)
- **Primary hover**: `#003d4a`
- **Accent**: `#00B7B5` (teal claro)
- **Background**: `#0f0f0f` (dark mode)
- **Framework**: Laravel 12 + AdminLTE + Bootstrap 4

---

## NOTAS TECNICAS

- **Video Streaming**: Range requests HTTP para seeking
- **Storage**: DigitalOcean Spaces (cloud) + local fallback
- **Thumbnails**: Generacion HTML5+Canvas
- **Comentarios**: AJAX sin recarga de pagina
- **Menciones**: Sistema de notificaciones con @usuario

---

*Documentado por Claude Code - Sistema en desarrollo activo*
