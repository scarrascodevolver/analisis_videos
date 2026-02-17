/**
 * Test de Velocidad de Conexión para RugbyHub
 * Mide la velocidad real de upload del usuario
 */

class ConnectionSpeedTest {
    constructor() {
        this.results = {
            downloadSpeed: null,
            uploadSpeed: null,
            latency: null,
            timestamp: new Date()
        };
    }

    /**
     * Medir latencia al servidor
     */
    async measureLatency() {
        const pings = [];
        const attempts = 5;

        console.log('Midiendo latencia...');

        for (let i = 0; i < attempts; i++) {
            const start = performance.now();
            try {
                await fetch(window.location.origin + '/favicon.ico', {
                    method: 'HEAD',
                    cache: 'no-cache'
                });
                const end = performance.now();
                pings.push(end - start);
            } catch (error) {
                console.error('Error en ping:', error);
            }
            await new Promise(resolve => setTimeout(resolve, 200));
        }

        if (pings.length === 0) return null;

        const avg = pings.reduce((a, b) => a + b, 0) / pings.length;
        this.results.latency = {
            average: Math.round(avg),
            min: Math.round(Math.min(...pings)),
            max: Math.round(Math.max(...pings))
        };

        console.log(`Latencia: ${this.results.latency.average}ms (min: ${this.results.latency.min}ms, max: ${this.results.latency.max}ms)`);

        return this.results.latency;
    }

    /**
     * Medir velocidad de upload simulada
     */
    async measureUploadSpeed() {
        console.log('Midiendo velocidad de upload...');

        // Crear blob de 5MB para test
        const testSize = 5 * 1024 * 1024; // 5MB
        const testData = new Blob([new ArrayBuffer(testSize)]);
        const formData = new FormData();
        formData.append('test', testData, 'speedtest.bin');

        const start = performance.now();

        try {
            // Hacer upload a un endpoint dummy o al propio servidor
            const response = await fetch(window.location.origin + '/api/upload-test', {
                method: 'POST',
                body: formData
            });

            const end = performance.now();
            const durationSeconds = (end - start) / 1000;
            const speedMBps = (testSize / (1024 * 1024)) / durationSeconds;
            const speedMbps = speedMBps * 8;

            this.results.uploadSpeed = {
                mbps: speedMbps.toFixed(2),
                MBps: speedMBps.toFixed(2),
                duration: durationSeconds.toFixed(2)
            };

            console.log(`Velocidad Upload: ${speedMbps.toFixed(2)} Mbps (${speedMBps.toFixed(2)} MB/s)`);

            return this.results.uploadSpeed;
        } catch (error) {
            console.warn('No se pudo medir upload speed (endpoint no disponible)');
            console.log('Para medición precisa, usa: https://www.speedtest.net/');
            return null;
        }
    }

    /**
     * Estimar tiempo de upload para diferentes tamaños
     */
    estimateUploadTime(fileSizeGB) {
        if (!this.results.uploadSpeed) {
            console.warn('Primero ejecuta measureUploadSpeed()');
            return null;
        }

        const speedMBps = parseFloat(this.results.uploadSpeed.MBps);
        const fileSizeMB = fileSizeGB * 1024;
        const timeSeconds = fileSizeMB / speedMBps;
        const timeMinutes = timeSeconds / 60;

        return {
            fileSize: fileSizeGB + ' GB',
            uploadSpeed: speedMBps + ' MB/s',
            estimatedTime: timeMinutes.toFixed(1) + ' minutos',
            estimatedTimeSeconds: timeSeconds.toFixed(0) + ' segundos'
        };
    }

    /**
     * Ejecutar test completo
     */
    async runFullTest() {
        console.log('=== TEST DE VELOCIDAD DE CONEXIÓN ===\n');

        // Latencia
        await this.measureLatency();

        // Upload speed (simulado)
        await this.measureUploadSpeed();

        // Mostrar resultados
        this.displayResults();

        return this.results;
    }

    /**
     * Mostrar resultados formateados
     */
    displayResults() {
        console.log('\n=== RESULTADOS ===');

        if (this.results.latency) {
            console.log(`\nLatencia al servidor:`);
            console.log(`  Promedio: ${this.results.latency.average}ms`);
            console.log(`  Min: ${this.results.latency.min}ms`);
            console.log(`  Max: ${this.results.latency.max}ms`);
        }

        if (this.results.uploadSpeed) {
            console.log(`\nVelocidad de Upload:`);
            console.log(`  ${this.results.uploadSpeed.mbps} Mbps`);
            console.log(`  ${this.results.uploadSpeed.MBps} MB/s`);

            console.log(`\nTiempos Estimados de Upload:`);
            console.log(`  500MB: ${this.estimateUploadTime(0.5).estimatedTime}`);
            console.log(`  1GB:   ${this.estimateUploadTime(1).estimatedTime}`);
            console.log(`  2GB:   ${this.estimateUploadTime(2).estimatedTime}`);
            console.log(`  4GB:   ${this.estimateUploadTime(4).estimatedTime}`);
        }

        console.log(`\n📊 Recomendación:`);
        if (this.results.uploadSpeed) {
            const mbps = parseFloat(this.results.uploadSpeed.mbps);
            if (mbps > 100) {
                console.log('  ✅ Excelente velocidad - uploads rápidos');
            } else if (mbps > 50) {
                console.log('  ✅ Buena velocidad - uploads aceptables');
            } else if (mbps > 20) {
                console.log('  ⚠️  Velocidad moderada - videos grandes tardarán');
            } else {
                console.log('  ❌ Velocidad baja - considerar subir videos más pequeños o comprimir primero');
            }
        }

        console.log(`\n💡 Para medición más precisa, usa:`);
        console.log(`   https://www.speedtest.net/`);
        console.log(`   (Anota el valor de UPLOAD en Mbps)\n`);

        console.log('===================\n');
    }

    /**
     * Obtener información de conexión del navegador
     */
    getConnectionInfo() {
        if ('connection' in navigator) {
            const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            return {
                effectiveType: conn.effectiveType,
                downlink: conn.downlink + ' Mbps',
                rtt: conn.rtt + ' ms',
                saveData: conn.saveData
            };
        }
        return null;
    }
}

// Exportar globalmente
window.ConnectionSpeedTest = ConnectionSpeedTest;

// Función helper para ejecutar desde consola
window.testConnectionSpeed = async function() {
    const test = new ConnectionSpeedTest();
    await test.runFullTest();

    // Mostrar info de conexión del navegador
    const connInfo = test.getConnectionInfo();
    if (connInfo) {
        console.log('Información de Conexión (navegador):');
        console.log(connInfo);
    }

    return test.results;
};

// Función simplificada para estimar tiempos
window.estimateUploadTime = function(fileSizeGB, uploadSpeedMbps) {
    const speedMBps = uploadSpeedMbps / 8;
    const fileSizeMB = fileSizeGB * 1024;
    const timeSeconds = fileSizeMB / speedMBps;
    const timeMinutes = timeSeconds / 60;

    console.log(`\nVideo de ${fileSizeGB}GB con velocidad de ${uploadSpeedMbps} Mbps:`);
    console.log(`  Tiempo estimado: ${timeMinutes.toFixed(1)} minutos`);
    console.log(`  (${timeSeconds.toFixed(0)} segundos)`);

    return timeMinutes;
};

console.log('✅ Test de velocidad cargado.');
console.log('Ejecuta: testConnectionSpeed()');
console.log('O calcula manualmente: estimateUploadTime(4, 100) // 4GB @ 100Mbps');
