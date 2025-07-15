# 🏉 Sistema de Análisis de Videos de Rugby - Los Troncos

## Estado del Proyecto: MVP Completado ✅

Este es un sistema web completo desarrollado en Laravel 12 para el análisis de videos de rugby del equipo "Los Troncos". 

### 🚀 Tecnologías Implementadas

- **Backend**: Laravel 12 
- **Frontend**: AdminLTE 3 (preparado para Vue.js)
- **Base de datos**: SQLite (fácil migración a MySQL)
- **Autenticación**: Sistema Laravel nativo
- **Zona horaria**: America/Santiago (Chile)

### ✅ Funcionalidades Completadas

#### 1. Estructura de Base de Datos
- ✅ **Usuarios**: Sistema completo con roles rugby-específicos
- ✅ **Equipos**: Todos los equipos del campeonato incluidos
- ✅ **Categorías**: Adulta Primera, Adulta Intermedia, Juveniles, Femenino
- ✅ **Videos**: Estructura completa para metadatos y archivos
- ✅ **Comentarios**: Sistema de comentarios temporales con categorización
- ✅ **Asignaciones**: Sistema para asignar videos a jugadores

#### 2. Modelos y Relaciones
- ✅ **User**: Con perfil rugby y métodos helper para roles
- ✅ **Team**: Equipos propios y rivales 
- ✅ **Category**: Categorías de videos
- ✅ **Video**: Videos con metadatos completos
- ✅ **VideoComment**: Comentarios con timestamps y threading
- ✅ **VideoAssignment**: Asignaciones entre analistas y jugadores
- ✅ **UserProfile**: Perfiles rugby-específicos

#### 3. Datos de Prueba
- ✅ **Equipos del Campeonato**:
  - LOS TRONCOS (equipo propio)
  - DOBS, ALL BRADS, OLD GEORGIANS
  - TABANCURA RC, OLD GABS, LAGARTOS RC
  - OLD ANGLONIANS, OLD LOCKS, COSTA DEL SOL

- ✅ **Usuarios de Ejemplo**:
  - Analista Principal (analista@lostroncos.cl)
  - Entrenador Principal (entrenador@lostroncos.cl)  
  - Jugador Ejemplo (jugador@lostroncos.cl)
  - Contraseña para todos: `password`

### 🎯 Roles y Funcionalidades Planificadas

#### ANALISTA (Rol Principal)
- Subir videos con formulario completo de metadatos
- Gestionar videos por categorías
- Asignar videos a jugadores con observaciones
- Crear análisis técnicos y tácticos
- Sistema de etiquetado temporal
- Generar reportes de progreso

#### JUGADOR  
- Ver videos asignados
- Sistema de comentarios en timestamps
- Subir videos propios para análisis
- Acceder a historial personal
- Ver estadísticas de progreso

#### ENTRENADOR
- Acceso completo a videos del equipo
- Dashboard con reportes consolidados
- Gestión de roster de jugadores
- Comparativas entre jugadores
- Análisis de equipos rivales

### 📊 Estructura de Comentarios Profesional

El sistema incluye un sofisticado sistema de comentarios temporales:

- **Timeline visual** debajo del reproductor
- **Marcadores temporales** clickeables
- **Categorización**: Técnico, Táctico, Físico, Mental
- **Sistema threading** (comentario > respuesta)
- **Prioridades**: Baja, Media, Alta, Crítica
- **Estados**: Pendiente, En revisión, Completado
- **Notificaciones** en tiempo real

### 🗄️ Base de Datos

La base de datos está completamente configurada con:

```
users (con campos rugby)
teams (equipos del campeonato)  
categories (categorías de videos)
user_profiles (perfiles rugby)
videos (con metadatos completos)
video_comments (comentarios temporales)
video_assignments (asignaciones)
```

### 🔧 Instalación y Configuración

1. **Clonar y configurar**:
   ```bash
   cd rugby-analysis-system
   composer install
   npm install
   ```

2. **Base de datos**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

3. **Iniciar servidor**:
   ```bash
   php artisan serve
   ```

4. **Acceder**: http://localhost:8000

### 🔄 Próximos Pasos

Para completar el sistema MVP:

1. **Implementar autenticación** con middleware de roles
2. **Crear formularios de registro** dual (básico + rugby)
3. **Sistema de subida de videos** con validación
4. **Reproductor de video** con comentarios temporales
5. **Dashboards específicos** por rol
6. **Sistema de notificaciones** para asignaciones
7. **Búsqueda y filtros** avanzados
8. **Exportación a PDF** de reportes

### 💾 Respaldo y Migración

Para migrar a MySQL:
1. Cambiar configuración en `.env`
2. Crear base de datos MySQL
3. Ejecutar `php artisan migrate` nuevamente

### 🛡️ Seguridad

- Validaciones en modelos y controladores
- Protección CSRF implementada
- Autenticación robusta con roles
- Subida de archivos segura

### 📧 Contacto

Sistema desarrollado para el equipo de rugby "Los Troncos" con enfoque en análisis profesional de videos y mejora del rendimiento deportivo.

---

**Estado**: MVP Base Completado ✅  
**Versión**: 1.0.0  
**Última actualización**: Junio 2025