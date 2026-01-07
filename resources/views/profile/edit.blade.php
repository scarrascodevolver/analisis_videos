@extends('layouts.app')

@section('page_title', 'Editar Perfil')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('profile.show') }}">Perfil</a></li>
<li class="breadcrumb-item active">Editar</li>
@endsection

@section('css')
.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--color-primary, #005461);
}

.file-upload-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.file-upload-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-upload-btn {
    cursor: pointer;
}

/* Camera Modal Styles */
.camera-modal .modal-dialog {
    max-width: 600px;
}

.camera-preview {
    width: 100%;
    height: 400px;
    background: #000;
    border-radius: 8px;
    object-fit: cover;
}

.capture-controls {
    text-align: center;
    margin-top: 15px;
}

.capture-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid var(--color-primary, #005461);
    background: #fff;
    font-size: 24px;
    color: var(--color-primary, #005461);
    cursor: pointer;
    transition: all 0.3s ease;
}

.capture-btn:hover {
    background: var(--color-primary, #005461);
    color: #fff;
}

.upload-options {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .upload-options {
        flex-direction: column;
        align-items: center;
    }

    .upload-options .btn {
        width: 100%;
        max-width: 200px;
    }
}

/* Rugby theme buttons */
.btn-rugby {
    background-color: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    color: #fff;
}

.btn-rugby:hover {
    background-color: var(--color-primary-hover, #003d4a);
    border-color: var(--color-primary-hover, #003d4a);
    color: #fff;
}

.btn-outline-rugby {
    color: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    background-color: transparent;
}

.btn-outline-rugby:hover {
    background-color: var(--color-primary, #005461);
    border-color: var(--color-primary, #005461);
    color: #fff;
}
@endsection

@section('main_content')
<div class="row">
    <div class="col-md-4">
        <!-- Avatar Section -->
        <div class="card">
            <div class="card-header rugby-green">
                <h3 class="card-title">
                    <i class="fas fa-camera"></i> Foto de Perfil
                </h3>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($user->profile && $user->profile->avatar)
                        <img id="avatar-preview" src="{{ asset('storage/' . $user->profile->avatar) }}"
                             alt="Avatar"
                             class="avatar-preview">
                    @else
                        <img id="avatar-preview" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNjAiIGN5PSI2MCIgcj0iNjAiIGZpbGw9IiNlOWVjZWYiLz48cGF0aCBkPSJtNjAgNThjMTEuMDQ2IDAgMjAtOC45NTQgMjAtMjBzLTguOTU0LTIwLTIwLTIwLTIwIDguOTU0LTIwIDIwIDguOTU0IDIwIDIwIDIwem0wIDEwYy0xMy4zNiAwLTQwIDYuNy00MCAyMHYxMGg4MHYtMTBjMC0xMy4zLTI2LjY0LTIwLTQwLTIweiIgZmlsbD0iIzllYTNhOCIvPjwvc3ZnPg=="
                             alt="Avatar por defecto"
                             class="avatar-preview">
                    @endif
                </div>

                <div class="upload-options">
                    <button type="button" class="btn btn-rugby" data-toggle="modal" data-target="#cameraModal">
                        <i class="fas fa-camera"></i> Tomar Foto
                    </button>
                    <div class="file-upload-wrapper">
                        <label for="avatar" class="btn btn-outline-rugby file-upload-btn">
                            <i class="fas fa-upload"></i> Subir Archivo
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                    </div>
                </div>

                @if($user->profile && $user->profile->avatar)
                <div class="mt-2">
                    <form action="{{ route('profile.avatar.remove') }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Hidden file input for avatar -->
            <input type="file" name="avatar" id="avatar-form" style="display: none;" accept="image/*">

            <!-- Basic Information -->
            <div class="card">
                <div class="card-header rugby-green">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> Información Básica
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="user_category_id">Categoría <span class="text-danger">*</span></label>
                                <select class="form-control @error('user_category_id') is-invalid @enderror"
                                        id="user_category_id" name="user_category_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('user_category_id', $user->profile->user_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_category_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Player Information (show only for players) -->
            @if($user->role === 'jugador')
            <div class="card">
                <div class="card-header rugby-green">
                    <h3 class="card-title">
                        <i class="fas fa-running"></i> Información de Jugador
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="position">Posición Principal</label>
                                <select class="form-control @error('position') is-invalid @enderror" id="position" name="position">
                                    <option value="">Seleccionar posición</option>
                                    <optgroup label="Forwards">
                                        <option value="Pilar Izquierdo" {{ old('position', $user->profile->position ?? '') == 'Pilar Izquierdo' ? 'selected' : '' }}>Pilar Izquierdo</option>
                                        <option value="Hooker" {{ old('position', $user->profile->position ?? '') == 'Hooker' ? 'selected' : '' }}>Hooker</option>
                                        <option value="Pilar Derecho" {{ old('position', $user->profile->position ?? '') == 'Pilar Derecho' ? 'selected' : '' }}>Pilar Derecho</option>
                                        <option value="Segunda Línea" {{ old('position', $user->profile->position ?? '') == 'Segunda Línea' ? 'selected' : '' }}>Segunda Línea</option>
                                        <option value="Ala" {{ old('position', $user->profile->position ?? '') == 'Ala' ? 'selected' : '' }}>Ala</option>
                                        <option value="Octavo" {{ old('position', $user->profile->position ?? '') == 'Octavo' ? 'selected' : '' }}>Octavo</option>
                                    </optgroup>
                                    <optgroup label="Backs">
                                        <option value="Medio Scrum" {{ old('position', $user->profile->position ?? '') == 'Medio Scrum' ? 'selected' : '' }}>Medio Scrum</option>
                                        <option value="Apertura" {{ old('position', $user->profile->position ?? '') == 'Apertura' ? 'selected' : '' }}>Apertura</option>
                                        <option value="Centro" {{ old('position', $user->profile->position ?? '') == 'Centro' ? 'selected' : '' }}>Centro</option>
                                        <option value="Wing" {{ old('position', $user->profile->position ?? '') == 'Wing' ? 'selected' : '' }}>Wing</option>
                                        <option value="Fullback" {{ old('position', $user->profile->position ?? '') == 'Fullback' ? 'selected' : '' }}>Fullback</option>
                                    </optgroup>
                                </select>
                                @error('position')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="secondary_position">Posición Secundaria</label>
                                <select class="form-control @error('secondary_position') is-invalid @enderror" id="secondary_position" name="secondary_position">
                                    <option value="">Seleccionar posición secundaria</option>
                                    <optgroup label="Forwards">
                                        <option value="Pilar Izquierdo" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Pilar Izquierdo' ? 'selected' : '' }}>Pilar Izquierdo</option>
                                        <option value="Hooker" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Hooker' ? 'selected' : '' }}>Hooker</option>
                                        <option value="Pilar Derecho" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Pilar Derecho' ? 'selected' : '' }}>Pilar Derecho</option>
                                        <option value="Segunda Línea" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Segunda Línea' ? 'selected' : '' }}>Segunda Línea</option>
                                        <option value="Ala" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Ala' ? 'selected' : '' }}>Ala</option>
                                        <option value="Octavo" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Octavo' ? 'selected' : '' }}>Octavo</option>
                                    </optgroup>
                                    <optgroup label="Backs">
                                        <option value="Medio Scrum" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Medio Scrum' ? 'selected' : '' }}>Medio Scrum</option>
                                        <option value="Apertura" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Apertura' ? 'selected' : '' }}>Apertura</option>
                                        <option value="Centro" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Centro' ? 'selected' : '' }}>Centro</option>
                                        <option value="Wing" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Wing' ? 'selected' : '' }}>Wing</option>
                                        <option value="Fullback" {{ old('secondary_position', $user->profile->secondary_position ?? '') == 'Fullback' ? 'selected' : '' }}>Fullback</option>
                                    </optgroup>
                                </select>
                                @error('secondary_position')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="player_number">Número de Camiseta</label>
                                <input type="number" class="form-control @error('player_number') is-invalid @enderror"
                                       id="player_number" name="player_number" min="1" max="99"
                                       value="{{ old('player_number', $user->profile->player_number ?? '') }}">
                                @error('player_number')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="weight">Peso (kg)</label>
                                <input type="number" class="form-control @error('weight') is-invalid @enderror"
                                       id="weight" name="weight" min="40" max="200"
                                       value="{{ old('weight', $user->profile->weight ?? '') }}">
                                @error('weight')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="height">Altura (cm)</label>
                                <input type="number" class="form-control @error('height') is-invalid @enderror"
                                       id="height" name="height" min="150" max="220"
                                       value="{{ old('height', $user->profile->height ?? '') }}">
                                @error('height')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_birth">Fecha de Nacimiento</label>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                                       id="date_of_birth" name="date_of_birth"
                                       value="{{ old('date_of_birth', $user->profile && $user->profile->date_of_birth ? $user->profile->date_of_birth->format('Y-m-d') : '') }}">
                                @error('date_of_birth')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif


            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body text-right">
                    <a href="{{ route('profile.show') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-rugby">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Camera Modal -->
<div class="modal fade camera-modal" id="cameraModal" tabindex="-1" role="dialog" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header rugby-green">
                <h5 class="modal-title" id="cameraModalLabel">
                    <i class="fas fa-camera"></i> Tomar Foto de Perfil
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="camera-container">
                    <video id="cameraVideo" class="camera-preview" autoplay muted></video>
                    <canvas id="cameraCanvas" style="display: none;"></canvas>
                </div>
                <div class="capture-controls">
                    <button type="button" id="captureBtn" class="capture-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="mt-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Posiciona tu rostro en el centro y presiona el botón para capturar
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="useCapturedPhoto" class="btn btn-rugby" style="display: none;">
                    <i class="fas fa-check"></i> Usar Esta Foto
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let stream = null;
    let capturedImageData = null;

    // Preview avatar when file selected
    $('#avatar').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#avatar-preview').attr('src', e.target.result);
                capturedImageData = null; // Clear camera data if file selected
            };
            reader.readAsDataURL(file);

            // Copy file to form input
            $('#avatar-form')[0].files = e.target.files;
        }
    });

    // Open camera when modal is shown
    $('#cameraModal').on('shown.bs.modal', function() {
        openCamera();
    });

    // Close camera when modal is hidden
    $('#cameraModal').on('hidden.bs.modal', function() {
        closeCamera();
        $('#useCapturedPhoto').hide();
        capturedImageData = null;
    });

    // Open camera function
    async function openCamera() {
        try {
            const constraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user' // Front camera on mobile
                }
            };

            stream = await navigator.mediaDevices.getUserMedia(constraints);
            const video = document.getElementById('cameraVideo');
            video.srcObject = stream;

            console.log('✅ Cámara iniciada correctamente');
        } catch (error) {
            console.error('❌ Error al acceder a la cámara:', error);
            alert('No se pudo acceder a la cámara. Verifica los permisos.');
        }
    }

    // Close camera function
    function closeCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    // Capture photo
    $('#captureBtn').on('click', function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const context = canvas.getContext('2d');

        // Set canvas size to video size
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Draw video frame to canvas
        context.drawImage(video, 0, 0);

        // Convert to blob and show preview
        canvas.toBlob(function(blob) {
            capturedImageData = blob;
            const url = URL.createObjectURL(blob);
            $('#avatar-preview').attr('src', url);
            $('#useCapturedPhoto').show();

            console.log('📸 Foto capturada correctamente');
        }, 'image/jpeg', 0.8);
    });

    // Use captured photo
    $('#useCapturedPhoto').on('click', function() {
        if (capturedImageData) {
            // Create file from blob
            const file = new File([capturedImageData], 'camera-photo.jpg', {
                type: 'image/jpeg'
            });

            // Create FileList and assign to input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            $('#avatar-form')[0].files = dataTransfer.files;

            // Clear regular file input
            $('#avatar').val('');

            $('#cameraModal').modal('hide');
            console.log('✅ Foto de cámara lista para subir');
        }
    });

    // Form submission with avatar
    $('form').on('submit', function() {
        if ($('#avatar')[0].files.length > 0) {
            $('#avatar-form')[0].files = $('#avatar')[0].files;
        }
    });

    // Check camera support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.warn('⚠️ getUserMedia no está soportado en este navegador');
        $('[data-target="#cameraModal"]').prop('disabled', true)
            .attr('title', 'Cámara no soportada en este navegador');
    }
});
</script>
@endsection