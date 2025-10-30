{{-- Partial recursivo para mostrar respuestas anidadas --}}
<div class="reply comment-item border-left border-primary pl-3 mb-2" data-reply-id="{{ $reply->id }}">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <p class="mb-1">{{ $reply->comment }}</p>
            <small class="text-muted">
                <i class="fas fa-user"></i> {{ $reply->user->name }}
                <span class="badge badge-sm badge-{{
                    $reply->user->role === 'analista' ? 'primary' :
                    ($reply->user->role === 'entrenador' ? 'success' : 'info')
                }}">
                    {{ ucfirst($reply->user->role) }}
                </span>
                - {{ $reply->created_at->diffForHumans() }}
            </small>
            <!-- Botón para responder a esta respuesta -->
            <button class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                    data-comment-id="{{ $reply->id }}"
                    title="Responder a esta respuesta">
                <i class="fas fa-reply"></i> Responder
            </button>

            <!-- Badges de menciones -->
            @if($reply->mentionedUsers && $reply->mentionedUsers->count() > 0)
                <div class="mt-2">
                    <span class="badge badge-light border">
                        <i class="fas fa-at text-primary"></i>
                        Menciona a:
                        @foreach($reply->mentionedUsers as $mentionedUser)
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
        @if($reply->user_id === auth()->id())
            <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                    data-comment-id="{{ $reply->id }}"
                    title="Eliminar respuesta">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>

    <!-- Reply Form para respuestas anidadas -->
    <div class="reply-form mt-2" id="replyForm{{ $reply->id }}" style="display: none;">
        <form class="reply-form-submit" data-comment-id="{{ $reply->id }}" data-video-id="{{ $video->id }}">
            @csrf
            <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                      placeholder="Escribe tu respuesta..." required></textarea>
            <button class="btn btn-rugby btn-sm" type="submit">
                <i class="fas fa-reply"></i> Responder
            </button>
        </form>
    </div>

    <!-- Respuestas anidadas recursivas -->
    @if($reply->replies && $reply->replies->count() > 0)
        <div class="replies ml-3 mt-2">
            @foreach($reply->replies as $nestedReply)
                @include('videos.partials.reply', ['reply' => $nestedReply, 'video' => $video])
            @endforeach
        </div>
    @endif
</div>
