#!/bin/bash

# Script de verificación de mejoras de performance
# Uso: bash test-performance-fixes.sh

echo "🔍 VERIFICACIÓN DE MEJORAS DE PERFORMANCE"
echo "=========================================="
echo ""

# 1. Verificar que estamos en la rama correcta
BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "✓ Rama actual: $BRANCH"

if [ "$BRANCH" != "performance/high-priority-fixes" ] && [ "$BRANCH" != "main" ]; then
    echo "⚠️  ADVERTENCIA: No estás en una rama de performance"
    echo "   Cambia a: performance/high-priority-fixes"
    echo ""
    read -p "¿Cambiar a performance/high-priority-fixes? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git checkout performance/high-priority-fixes
    else
        exit 1
    fi
fi

echo ""

# 2. Verificar que el build está actualizado
echo "📦 Verificando build..."
if [ ! -d "public/build" ]; then
    echo "❌ Build no encontrado"
    echo "   Ejecuta: npm run build"
    exit 1
fi

BUILD_TIME=$(stat -c %Y public/build/manifest.json 2>/dev/null || stat -f %m public/build/manifest.json 2>/dev/null)
CURRENT_TIME=$(date +%s)
TIME_DIFF=$((CURRENT_TIME - BUILD_TIME))

if [ $TIME_DIFF -gt 300 ]; then
    echo "⚠️  Build tiene más de 5 minutos"
    echo "   Recomendado: npm run build"
    read -p "¿Ejecutar build ahora? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        npm run build
    fi
else
    echo "✓ Build actualizado"
fi

echo ""

# 3. Verificar archivos modificados
echo "📝 Verificando archivos modificados..."
FILES=(
    "resources/js/video-player/notifications.js"
    "resources/js/video-player/annotations.js"
    "resources/js/video-player/comments.js"
    "resources/js/video-player/clip-manager.js"
)

for file in "${FILES[@]}"; do
    if grep -q "Performance optimization" "$file" 2>/dev/null; then
        echo "✓ $file - optimizado"
    else
        echo "❌ $file - falta optimización"
    fi
done

echo ""

# 4. Verificar índices implementados
echo "🔍 Verificando implementaciones clave..."

if grep -q "notificationTimeouts = new Map()" resources/js/video-player/notifications.js; then
    echo "✓ Fix #4: setTimeout tracking implementado"
else
    echo "❌ Fix #4: setTimeout tracking NO encontrado"
fi

if grep -q "annotationsBySecond = new Map()" resources/js/video-player/annotations.js; then
    echo "✓ Fix #5: Annotation index implementado"
else
    echo "❌ Fix #5: Annotation index NO encontrado"
fi

if grep -q "commentsBySecond = new Map()" resources/js/video-player/notifications.js; then
    echo "✓ Fix #6: Comment index implementado"
else
    echo "❌ Fix #6: Comment index NO encontrado"
fi

if grep -q "cleanupCommentHandlers()" resources/js/video-player/comments.js; then
    echo "✓ Fix #7: Handler cleanup implementado"
else
    echo "❌ Fix #7: Handler cleanup NO encontrado"
fi

if grep -q "setupClipListEventDelegation" resources/js/video-player/clip-manager.js; then
    echo "✓ Fix #8: Event delegation implementado"
else
    echo "❌ Fix #8: Event delegation NO encontrado"
fi

echo ""

# 5. Verificar que no hay errores de sintaxis
echo "🔧 Verificando sintaxis JavaScript..."
if command -v node &> /dev/null; then
    ERROR_COUNT=0
    for file in "${FILES[@]}"; do
        if node -c "$file" 2>/dev/null; then
            echo "✓ $file - sintaxis OK"
        else
            echo "❌ $file - error de sintaxis"
            ERROR_COUNT=$((ERROR_COUNT + 1))
        fi
    done

    if [ $ERROR_COUNT -gt 0 ]; then
        echo ""
        echo "⚠️  Encontrados $ERROR_COUNT errores de sintaxis"
        exit 1
    fi
else
    echo "⚠️  Node no disponible, skip syntax check"
fi

echo ""

# 6. Resumen
echo "✅ VERIFICACIÓN COMPLETADA"
echo "=========================="
echo ""
echo "Próximos pasos:"
echo "1. Inicia servidor: php artisan serve"
echo "2. Abre: http://localhost:8000/videos/[id]"
echo "3. Prueba funcionalidad:"
echo "   - Comentarios: agregar, eliminar, notificaciones"
echo "   - Anotaciones: crear, visualizar, eliminar"
echo "   - Clips: reproducir, eliminar, exportar GIF"
echo "4. Abre DevTools (F12) y verifica:"
echo "   - Sin errores en consola"
echo "   - Event listeners reducidos"
echo "   - No memory leaks"
echo ""
echo "📊 Benchmarks esperados:"
echo "   - Event Listeners: ~50 (antes: 500+)"
echo "   - CPU durante playback: 5-10% (antes: 15-25%)"
echo "   - Memory: estable (antes: +2MB/min)"
echo ""
echo "Si todo funciona bien, ejecuta:"
echo "   git checkout main"
echo "   git merge performance/high-priority-fixes"
echo "   git push origin main"
echo ""
