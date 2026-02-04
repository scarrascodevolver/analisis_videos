# CLAUDE.md - RugbyHub (Sistema Multi-Tenant)

## Ultima actualizacion: 2026-01-27

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
- Importacion XML de LongoMatch para clips de video
- Editor visual de timeline (drag & drop para ajustar clips)
- **Compresion adaptativa de videos** (Enero 2026)

---

## SISTEMA DE COMPRESION DE VIDEOS

### Compresion Adaptativa (Enero 2026)

El sistema aplica diferentes niveles de compresion segun el tamano del archivo:

| Tamano | Preset FFmpeg | CRF | Tiempo Estimado | Uso |
|--------|---------------|-----|-----------------|-----|
| < 500MB | medium | 23 | 30-60 min | Mejor calidad |
| 500MB - 2GB | fast | 23 | 1-2 horas | Calidad balanceada |
| 2GB - 4GB | veryfast | 22 | 2-3 horas | Optimizado velocidad |
| > 4GB | veryfast | 24 | 3-4 horas | Max velocidad |

**Configuracion Actual (VPS 2 CPU / 4GB RAM):**
- **Timeout**: 14400s (4 horas) - permite procesar archivos >4GB
- **Reintentos**: 1 intento (evita bloquear queue)
- **Procesos simultaneos**: 1 (limitacion de hardware)

**Capacidad con Hardware Actual:**
- 1 video simultaneo
- 9 usuarios = espera maxima de 16 horas
- ⚠️ **No viable para produccion con 9 equipos**

**Migracion Planeada a Hetzner VPS:**
- Hardware: 4-8 vCPUs, 8-16GB RAM
- Procesos simultaneos: 3-8
- Espera maxima: 2-5 horas
- Escalable para crecimiento

**Archivos Relacionados:**
- `app/Jobs/CompressVideoJob.php` - Job de compresion
- `start-queue-worker.sh` - Script de inicio del worker
- `docs/VPS_OPTIMIZATION.md` - Documentacion tecnica completa
- `DEPLOY-VPS-INSTRUCCIONES.md` - Guia de deployment
- `MIGRATION_HETZNER.md` - Plan de migracion a Hetzner VPS + Object Storage

**Comandos:**
```bash
# Con Supervisor (PRODUCCION - recomendado)
sudo supervisorctl status rugby-queue-worker:*
sudo supervisorctl restart rugby-queue-worker:*
sudo supervisorctl tail -f rugby-queue-worker:rugby-queue-worker_00

# Sin Supervisor (desarrollo/manual)
bash start-queue-worker.sh
php artisan queue:work database --sleep=3 --tries=1 --timeout=14400

# Monitoreo
tail -f storage/logs/laravel.log | grep CompressVideoJob
ps aux | grep "queue:work" | grep analisis_videos
```

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
| `VideoClip` | Clips de video (inicio, fin, categoria) |
| `ClipCategory` | Categorias de clips (color, nombre) |

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

**Ver guia completa:** `DEPLOY-VPS-INSTRUCCIONES.md`

```bash
# Deployment rapido (desde VPS)
cd /var/www/analisis_videos
git pull origin main
npm run build  # Si hay cambios JS/CSS
php artisan migrate --force  # Si hay migraciones
php artisan config:clear && php artisan cache:clear
sudo supervisorctl restart rugby-queue-worker:*  # Si hay cambios en workers
```

**Comando unico (desde tu PC):**
```bash
ssh root@161.35.108.164 "cd /var/www/analisis_videos && git pull origin main && npm run build && php artisan config:clear && php artisan cache:clear && sudo supervisorctl restart rugby-queue-worker:*"
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
- **Compresion**: FFmpeg con H.264 (libx264), presets adaptativos, CRF 22-24
- **Queue System**: Laravel queues con database driver, 1 proceso activo
- **Multipart Upload**: Soporte para archivos >2GB con chunks de 5MB

---

## RAMAS DE DESARROLLO

| Rama | Estado | Descripcion |
|------|--------|-------------|
| `main` | Estable | Produccion actual |
| `feature/adaptive-compression` | Completada | Compresion adaptativa basica |
| `optimize/vps-2cpu-4gb` | Activa | Optimizacion para VPS limitado (temporal) |

**Nota:** La rama `optimize/vps-2cpu-4gb` es temporal hasta migracion a Hetzner.

---

## ESTRUCTURA DE DOCUMENTACION

### Documentos Principales
- **CLAUDE.md** (este archivo) - Documentacion general del proyecto
- **README.md** - README estandar de Laravel
- **DEPLOY-VPS-INSTRUCCIONES.md** - Guia de deployment a produccion
- **MIGRATION_HETZNER.md** - Plan de migracion a Hetzner VPS + Object Storage

### Documentacion Tecnica
- **docs/VPS_OPTIMIZATION.md** - Optimizacion de compresion de video
- **docs/YOUTUBE_LEVEL_UPLOAD_SPEED.md** - Estrategias de optimizacion de upload

### Archivo Historico
- **docs/archive/** - Documentacion de features completadas y obsoletas

---

*Documentado por Claude Code - Sistema en desarrollo activo*
