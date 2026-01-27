#!/bin/bash

##############################################################################
# RugbyHub Queue Worker Starter
#
# Este script inicia el queue worker optimizado para VPS 2 CPU / 4GB RAM
# Timeout: 4 horas (14400s) - Permite procesar videos de 4GB+
##############################################################################

echo "=========================================="
echo "  RugbyHub Queue Worker"
echo "  VPS Optimizado (2 CPU / 4GB RAM)"
echo "=========================================="
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: Este script debe ejecutarse desde el directorio raíz de Laravel"
    echo "   Ejemplo: cd /var/www/analisis_videos && bash start-queue-worker.sh"
    exit 1
fi

# Verificar si ya hay un worker corriendo
EXISTING_PID=$(ps aux | grep "artisan queue:work" | grep "analisis_videos" | grep -v grep | awk '{print $2}')

if [ ! -z "$EXISTING_PID" ]; then
    echo "⚠️  Ya hay un queue worker corriendo (PID: $EXISTING_PID)"
    echo ""
    read -p "¿Deseas detenerlo y reiniciar? (s/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        echo "🛑 Deteniendo worker anterior..."
        kill $EXISTING_PID
        sleep 2
        echo "✅ Worker anterior detenido"
    else
        echo "❌ Operación cancelada. El worker existente continuará corriendo."
        exit 0
    fi
fi

echo ""
echo "🚀 Iniciando queue worker con configuración optimizada:"
echo "   - Timeout: 14400s (4 horas)"
echo "   - Tries: 1 (sin reintentos automáticos)"
echo "   - Max time: 3600s (worker se reinicia cada hora)"
echo "   - Sleep: 3s (espera entre jobs)"
echo ""

# Crear directorio de logs si no existe
mkdir -p storage/logs

# Iniciar el worker en background
nohup php artisan queue:work database \
  --sleep=3 \
  --tries=1 \
  --max-time=3600 \
  --timeout=14400 \
  > storage/logs/queue-worker.log 2>&1 &

# Esperar un momento para verificar que inició correctamente
sleep 2

# Verificar si el proceso se inició
NEW_PID=$(ps aux | grep "artisan queue:work" | grep "analisis_videos" | grep -v grep | awk '{print $2}')

if [ ! -z "$NEW_PID" ]; then
    echo "✅ Queue worker iniciado correctamente"
    echo ""
    echo "   PID: $NEW_PID"
    echo "   Log: storage/logs/queue-worker.log"
    echo ""
    echo "Comandos útiles:"
    echo "   Ver logs en tiempo real:"
    echo "     tail -f storage/logs/queue-worker.log"
    echo ""
    echo "   Ver estado de procesamiento:"
    echo "     tail -50 storage/logs/laravel.log | grep CompressVideoJob"
    echo ""
    echo "   Detener worker:"
    echo "     kill $NEW_PID"
    echo ""
    echo "   Ver uso de recursos:"
    echo "     top -p $NEW_PID"
    echo ""
else
    echo "❌ Error al iniciar el queue worker"
    echo "   Revisa el log: storage/logs/queue-worker.log"
    exit 1
fi
