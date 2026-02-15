/**
 * Herramienta de Diagnóstico de Velocidad de Upload
 * Mide latencia y velocidad de carga a DigitalOcean Spaces
 */

class UploadDiagnostics {
    constructor() {
        this.spacesEndpoint = 'https://sfo3.digitaloceanspaces.com';
        this.results = {
            location: null,
            ping: null,
            uploadSpeed: null,
            timestamp: new Date()
        };
    }

    /**
     * Obtener ubicación aproximada del usuario
     */
    async getUserLocation() {
        try {
            const response = await fetch('https://ipapi.co/json/');
            const data = await response.json();
            this.results.location = {
                city: data.city,
                region: data.region,
                country: data.country_name,
                ip: data.ip
            };
            return this.results.location;
        } catch (error) {
            console.error('Error obteniendo ubicación:', error);
            return null;
        }
    }

    /**
     * Medir latencia (ping) al endpoint
     */
    async measureLatency(attempts = 5) {
        const pings = [];

        for (let i = 0; i < attempts; i++) {
            const start = performance.now();
            try {
                await fetch(this.spacesEndpoint, { method: 'HEAD', mode: 'no-cors' });
                const end = performance.now();
                pings.push(end - start);
            } catch (error) {
                console.error('Error en ping:', error);
            }
            // Esperar 200ms entre pings
            await new Promise(resolve => setTimeout(resolve, 200));
        }

        if (pings.length === 0) return null;

        const avgPing = pings.reduce((a, b) => a + b, 0) / pings.length;
        const minPing = Math.min(...pings);
        const maxPing = Math.max(...pings);

        this.results.ping = {
            average: Math.round(avgPing),
            min: Math.round(minPing),
            max: Math.round(maxPing),
            samples: pings
        };

        return this.results.ping;
    }

    /**
     * Estimar velocidad de upload con un archivo pequeño
     */
    async estimateUploadSpeed(sizeKB = 100) {
        // Crear blob de prueba
        const testData = new Blob([new ArrayBuffer(sizeKB * 1024)]);
        const formData = new FormData();
        formData.append('file', testData, 'speedtest.bin');

        const start = performance.now();

        try {
            // Nota: esto requiere un endpoint en el backend para recibir el test
            const response = await fetch('/api/upload-test', {
                method: 'POST',
                body: formData
            });

            const end = performance.now();
            const durationSeconds = (end - start) / 1000;
            const speedKBps = sizeKB / durationSeconds;
            const speedMbps = (speedKBps * 8) / 1024;

            this.results.uploadSpeed = {
                kbps: Math.round(speedKBps),
                mbps: speedMbps.toFixed(2),
                duration: durationSeconds.toFixed(2),
                size: sizeKB
            };

            return this.results.uploadSpeed;
        } catch (error) {
            console.error('Error midiendo velocidad de upload:', error);
            return null;
        }
    }

    /**
     * Ejecutar diagnóstico completo
     */
    async runFullDiagnostic() {
        console.log('Iniciando diagnóstico de red...');

        const location = await this.getUserLocation();
        console.log('Ubicación:', location);

        const ping = await this.measureLatency();
        console.log('Latencia:', ping);

        // Nota: upload speed requiere endpoint en backend
        // const uploadSpeed = await this.estimateUploadSpeed();
        // console.log('Velocidad Upload:', uploadSpeed);

        return this.results;
    }

    /**
     * Mostrar resultados en consola formateados
     */
    displayResults() {
        console.log('\n=== DIAGNÓSTICO DE RED ===');
        console.log(`Ubicación: ${this.results.location?.city}, ${this.results.location?.country}`);
        console.log(`IP: ${this.results.location?.ip}`);
        console.log(`\nLatencia a SFO3:`);
        console.log(`  Promedio: ${this.results.ping?.average}ms`);
        console.log(`  Min: ${this.results.ping?.min}ms`);
        console.log(`  Max: ${this.results.ping?.max}ms`);

        if (this.results.uploadSpeed) {
            console.log(`\nVelocidad Upload:`);
            console.log(`  ${this.results.uploadSpeed.mbps} Mbps`);
        }

        console.log(`\nRecomendación:`);
        if (this.results.ping?.average < 100) {
            console.log('  ✓ Excelente conectividad');
        } else if (this.results.ping?.average < 200) {
            console.log('  ✓ Buena conectividad');
        } else if (this.results.ping?.average < 300) {
            console.log('  ⚠ Conectividad aceptable - subidas lentas');
        } else {
            console.log('  ✗ Conectividad limitada - considerar CDN regional');
        }
        console.log('=========================\n');
    }

    /**
     * Enviar resultados al servidor para logging
     */
    async sendToServer() {
        try {
            await fetch('/api/diagnostics/upload', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.results)
            });
        } catch (error) {
            console.error('Error enviando diagnóstico:', error);
        }
    }
}

// Uso global
window.UploadDiagnostics = UploadDiagnostics;

// Función helper para ejecutar desde consola
window.runNetworkDiagnostic = async function() {
    const diag = new UploadDiagnostics();
    await diag.runFullDiagnostic();
    diag.displayResults();
    return diag.results;
};

console.log('Diagnóstico de red cargado. Ejecuta: runNetworkDiagnostic()');
