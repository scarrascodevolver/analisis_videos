#!/bin/bash
# Test simple de latencia a diferentes regiones
# Útil para decidir dónde poner el storage

echo "=== TEST DE LATENCIA A DIFERENTES REGIONES ==="
echo ""
echo "Tu ubicación actual:"
curl -s ipinfo.io | grep -E "(city|region|country|loc)" | head -4
echo ""
echo "----------------------------------------"

# Función para hacer ping HTTP (más confiable que ICMP)
http_ping() {
    local name=$1
    local url=$2

    echo ""
    echo "Probando: $name"
    echo "URL: $url"

    # Hacer 5 requests y medir tiempo
    total=0
    count=0

    for i in {1..5}; do
        time=$(curl -o /dev/null -s -w '%{time_total}\n' --connect-timeout 5 "$url" 2>/dev/null)
        if [ $? -eq 0 ]; then
            ms=$(echo "$time * 1000" | bc)
            echo "  Intento $i: ${ms} ms"
            total=$(echo "$total + $ms" | bc)
            count=$((count + 1))
        fi
        sleep 0.5
    done

    if [ $count -gt 0 ]; then
        avg=$(echo "scale=2; $total / $count" | bc)
        echo "  Promedio: ${avg} ms"

        # Interpretación
        if (( $(echo "$avg < 50" | bc -l) )); then
            echo "  ✅ EXCELENTE - Ideal para storage"
        elif (( $(echo "$avg < 100" | bc -l) )); then
            echo "  ✅ MUY BUENO - Recomendado"
        elif (( $(echo "$avg < 200" | bc -l) )); then
            echo "  ⚠️  ACEPTABLE - Funciona pero no óptimo"
        else
            echo "  ❌ LENTO - No recomendado para storage"
        fi
    else
        echo "  ❌ Error - No se pudo conectar"
    fi
}

# Probar diferentes regiones
echo ""
echo "=== DIGITALOCEAN SPACES ==="
http_ping "NYC3 (New York)" "https://nyc3.digitaloceanspaces.com"
http_ping "SFO3 (San Francisco)" "https://sfo3.digitaloceanspaces.com"
http_ping "AMS3 (Amsterdam)" "https://ams3.digitaloceanspaces.com"
http_ping "SGP1 (Singapore)" "https://sgp1.digitaloceanspaces.com"

echo ""
echo "=== HETZNER ==="
http_ping "Hetzner Falkenstein (Alemania)" "https://fsn1.your-objectstorage.com"
http_ping "Hetzner Helsinki (Finlandia)" "https://hel1.your-objectstorage.com"

echo ""
echo "=== AWS S3 (Referencia) ==="
http_ping "AWS us-east-1 (Virginia)" "https://s3.amazonaws.com"
http_ping "AWS eu-west-1 (Irlanda)" "https://s3.eu-west-1.amazonaws.com"

echo ""
echo "=== ANÁLISIS COMPLETADO ==="
echo ""
echo "📊 RECOMENDACIÓN:"
echo ""
echo "1. La región con latencia más baja es donde deberías poner el STORAGE"
echo "2. El VPS debe estar lo más cerca posible del STORAGE"
echo "3. Los usuarios suben directo al storage (no importa tanto su latencia)"
echo ""
echo "EJEMPLOS:"
echo "  - Si migras a Hetzner Alemania → Usa Hetzner Storage o DO Spaces AMS3"
echo "  - Si quedas en DO NYC1 → Usa DO Spaces NYC3"
echo "  - Si migras a DO AMS3 → Usa DO Spaces AMS3"
