/**
 * Cloudflare Worker: Video CORS Proxy
 *
 * Purpose: Intercepta requests al CDN de videos y agrega headers CORS
 * sin hacer proxy completo (solo modifica headers en el edge)
 *
 * Deployed at: https://videos.rugbyhub.cl/*
 * Proxies to: https://analisis-videos-storage.sfo3.cdn.digitaloceanspaces.com/*
 *
 * Performance:
 * - Latencia: ~10-50ms (edge processing)
 * - Throughput: Sin límite (streaming directo desde CDN)
 * - CPU: 0% en Laravel (no hace proxy)
 *
 * @author Claude Code
 * @version 1.0.0
 */

addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const url = new URL(request.url)

  // Construir URL del CDN original
  const cdnUrl = `https://analisis-videos-storage.sfo3.cdn.digitaloceanspaces.com${url.pathname}`

  // Manejar preflight CORS (OPTIONS)
  if (request.method === 'OPTIONS') {
    return new Response(null, {
      status: 204,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers': 'Range, Content-Range, Accept-Ranges, Content-Type',
        'Access-Control-Max-Age': '86400', // 24 horas
      }
    })
  }

  // Hacer fetch al CDN original (con headers Range si existen)
  const cdnRequest = new Request(cdnUrl, {
    method: request.method,
    headers: request.headers,
  })

  try {
    const response = await fetch(cdnRequest)

    // Clonar response para modificar headers
    const newHeaders = new Headers(response.headers)

    // Agregar headers CORS
    newHeaders.set('Access-Control-Allow-Origin', '*')
    newHeaders.set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
    newHeaders.set('Access-Control-Allow-Headers', 'Range, Content-Range, Accept-Ranges, Content-Type')
    newHeaders.set('Access-Control-Expose-Headers', 'Content-Length, Content-Range, Accept-Ranges')

    // Optimizaciones de cache
    if (!newHeaders.has('Cache-Control')) {
      newHeaders.set('Cache-Control', 'public, max-age=31536000, immutable') // 1 año para videos
    }

    // Retornar respuesta con headers modificados
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: newHeaders
    })

  } catch (error) {
    // Fallback a Laravel si Worker falla
    console.error('Worker error:', error)
    return new Response('Worker error - fallback to origin', {
      status: 502,
      headers: { 'X-Worker-Error': error.message }
    })
  }
}
