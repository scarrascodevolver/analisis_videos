<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoCommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VideoStreamController;
use App\Http\Controllers\PlayerApiController;

// Public route - Redirect directly to login
Route::redirect('/', '/login');

// Authentication Routes
Auth::routes();

// Video Streaming Routes (PUBLIC - no auth needed for video tags)
Route::get('videos/{video}/stream', [VideoStreamController::class, 'stream'])->name('videos.stream');
Route::get('stream/videos/{filename}', [VideoStreamController::class, 'streamByPath'])->name('videos.stream.file');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Main Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // Video Routes
    Route::resource('videos', VideoController::class);
    Route::post('videos/{video}/comments', [VideoCommentController::class, 'store'])->name('video.comments.store');
    Route::delete('comments/{comment}', [VideoCommentController::class, 'destroy'])->name('comments.destroy');

    // Player API Routes (for AJAX search functionality)
    Route::get('api/players/all', [PlayerApiController::class, 'all'])->name('api.players.all');
    Route::get('api/players/search', [PlayerApiController::class, 'search'])->name('api.players.search');
    Route::get('api/players/{player}/videos', [PlayerApiController::class, 'playerVideos'])->name('api.players.videos');
    
    // My Videos Routes
    Route::get('my-videos', [App\Http\Controllers\MyVideosController::class, 'index'])->name('my-videos');
    Route::patch('assignments/{assignment}/complete', [App\Http\Controllers\MyVideosController::class, 'markAsCompleted'])->name('assignments.complete');
    Route::get('assignments/{assignment}/video', [App\Http\Controllers\MyVideosController::class, 'show'])->name('assignments.show');

    // Analyst Routes
    Route::middleware(['role:analista'])->prefix('analyst')->name('analyst.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'analyst'])->name('dashboard');
        Route::resource('assignments', App\Http\Controllers\VideoAssignmentController::class);
        Route::patch('assignments/{assignment}/complete', [App\Http\Controllers\VideoAssignmentController::class, 'markCompleted'])->name('assignments.markCompleted');
        Route::post('assignments/{assignment}/accept', [App\Http\Controllers\VideoAssignmentController::class, 'playerAccept'])->name('assignments.playerAccept');
        Route::post('assignments/{assignment}/submit', [App\Http\Controllers\VideoAssignmentController::class, 'playerSubmit'])->name('assignments.playerSubmit');
        Route::post('assignments/bulk', [App\Http\Controllers\VideoAssignmentController::class, 'bulk'])->name('assignments.bulk');
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
