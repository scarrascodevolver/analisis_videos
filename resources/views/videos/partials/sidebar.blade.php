        <!-- Sidebar Section -->
        <div class="col-lg-2" id="sidebarSection">
            @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
            <!-- Tabs para alternar entre Comentarios y Clips -->
            <!-- Analistas/Entrenadores: Clips primero -->
            <div class="sidebar-tabs mb-2" style="display: flex; border-radius: 8px; overflow: hidden; background: #1a1a1a;">
                <button type="button" class="sidebar-tab" data-tab="comments" style="flex: 1; padding: 10px; border: none; background: #252525; color: #888; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-comments"></i> Comentarios
                </button>
                <button type="button" class="sidebar-tab active" data-tab="clips" style="flex: 1; padding: 10px; border: none; background: #005461; color: #fff; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    <i class="fas fa-film"></i> Clips <span id="sidebarClipCount" class="badge badge-light ml-1">0</span>
                </button>
            </div>
            @endif

            <!-- Tab Content: Comentarios -->
            <!-- Analistas/Entrenadores: oculto por defecto (Clips visible), Jugadores: visible -->
            <div id="tabComments" class="tab-content-sidebar" @if(in_array(auth()->user()->role, ['analista', 'entrenador'])) style="display: none;" @endif>
            <!-- Add Comment Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-comments"></i>
                        Agregar Comentario
                    </h5>
                </div>
                <div class="card-body py-2 px-3">
                    <form id="commentForm" action="{{ route('video.comments.store', $video) }}" method="POST" data-video-id="{{ $video->id }}">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="mb-1">Timestamp</label>
                            <div class="input-group">
                                <input type="number" id="timestamp_seconds" name="timestamp_seconds" 
                                       class="form-control" min="0" value="0" required>
                                <div class="input-group-append">
                                    <button type="button" id="useCurrentTime" class="btn btn-outline-secondary">
                                        <i class="fas fa-clock"></i> Actual
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted" id="timestampDisplay">00:00</small>
                        </div>

                        <div class="form-group mb-2">
                            <label class="mb-1">Comentario <small class="text-muted">(Usa @ para mencionar usuarios)</small></label>
                            <textarea name="comment" class="form-control" rows="3"
                                      placeholder="Describe lo que observas... (Escribe @ para mencionar)" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Categoría</label>
                                    <select name="category" class="form-control" required>
                                        <option value="tecnico">Técnico</option>
                                        <option value="tactico">Táctico</option>
                                        <option value="fisico">Físico</option>
                                        <option value="mental">Mental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label class="mb-1">Prioridad</label>
                                    <select name="priority" class="form-control" required>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Crítica</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-rugby btn-sm btn-block">
                            <i class="fas fa-comment"></i> Agregar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Comments List -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list"></i>
                        Comentarios ({{ $comments->count() }})
                    </h5>
                </div>
                <div class="card-body p-0 comments-scroll-container" style="max-height: 400px; overflow-y: scroll; overflow-x: hidden;">
                    @forelse($comments as $comment)
                        <div class="comment-item border-bottom p-2" data-timestamp="{{ $comment->timestamp_seconds }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <button class="btn btn-sm btn-rugby-light timestamp-btn mr-2" 
                                                data-timestamp="{{ $comment->timestamp_seconds }}">
                                            {{ $comment->formatted_timestamp }}
                                        </button>
                                        <span class="badge badge-{{ 
                                            $comment->category === 'tecnico' ? 'info' : 
                                            ($comment->category === 'tactico' ? 'warning' : 
                                            ($comment->category === 'fisico' ? 'success' : 'purple')) 
                                        }}">
                                            {{ ucfirst($comment->category) }}
                                        </span>
                                        <span class="badge badge-{{ 
                                            $comment->priority === 'critica' ? 'danger' : 
                                            ($comment->priority === 'alta' ? 'warning' : 
                                            ($comment->priority === 'media' ? 'info' : 'secondary')) 
                                        }} ml-1">
                                            {{ ucfirst($comment->priority) }}
                                        </span>
                                    </div>
                                    <p class="mb-2">{{ $comment->comment }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> {{ $comment->user->name }}
                                        <span class="badge badge-sm badge-{{ 
                                            $comment->user->role === 'analista' ? 'primary' : 
                                            ($comment->user->role === 'entrenador' ? 'success' : 'info') 
                                        }}">
                                            {{ ucfirst($comment->user->role) }}
                                        </span>
                                    </small>
                                    <small class="text-muted ml-2">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </small>

                                    <!-- Badges de menciones -->
                                    @if($comment->mentionedUsers && $comment->mentionedUsers->count() > 0)
                                        <div class="mt-2">
                                            <span class="badge badge-light border">
                                                <i class="fas fa-at text-primary"></i>
                                                Menciona a:
                                                @foreach($comment->mentionedUsers as $mentionedUser)
                                                    <span class="badge badge-{{
                                                        $mentionedUser->role === 'jugador' ? 'info' :
                                                        ($mentionedUser->role === 'entrenador' ? 'success' : 'primary')
                                                    }} ml-1">
                                                        {{ $mentionedUser->name }}
                                                    </span>
                                                @endforeach
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <button class="dropdown-item dropdown-item-sm reply-btn" data-comment-id="{{ $comment->id }}">
                                            <i class="fas fa-reply"></i> Responder
                                        </button>
                                        @if($comment->user_id === auth()->id())
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item dropdown-item-sm text-danger delete-comment-btn"
                                                    data-comment-id="{{ $comment->id }}">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Form (Hidden by default) -->
                            <div class="reply-form mt-3" id="replyForm{{ $comment->id }}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="{{ $comment->id }}" data-video-id="{{ $video->id }}">
                                    @csrf
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>

                            <!-- Replies -->
                            @if($comment->replies->count() > 0)
                                <div class="replies ml-4 mt-3">
                                    @foreach($comment->replies as $reply)
                                        @include('videos.partials.reply', ['reply' => $reply, 'video' => $video])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>No hay comentarios aún.</p>
                            <p>Sé el primero en agregar un comentario de análisis.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Annotations List -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-pen"></i>
                        Anotaciones (<span id="annotationsCount">0</span>)
                    </h5>
                </div>
                <div class="card-body p-0 comments-scroll-container" style="max-height: 300px; overflow-y: auto;" id="annotationsList">
                    <div class="text-center p-3 text-muted" id="noAnnotationsMessage">
                        <i class="fas fa-pen-fancy fa-2x mb-2"></i>
                        <p>No hay anotaciones aún.</p>
                    </div>
                    <!-- Las anotaciones se cargarán aquí via JavaScript -->
                </div>
            </div>
            </div><!-- End tabComments -->

            @if(in_array(auth()->user()->role, ['analista', 'entrenador']))
            <!-- Tab Content: Clips -->
            <!-- Analistas/Entrenadores: visible por defecto -->
            <div id="tabClips" class="tab-content-sidebar" style="display: block;">
                <div class="card" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-header py-2" style="background: #252525; border-bottom: 1px solid #333;">
                        <h6 class="mb-0" style="color: #fff;">
                            <i class="fas fa-film" style="color: #00B7B5;"></i> Clips del Video
                        </h6>
                    </div>
                    <div class="card-body p-0" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                        <div id="sidebarClipsList" style="color: #ccc;">
                            <div class="text-center py-4" style="color: #666;">
                                <i class="fas fa-film fa-2x mb-2"></i>
                                <p class="mb-0">Sin clips aún</p>
                                <small>Usa la botonera bajo el video para crear clips</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-2" style="background: #1a1a1a; border: 1px solid #333;">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-around text-center" style="color: #888; font-size: 12px;">
                            <div>
                                <div id="sidebarTotalClips" style="font-size: 18px; font-weight: bold; color: #00B7B5;">0</div>
                                <div>Total</div>
                            </div>
                            <div>
                                <div id="sidebarHighlights" style="font-size: 18px; font-weight: bold; color: #ffc107;">0</div>
                                <div>Destacados</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
