<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoCommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VideoStreamController;
use App\Http\Controllers\PlayerApiController;
use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VideoViewController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\DirectUploadController;
use App\Http\Controllers\ClipCategoryController;
use App\Http\Controllers\VideoClipController;

// Public route - Redirect directly to login
Route::redirect('/', '/login');

// Authentication Routes
Auth::routes();

// API Pública para validar código de invitación (sin auth - para registro)
Route::post('/api/validate-invitation-code', function (Illuminate\Http\Request $request) {
    $code = strtoupper($request->input('code', ''));

    if (empty($code)) {
        return response()->json(['valid' => false, 'message' => 'Código requerido']);
    }

    $organization = App\Models\Organization::active()->byInvitationCode($code)->first();

    if (!$organization) {
        return response()->json(['valid' => false, 'message' => 'Código inválido o inactivo']);
    }

    // Obtener categorías de esta organización (sin global scope)
    $categories = App\Models\Category::withoutGlobalScope('organization')
        ->where('organization_id', $organization->id)
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json([
        'valid' => true,
        'organization' => [
            'id' => $organization->id,
            'name' => $organization->name,
            'logo_url' => $organization->logo_path ? asset('storage/' . $organization->logo_path) : null,
        ],
        'categories' => $categories,
    ]);
});

// Organization Selection Routes (auth required, but excluded from organization middleware)
Route::middleware(['auth'])->group(function () {
    Route::get('/select-organization', [OrganizationController::class, 'select'])->name('select-organization');
    Route::post('/set-organization/{organization}', [OrganizationController::class, 'switch'])->name('set-organization');
});

// Video Streaming Routes (PUBLIC - no auth needed for video tags)
Route::get('videos/{video}/stream', [VideoStreamController::class, 'stream'])->name('videos.stream');
Route::get('stream/videos/{filename}', [VideoStreamController::class, 'streamByPath'])->name('videos.stream.file');

// CDN Health Status (for monitoring - protected)
Route::get('api/cdn-status', [VideoStreamController::class, 'cdnStatus'])->middleware('auth')->name('api.cdn.status');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Main Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // Video Routes
    Route::resource('videos', VideoController::class);
    Route::post('videos/{video}/comments', [VideoCommentController::class, 'store'])->name('video.comments.store');

    // Direct Upload to Spaces (pre-signed URLs)
    Route::post('api/upload/presigned-url', [DirectUploadController::class, 'getPresignedUrl'])->name('api.upload.presigned');
    Route::post('api/upload/confirm', [DirectUploadController::class, 'confirmUpload'])->name('api.upload.confirm');
    Route::delete('comments/{comment}', [VideoCommentController::class, 'destroy'])->name('comments.destroy');

    // Player API Routes (for AJAX search functionality)
    Route::get('api/players/all', [PlayerApiController::class, 'all'])->name('api.players.all');
    Route::get('api/players/search', [PlayerApiController::class, 'search'])->name('api.players.search');
    Route::get('api/players/{player}/videos', [PlayerApiController::class, 'playerVideos'])->name('api.players.videos');

    // Video Annotations API Routes
    Route::prefix('api/annotations')->name('api.annotations.')->group(function () {
        Route::post('/', [AnnotationController::class, 'store'])->name('store');
        Route::get('/video/{videoId}', [AnnotationController::class, 'getByVideo'])->name('getByVideo');
        Route::get('/video/{videoId}/timestamp', [AnnotationController::class, 'getByTimestamp'])->name('getByTimestamp');
        Route::delete('/{id}', [AnnotationController::class, 'destroy'])->name('destroy');
    });

    // Video Clips API Routes
    Route::prefix('api/videos/{video}/clips')->name('api.clips.')->group(function () {
        Route::get('/', [VideoClipController::class, 'apiIndex'])->name('index');
        Route::post('/quick', [VideoClipController::class, 'quickStore'])->name('quick-store');
    });

    // Clip Categories API
    Route::get('api/clip-categories', [ClipCategoryController::class, 'apiIndex'])->name('api.clip-categories.index');

    // Video Clips CRUD Routes
    Route::prefix('videos/{video}/clips')->name('videos.clips.')->group(function () {
        Route::get('/', [VideoClipController::class, 'index'])->name('index');
        Route::post('/', [VideoClipController::class, 'store'])->name('store');
        Route::put('/{clip}', [VideoClipController::class, 'update'])->name('update');
        Route::delete('/{clip}', [VideoClipController::class, 'destroy'])->name('destroy');
    });

    // Clip highlight toggle
    Route::post('api/clips/{clip}/toggle-highlight', [VideoClipController::class, 'toggleHighlight'])->name('api.clips.toggle-highlight');

    // Video View Tracking API Routes
    Route::prefix('api/videos')->name('api.videos.')->group(function () {
        Route::post('/{video}/track-view', [VideoViewController::class, 'track'])->name('track-view');
        Route::patch('/{video}/update-duration', [VideoViewController::class, 'updateDuration'])->name('update-duration');
        Route::patch('/{video}/mark-completed', [VideoViewController::class, 'markCompleted'])->name('mark-completed');
        Route::get('/{video}/stats', [VideoViewController::class, 'getStats'])->name('stats');
    });

    // My Videos Routes
    Route::get('my-videos', [App\Http\Controllers\MyVideosController::class, 'index'])->name('my-videos');
    Route::patch('assignments/{assignment}/complete', [App\Http\Controllers\MyVideosController::class, 'markAsCompleted'])->name('assignments.complete');
    Route::get('assignments/{assignment}/video', [App\Http\Controllers\MyVideosController::class, 'show'])->name('assignments.show');

    // Notifications Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/mark-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('markRead');
        Route::post('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('markAllRead');
    });

    // Analyst Routes (Analistas y Entrenadores)
    Route::middleware(['role:analista,entrenador'])->prefix('analyst')->name('analyst.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'analyst'])->name('dashboard');
        Route::resource('assignments', App\Http\Controllers\VideoAssignmentController::class);
        Route::patch('assignments/{assignment}/complete', [App\Http\Controllers\VideoAssignmentController::class, 'markCompleted'])->name('assignments.markCompleted');
        Route::post('assignments/{assignment}/accept', [App\Http\Controllers\VideoAssignmentController::class, 'playerAccept'])->name('assignments.playerAccept');
        Route::post('assignments/{assignment}/submit', [App\Http\Controllers\VideoAssignmentController::class, 'playerSubmit'])->name('assignments.playerSubmit');
        Route::post('assignments/bulk', [App\Http\Controllers\VideoAssignmentController::class, 'bulk'])->name('assignments.bulk');
    });

    // Admin/Mantenedor Routes (Analistas y Entrenadores)
    Route::middleware(['role:analista,entrenador'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard del Mantenedor
        Route::get('/', [AdminController::class, 'index'])->name('index');

        // Gestión de Categorías
        Route::resource('categories', App\Http\Controllers\CategoryManagementController::class);

        // Gestión de Categorías de Clips (Botonera)
        Route::resource('clip-categories', ClipCategoryController::class);
        Route::post('clip-categories/reorder', [ClipCategoryController::class, 'reorder'])->name('clip-categories.reorder');

        // Gestión de Equipos (deshabilitado - equipos se crean automáticamente con la organización)
        // Route::resource('teams', App\Http\Controllers\TeamManagementController::class);

        // Gestión de Situaciones de Rugby
        Route::resource('situations', App\Http\Controllers\RugbySituationController::class);
        Route::post('situations/reorder', [App\Http\Controllers\RugbySituationController::class, 'reorder'])->name('situations.reorder');

        // Gestión de Usuarios
        Route::resource('users', App\Http\Controllers\UserManagementController::class);

        // Gestión de Organización (código de invitación)
        Route::get('organization', [AdminController::class, 'organization'])->name('organization');
        Route::put('organization/invitation-code', [AdminController::class, 'updateInvitationCode'])->name('organization.update-code');
        Route::post('organization/regenerate-code', [AdminController::class, 'regenerateInvitationCode'])->name('organization.regenerate-code');
    });

    // Player Routes
    Route::middleware(['role:jugador'])->prefix('player')->name('player.')->group(function () {
        Route::get('/videos', [DashboardController::class, 'playerVideos'])->name('videos');
        Route::get('/completed', [DashboardController::class, 'playerCompleted'])->name('completed');
        Route::get('/pending', [DashboardController::class, 'playerPending'])->name('pending');
        Route::get('/upload', [VideoController::class, 'playerUpload'])->name('upload');
        Route::post('/upload', [VideoController::class, 'playerStore'])->name('upload.store');
    });

    // Profile Routes (for all authenticated users)
    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile/avatar', [App\Http\Controllers\ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    });

    // Coach Routes
    Route::middleware(['role:entrenador,analista'])->prefix('coach')->name('coach.')->group(function () {
        Route::get('/videos', [DashboardController::class, 'coachVideos'])->name('videos');
        Route::get('/users', [DashboardController::class, 'coachUsers'])->name('users');
        Route::get('/assignments', [DashboardController::class, 'coachAssignments'])->name('assignments');
        Route::get('/player/{user}', [DashboardController::class, 'playerProfile'])->name('player.profile');
        Route::get('/player/{user}/assign', [DashboardController::class, 'playerAssign'])->name('player.assign');
    });

    // General Routes (accessible by all roles)
    // Removed unused routes (teams, categories, reports)
});

// DEBUG: Test route to verify routing works
Route::get('/test-video-route', function() {
    return 'Video route works!';
});

// DEBUG: Test route that mimics video structure
Route::get('/test-video/{id}', function($id) {
    return "Test video route works for ID: $id";
});

// DEBUG: Test video route without model binding
Route::get('/debug-video/{id}', function($id) {
    return 'Video ID: ' . $id;
});

// DEBUG: Test video route with manual model lookup
Route::get('/debug-video-model/{id}', function($id) {
    try {
        $video = App\Models\Video::findOrFail($id);
        return 'Found video: ' . $video->title;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// DEBUG: Test exact VideoStreamController method call
Route::get('/debug-stream/{video}', function(App\Models\Video $video) {
    try {
        $controller = new App\Http\Controllers\VideoStreamController();
        $request = request();
        return $controller->stream($video, $request);
    } catch (Exception $e) {
        return 'VideoStreamController Error: ' . $e->getMessage();
    }
});

// ======================================
// EVALUACIÓN DE COMPAÑEROS
// ======================================
Route::middleware('auth')->group(function() {
    Route::get('/evaluacion', [App\Http\Controllers\EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluacion/wizard/{player}', [App\Http\Controllers\EvaluationController::class, 'wizard'])->name('evaluations.wizard');
    Route::post('/evaluacion/store', [App\Http\Controllers\EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('/evaluacion/resultados', [App\Http\Controllers\EvaluationController::class, 'dashboard'])->name('evaluations.dashboard');
    Route::get('/evaluacion/jugador/{player}', [App\Http\Controllers\EvaluationController::class, 'show'])->name('evaluations.show');

    // Toggle de evaluaciones (entrenadores/analistas)
    Route::match(['get', 'post'], '/evaluacion/toggle', [App\Http\Controllers\EvaluationController::class, 'toggleEvaluations'])
        ->middleware('role:entrenador,analista')
        ->name('evaluations.toggle');

    // Gestión de períodos de evaluación (entrenadores/analistas)
    Route::middleware('role:entrenador,analista')->group(function() {
        Route::get('/evaluacion/periodos', [App\Http\Controllers\EvaluationController::class, 'listPeriods'])->name('evaluations.periods.list');
        Route::post('/evaluacion/periodos', [App\Http\Controllers\EvaluationController::class, 'createPeriod'])->name('evaluations.periods.create');
        Route::post('/evaluacion/periodos/{period}/activar', [App\Http\Controllers\EvaluationController::class, 'activatePeriod'])->name('evaluations.periods.activate');
        Route::post('/evaluacion/periodos/{period}/cerrar', [App\Http\Controllers\EvaluationController::class, 'closePeriod'])->name('evaluations.periods.close');
    });

    Route::get('/evaluacion/completada', function() {
        return view('evaluations.success');
    })->name('evaluations.success');
});

// API para búsqueda de jugadores (solo de la misma categoría)
Route::middleware('auth')->get('/api/search-players', function(Illuminate\Http\Request $request) {
    $query = $request->input('q', '');
    $currentUser = auth()->user();
    $categoryId = $currentUser->profile->user_category_id ?? null;

    $players = App\Models\User::where('role', 'jugador')
        ->where('id', '!=', $currentUser->id) // Excluir a sí mismo
        ->where('name', 'LIKE', "%{$query}%")
        ->when($categoryId, function($q) use ($categoryId) {
            return $q->whereHas('profile', function($query) use ($categoryId) {
                $query->where('user_category_id', $categoryId);
            });
        })
        ->with('profile.category')
        ->limit(10)
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'position' => $user->profile->position ?? 'Sin posición',
                'category' => $user->profile->category->name ?? 'Sin categoría'
            ];
        });

    return response()->json($players);
});

// API para jugadores de la categoría del usuario actual
Route::middleware('auth')->get('/api/category-players', function() {
    $currentUser = auth()->user();
    $categoryId = $currentUser->profile->user_category_id ?? null;

    $players = App\Models\User::where('role', 'jugador')
        ->where('id', '!=', $currentUser->id)
        ->when($categoryId, function($query) use ($categoryId) {
            return $query->whereHas('profile', function($q) use ($categoryId) {
                $q->where('user_category_id', $categoryId);
            });
        })
        ->with('profile')
        ->limit(5)
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'position' => $user->profile->position ?? 'Sin posición',
                'category' => $user->profile->category->name ?? 'Sin categoría'
            ];
        });

    return response()->json($players);
});

// ======================================
// SUPER ADMIN ROUTES
// ======================================
Route::middleware(['auth', 'super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/organizations', [App\Http\Controllers\SuperAdminController::class, 'organizations'])->name('organizations');
    Route::get('/organizations/create', [App\Http\Controllers\SuperAdminController::class, 'createOrganization'])->name('organizations.create');
    Route::post('/organizations', [App\Http\Controllers\SuperAdminController::class, 'storeOrganization'])->name('organizations.store');
    Route::get('/organizations/{organization}/edit', [App\Http\Controllers\SuperAdminController::class, 'editOrganization'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [App\Http\Controllers\SuperAdminController::class, 'updateOrganization'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [App\Http\Controllers\SuperAdminController::class, 'destroyOrganization'])->name('organizations.destroy');
    Route::get('/organizations/{organization}/assign-admin', [App\Http\Controllers\SuperAdminController::class, 'assignAdminForm'])->name('organizations.assign-admin');
    Route::post('/organizations/{organization}/assign-admin', [App\Http\Controllers\SuperAdminController::class, 'assignAdmin'])->name('organizations.assign-admin.store');
    Route::post('/organizations/{organization}/create-user', [App\Http\Controllers\SuperAdminController::class, 'createUserForOrganization'])->name('organizations.create-user');
    Route::get('/users', [App\Http\Controllers\SuperAdminController::class, 'users'])->name('users');
    Route::get('/storage', [App\Http\Controllers\SuperAdminController::class, 'storageStats'])->name('storage');
});
