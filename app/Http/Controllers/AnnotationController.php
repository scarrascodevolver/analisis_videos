<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoAnnotation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AnnotationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Guardar nueva anotación
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:videos,id',
            'timestamp' => 'required|numeric|min:0',
            'annotation_data' => 'required|array',
            'annotation_type' => 'required|in:arrow,circle,line,text,rectangle,free_draw,canvas',
            'duration_seconds' => 'nullable|integer|min:1|max:60',
            'is_permanent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar que el usuario puede ver el video
            $video = Video::visibleForUser(Auth::user())->findOrFail($request->video_id);

            $annotation = VideoAnnotation::create([
                'video_id' => $request->video_id,
                'user_id' => Auth::id(),
                'timestamp' => $request->timestamp,
                'annotation_data' => $request->annotation_data,
                'annotation_type' => $request->annotation_type,
                'duration_seconds' => $request->duration_seconds ?? 4, // Default 4 segundos
                'is_permanent' => $request->is_permanent ?? false,
                'is_visible' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anotación guardada exitosamente',
                'annotation' => [
                    'id' => $annotation->id,
                    'timestamp' => $annotation->timestamp,
                    'annotation_type' => $annotation->annotation_type,
                    'annotation_data' => $annotation->annotation_data,
                    'user_name' => $annotation->user->name,
                    'created_at' => $annotation->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la anotación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener anotaciones por video
     */
    public function getByVideo($videoId): JsonResponse
    {
        try {
            // Verificar que el usuario puede ver el video
            $video = Video::visibleForUser(Auth::user())->findOrFail($videoId);

            $annotations = VideoAnnotation::with('user:id,name')
                ->where('video_id', $videoId)
                ->visible()
                ->orderedByTimestamp()
                ->get()
                ->map(function ($annotation) {
                    return [
                        'id' => $annotation->id,
                        'timestamp' => $annotation->timestamp,
                        'annotation_type' => $annotation->annotation_type,
                        'annotation_data' => $annotation->annotation_data,
                        'duration_seconds' => $annotation->duration_seconds,
                        'is_permanent' => $annotation->is_permanent,
                        'user' => [
                            'name' => $annotation->user->name,
                            'id' => $annotation->user->id,
                        ],
                        'created_at' => $annotation->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'annotations' => $annotations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las anotaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener anotaciones por timestamp específico
     */
    public function getByTimestamp(Request $request, $videoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'timestamp' => 'required|numeric|min:0',
            'tolerance' => 'nullable|numeric|min:0|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar que el usuario puede ver el video
            $video = Video::visibleForUser(Auth::user())->findOrFail($videoId);

            $timestamp = $request->timestamp;
            $tolerance = $request->tolerance ?? 0.5;

            $annotations = VideoAnnotation::with('user:id,name')
                ->where('video_id', $videoId)
                ->visible()
                ->atTimestamp($timestamp, $tolerance)
                ->orderedByTimestamp()
                ->get()
                ->map(function ($annotation) {
                    return [
                        'id' => $annotation->id,
                        'timestamp' => $annotation->timestamp,
                        'annotation_type' => $annotation->annotation_type,
                        'annotation_data' => $annotation->annotation_data,
                        'user_name' => $annotation->user->name,
                        'created_at' => $annotation->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'annotations' => $annotations,
                'timestamp_searched' => $timestamp,
                'tolerance_used' => $tolerance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar anotaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar anotación (solo el creador o staff)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $annotation = VideoAnnotation::find($id);

            // Si la anotación no existe, asumir que ya fue eliminada
            if (!$annotation) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anotación ya fue eliminada previamente',
                    'already_deleted' => true
                ]);
            }

            $user = Auth::user();

            // Solo el creador o staff pueden eliminar
            if ($annotation->user_id !== $user->id && !in_array($user->role, ['analista', 'entrenador', 'staff'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar esta anotación'
                ], 403);
            }

            $annotation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Anotación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la anotación: ' . $e->getMessage()
            ], 500);
        }
    }
}