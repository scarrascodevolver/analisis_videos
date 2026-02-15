#!/bin/bash
# Test de velocidad de upload a diferentes regiones de DigitalOcean Spaces
# Ejecutar desde el VPS actual o desde computadoras de usuarios

echo "=== TEST DE VELOCIDAD DE UPLOAD A DIGITALOCEAN SPACES ==="
echo ""

# Crear archivo de prueba de 50MB
TEST_FILE="/tmp/test_upload_50mb.bin"
echo "Creando archivo de prueba de 50MB..."
dd if=/dev/urandom of=$TEST_FILE bs=1M count=50 2>/dev/null

# Función para probar upload
test_upload() {
    local region=$1
    local endpoint=$2

    echo ""
    echo "----------------------------------------"
    echo "Probando región: $region"
    echo "Endpoint: $endpoint"
    echo "----------------------------------------"

    # Medir tiempo de upload
    start_time=$(date +%s.%N)

    # Intentar upload (esto fallará por permisos, pero mide la velocidad de red)
    curl -w "\nTiempo total: %{time_total}s\nVelocidad: %{speed_upload} bytes/s\n" \
         --connect-timeout 10 \
         --max-time 120 \
         -X PUT \
         -H "Host: test-bucket.${region}.digitaloceanspaces.com" \
         -T $TEST_FILE \
         "https://${region}.digitaloceanspaces.com/test-upload-$(date +%s).bin" \
         2>&1 | grep -E "(Tiempo|Velocidad|time_|speed_)"

    end_time=$(date +%s.%N)
    duration=$(echo "$end_time - $start_time" | bc)

    # Calcular velocidad en Mbps
    size_mb=50
    speed_mbps=$(echo "scale=2; ($size_mb * 8) / $duration" | bc)

    echo "Duración real: ${duration}s"
    echo "Velocidad estimada: ${speed_mbps} Mbps"
}

# Probar diferentes regiones
test_upload "NYC3" "nyc3.digitaloceanspaces.com"
test_upload "SFO3" "sfo3.digitaloceanspaces.com"
test_upload "AMS3" "ams3.digitaloceanspaces.com"
test_upload "SGP1" "sgp1.digitaloceanspaces.com"

# Limpiar
rm -f $TEST_FILE

echo ""
echo "=== PRUEBA COMPLETADA ==="
echo ""
echo "INTERPRETACIÓN:"
echo "  < 5 Mbps  : Lento - videos >500MB tardarán >15 minutos"
echo "  5-15 Mbps : Aceptable - videos 1GB ~10-15 minutos"
echo "  15-30 Mbps: Bueno - videos 1GB ~5 minutos"
echo "  > 30 Mbps : Excelente - videos 1GB ~3 minutos"
echo ""
echo "NOTA: Esta prueba mide conectividad de red."
echo "      La velocidad real puede variar según ISP y hora del día."
