# 🏉 Guía de Reestructuración de Categorías

## 📋 Resumen

Esta reestructuración cambia el sistema de categorías para ser más realista según las operaciones del rugby:

**ANTES:**
- Adulta Primera (ID: 1)
- Adulta Intermedia (ID: 2)
- Juveniles (ID: 3)
- Femenino (ID: 4)

**DESPUÉS:**
- Juveniles (ID: 1)
- Adultas (ID: 2) - Combina Primera + Intermedia
- Femenino (ID: 3)

## 👨‍🏫 Asignaciones de Entrenadores

**ANTES:**
- Juan Cruz → Juveniles solamente
- Valentín → Solo Adulta Primera
- Víctor → Solo Adulta Intermedia

**DESPUÉS:**
- Juan Cruz → Juveniles solamente
- Valentín → Todas las Adultas (Primera + Intermedia)
- Víctor → Todas las Adultas (Primera + Intermedia)

## 🔧 Pasos para Ejecutar la Reestructuración

### 1. Backup de Seguridad (OBLIGATORIO)

```bash
# Crear backup de la base de datos
mysqldump -u usuario -p nombre_bd > backup_antes_reestructura.sql

# Verificar que estamos en la rama correcta
git checkout reestructura/categorias-realistas
git status
```

### 2. Ejecutar la Reestructuración

```bash
# Ejecutar el seeder maestro que hace todo el proceso
php artisan db:seed --class=RestructureToRealisticCategoriesSeeder
```

Este comando ejecutará automáticamente:
1. `MigrateVideosToNewCategoriesSeeder` - Migra videos existentes
2. `UpdateCategoriesRealisticSeeder` - Actualiza categorías y usuarios

### 3. Verificación Post-Migración

```bash
# Verificar categorías nuevas
php artisan tinker --execute="App\Models\Category::all()->pluck('name', 'id');"

# Verificar asignaciones de entrenadores
php artisan tinker --execute="App\Models\User::where('role', 'entrenador')->with('profile')->get()->pluck('profile.user_category_id', 'name');"

# Verificar distribución de videos por categoría
php artisan tinker --execute="App\Models\Video::select('category_id', DB::raw('count(*) as total'))->groupBy('category_id')->get();"
```

## 🎯 Resultados Esperados

### Categorías
```
ID 1: Juveniles
ID 2: Adultas
ID 3: Femenino
```

### Entrenadores
```
Juan Cruz Fleitas → Category ID: 1 (Juveniles)
Valentín Dapena → Category ID: 2 (Adultas)
Víctor Escobar → Category ID: 2 (Adultas)
```

### Videos
- Todos los videos de "Adulta Primera" e "Adulta Intermedia" → Category ID: 2
- Videos de "Juveniles" → Category ID: 1
- Videos de "Femenino" → Category ID: 3

## 🚨 Rollback (Si algo sale mal)

```bash
# Restaurar backup
mysql -u usuario -p nombre_bd < backup_antes_reestructura.sql

# Volver a rama anterior
git checkout funcionalidad/categorias-usuario
```

## ✅ Pruebas de Funcionamiento

Después de la migración, probar:

1. **Entrenador Juan Cruz**:
   - Solo debe ver videos de Juveniles
   - No debe ver videos adultos

2. **Entrenadores Valentín y Víctor**:
   - Deben ver TODOS los videos adultos
   - Tanto los que antes eran "Primera" como "Intermedia"

3. **Jugadores**:
   - Jugadores juveniles: Solo videos de Juveniles
   - Jugadores adultos: Solo videos de Adultas

## 📝 Notas Técnicas

- Los seeders manejan automáticamente las foreign keys
- La migración es segura y reversible
- No se pierden datos, solo se reagrupan
- La lógica de filtrado en el modelo Video ya está preparada para la nueva estructura

---

*Generado por Claude Code - Sistema Rugby Los Troncos*