<?php

namespace App\Http\Controllers;

use App\Models\Jugada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JugadasController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the plays editor.
     */
    public function index()
    {
        return view('jugadas.index');
    }

    /**
     * API: Listar jugadas de la organización
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $jugadas = Jugada::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($jugada) {
                return [
                    'id' => $jugada->id,
                    'name' => $jugada->name,
                    'category' => $jugada->category,
                    'categoryIcon' => $jugada->category_icon,
                    'thumbnail' => $jugada->thumbnail,
                    'user' => $jugada->user->name ?? 'Desconocido',
                    'created_at' => $jugada->created_at->format('d/m/Y H:i'),
                    'data' => $jugada->data,
                ];
            });

        return response()->json([
            'success' => true,
            'jugadas' => $jugadas,
        ]);
    }

    /**
     * API: Guardar nueva jugada (solo analistas/entrenadores)
     */
    public function apiStore(Request $request): JsonResponse
    {
        // Solo analistas y entrenadores pueden crear jugadas
        if (!in_array(auth()->user()->role, ['analista', 'entrenador'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear jugadas.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:forwards,backs,full_team',
            'data' => 'required|array',
            'thumbnail' => 'nullable|string',
        ]);

        $user = auth()->user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una organización asignada.',
            ], 403);
        }

        $jugada = Jugada::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'data' => $validated['data'],
            'thumbnail' => $validated['thumbnail'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jugada guardada exitosamente.',
            'jugada' => [
                'id' => $jugada->id,
                'name' => $jugada->name,
                'category' => $jugada->category,
                'categoryIcon' => $jugada->category_icon,
                'thumbnail' => $jugada->thumbnail,
                'user' => $user->name,
                'created_at' => $jugada->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * API: Obtener una jugada específica
     */
    public function apiShow(Jugada $jugada): JsonResponse
    {
        return response()->json([
            'success' => true,
            'jugada' => [
                'id' => $jugada->id,
                'name' => $jugada->name,
                'category' => $jugada->category,
                'categoryIcon' => $jugada->category_icon,
                'thumbnail' => $jugada->thumbnail,
                'user' => $jugada->user->name ?? 'Desconocido',
                'created_at' => $jugada->created_at->format('d/m/Y H:i'),
                'data' => $jugada->data,
            ],
        ]);
    }

    /**
     * API: Eliminar jugada (solo analistas/entrenadores)
     */
    public function apiDestroy(Jugada $jugada): JsonResponse
    {
        // Solo analistas y entrenadores pueden eliminar jugadas
        if (!in_array(auth()->user()->role, ['analista', 'entrenador'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar jugadas.',
            ], 403);
        }

        $name = $jugada->name;
        $jugada->delete();

        return response()->json([
            'success' => true,
            'message' => "Jugada '{$name}' eliminada.",
        ]);
    }

    /**
     * API: Convertir video WebM a MP4
     */
    public function apiConvertToMp4(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file',
            'filename' => 'required|string|max:255',
        ]);

        // Log para debug
        $uploadedMime = $request->file('video')->getMimeType();
        \Log::info('Video conversion request', [
            'mime' => $uploadedMime,
            'size' => $request->file('video')->getSize(),
            'filename' => $request->input('filename'),
        ]);

        $webmFile = $request->file('video');
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $request->input('filename'));

        // Crear directorios temporales
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $webmPath = $tempDir . '/' . uniqid() . '.webm';
        $mp4Path = $tempDir . '/' . $filename . '_' . date('Y-m-d') . '.mp4';

        try {
            // Guardar archivo WebM
            $webmFile->move($tempDir, basename($webmPath));

            // Convertir con FFmpeg
            // -vf scale: asegura dimensiones pares (requerido por libx264)
            $command = sprintf(
                'ffmpeg -i %s -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart -threads 1 -y %s 2>&1',
                escapeshellarg($webmPath),
                escapeshellarg($mp4Path)
            );

            exec($command, $output, $returnCode);

            // Eliminar archivo WebM temporal
            if (file_exists($webmPath)) {
                unlink($webmPath);
            }

            if ($returnCode !== 0 || !file_exists($mp4Path)) {
                \Log::error('FFmpeg conversion failed', [
                    'returnCode' => $returnCode,
                    'output' => $output,
                    'command' => $command,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al convertir el video. FFmpeg code: ' . $returnCode,
                    'debug' => implode("\n", array_slice($output, -10)), // últimas 10 líneas
                ], 500);
            }

            // Leer MP4 y devolver como base64
            $mp4Content = file_get_contents($mp4Path);
            $mp4Base64 = base64_encode($mp4Content);
            $mp4Size = filesize($mp4Path);

            // Eliminar archivo MP4 temporal
            unlink($mp4Path);

            return response()->json([
                'success' => true,
                'video' => $mp4Base64,
                'filename' => basename($mp4Path),
                'size' => $mp4Size,
            ]);

        } catch (\Exception $e) {
            // Limpiar archivos temporales en caso de error
            if (file_exists($webmPath)) {
                unlink($webmPath);
            }
            if (file_exists($mp4Path)) {
                unlink($mp4Path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
