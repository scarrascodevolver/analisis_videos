{{-- Sync Tool Modal --}}
<div class="modal fade" id="syncToolModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header bg-rugby">
                <h5 class="modal-title text-white">
                    <i class="fas fa-sync-alt"></i> Sincronizar Ángulo: <span id="syncAngleName"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="background: #1a1a1a;">
                {{-- Instructions --}}
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Instrucciones:</h6>
                    <ol class="mb-0">
                        <li>Busca un <strong>evento obvio</strong> visible en ambos videos (kickoff, try, penal, scrum)</li>
                        <li>Usa los controles para encontrar ese momento en ambos videos</li>
                        <li>Ajusta el slider hasta que ambos muestren el mismo momento</li>
                        <li>Usa el botón <strong>"Probar Sincronización"</strong> para verificar</li>
                        <li>Guarda cuando estés satisfecho con la sincronización</li>
                    </ol>
                </div>

                {{-- Clip Reference --}}
                @if($video->clips->count() > 0)
                <div class="card mb-3" style="background: #2a2a2a; border-color: #444;">
                    <div class="card-body">
                        <h6 class="text-white mb-2">
                            <i class="fas fa-bookmark"></i> Usar Clip como Referencia (Recomendado)
                        </h6>
                        <select id="referenceClipSelect" class="form-control">
                            <option value="">-- Seleccionar Clip del Master --</option>
                            @foreach($video->clips->take(20) as $clip)
                                <option value="{{ $clip->id }}" data-start="{{ $clip->start_time }}">
                                    {{ $clip->category->name ?? 'Sin categoría' }} - {{ $clip->name ?? 'Clip sin nombre' }} ({{ gmdate('i:s', $clip->start_time) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Selecciona un clip del master para saltar automáticamente a ese momento
                        </small>
                    </div>
                </div>
                @endif

                {{-- Side-by-Side Video Players --}}
                <div class="row">
                    {{-- Master Video --}}
                    <div class="col-md-6">
                        <div class="card" style="background: #2a2a2a; border-color: #444;">
                            <div class="card-header bg-rugby-dark">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-video"></i> Master ({{ $video->camera_angle ?? 'Tribuna Central' }})
                                </h6>
                            </div>
                            <div class="card-body p-2">
                                <video id="syncMasterVideo" controls style="width: 100%; max-height: 400px; background: #000;">
                                    <source src="{{ route('videos.stream', $video->id) }}" type="video/mp4">
                                </video>
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm d-flex" role="group">
                                        <button type="button" class="btn btn-secondary" id="masterBackward5">
                                            <i class="fas fa-backward"></i> 5s
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="masterBackward1">
                                            <i class="fas fa-step-backward"></i> 1s
                                        </button>
                                        <button type="button" class="btn btn-rugby flex-grow-1" id="masterPlayPause">
                                            <i class="fas fa-play"></i> Play
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="masterForward1">
                                            <i class="fas fa-step-forward"></i> 1s
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="masterForward5">
                                            <i class="fas fa-forward"></i> 5s
                                        </button>
                                    </div>
                                    <div class="text-center mt-2 text-white">
                                        <i class="fas fa-clock"></i> Tiempo: <strong id="masterTimeDisplay">00:00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Slave Video --}}
                    <div class="col-md-6">
                        <div class="card" style="background: #2a2a2a; border-color: #444;">
                            <div class="card-header bg-dark">
                                <h6 class="mb-0 text-white">
                                    <i class="fas fa-video"></i> Ángulo: <span id="slaveAngleName"></span>
                                </h6>
                            </div>
                            <div class="card-body p-2">
                                <video id="syncSlaveVideo" controls style="width: 100%; max-height: 400px; background: #000;">
                                    {{-- Source will be set by JavaScript --}}
                                </video>
                                <div class="mt-2">
                                    <div class="btn-group btn-group-sm d-flex" role="group">
                                        <button type="button" class="btn btn-secondary" id="slaveBackward5">
                                            <i class="fas fa-backward"></i> 5s
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="slaveBackward1">
                                            <i class="fas fa-step-backward"></i> 1s
                                        </button>
                                        <button type="button" class="btn btn-info flex-grow-1" id="slavePlayPause">
                                            <i class="fas fa-play"></i> Play
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="slaveForward1">
                                            <i class="fas fa-step-forward"></i> 1s
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="slaveForward5">
                                            <i class="fas fa-forward"></i> 5s
                                        </button>
                                    </div>
                                    <div class="text-center mt-2 text-white">
                                        <i class="fas fa-clock"></i> Tiempo: <strong id="slaveTimeDisplay">00:00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sync Offset Adjustment --}}
                <div class="card mt-3" style="background: #2a2a2a; border-color: #444;">
                    <div class="card-body">
                        <h6 class="text-white mb-3">
                            <i class="fas fa-sliders-h"></i> Ajuste de Sincronización
                        </h6>

                        {{-- Radio buttons --}}
                        <div class="form-group">
                            <label class="text-white">Este video está:</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="offsetType" id="offsetNegative" value="negative">
                                    <label class="form-check-label text-white" for="offsetNegative">
                                        Adelantado (empezó antes que el master)
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="offsetType" id="offsetPositive" value="positive" checked>
                                    <label class="form-check-label text-white" for="offsetPositive">
                                        Atrasado (empezó después que el master)
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Slider --}}
                        <div class="form-group">
                            <label class="text-white">Ajuste: <strong id="offsetValueDisplay">+0.0</strong> segundos</label>
                            <input type="range" class="custom-range" id="syncOffsetSlider" min="0" max="300" step="0.5" value="0">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">0s</small>
                                <small class="text-muted">±150s</small>
                                <small class="text-muted">±300s (5 min)</small>
                            </div>
                        </div>

                        {{-- Fine adjustment --}}
                        <div class="text-center mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-secondary" id="offsetMinus1">-1s</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="offsetMinus05">-0.5s</button>
                                <button type="button" class="btn btn-sm btn-rugby" id="offsetReset">Reset</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="offsetPlus05">+0.5s</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="offsetPlus1">+1s</button>
                            </div>
                        </div>

                        {{-- Test sync button --}}
                        <button type="button" class="btn btn-warning btn-block" id="testSyncBtn">
                            <i class="fas fa-play-circle"></i> Probar Sincronización (5 segundos)
                        </button>
                        <small class="form-text text-muted text-center">
                            Reproduce ambos videos sincronizados por 5 segundos para verificar
                        </small>
                    </div>
                </div>

                {{-- Reference Event (optional) --}}
                <div class="card mt-3" style="background: #2a2a2a; border-color: #444;">
                    <div class="card-body">
                        <label class="text-white">Evento de Referencia (Opcional)</label>
                        <input type="text" id="referenceEventInput" class="form-control" placeholder="Ej: Kickoff Inicial, Try Minuto 15">
                        <small class="form-text text-muted">
                            Documenta qué evento usaste para sincronizar (útil para futuras referencias)
                        </small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-rugby" id="saveSyncBtn">
                    <i class="fas fa-save"></i> Guardar Sincronización
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Sync Tool JavaScript
jQuery(function($) {
    let currentSlaveVideoId = null;
    let currentOffset = 0;
    let masterVideo = null;
    let slaveVideo = null;

    window.openSyncModal = function(slaveVideoId) {
        currentSlaveVideoId = slaveVideoId;

        // Load slave video info
        $.ajax({
            url: `/videos/${slaveVideoId}/multi-camera/stream-url`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    initSyncTool(response.video);
                    $('#syncToolModal').modal('show');
                }
            }
        });
    };

    function initSyncTool(slaveInfo) {
        // Set slave video name
        $('#syncAngleName').text(slaveInfo.camera_angle);
        $('#slaveAngleName').text(slaveInfo.camera_angle);

        // Set slave video source
        $('#syncSlaveVideo').attr('src', slaveInfo.stream_url);

        // Get video elements
        masterVideo = document.getElementById('syncMasterVideo');
        slaveVideo = document.getElementById('syncSlaveVideo');

        // Set initial offset if exists
        if (slaveInfo.sync_offset !== null && slaveInfo.sync_offset !== 0) {
            currentOffset = parseFloat(slaveInfo.sync_offset);
            $('#syncOffsetSlider').val(Math.abs(currentOffset));
            $('#offsetType').val(currentOffset < 0 ? 'negative' : 'positive');
            updateOffsetDisplay();
        } else {
            currentOffset = 0;
            $('#syncOffsetSlider').val(0);
        }

        // Setup event listeners
        setupSyncControls();
    }

    function setupSyncControls() {
        // Time display updates
        masterVideo.addEventListener('timeupdate', function() {
            $('#masterTimeDisplay').text(formatTime(this.currentTime));
        });

        slaveVideo.addEventListener('timeupdate', function() {
            $('#slaveTimeDisplay').text(formatTime(this.currentTime));
        });

        // Master controls
        $('#masterBackward5').off('click').on('click', () => masterVideo.currentTime = Math.max(0, masterVideo.currentTime - 5));
        $('#masterBackward1').off('click').on('click', () => masterVideo.currentTime = Math.max(0, masterVideo.currentTime - 1));
        $('#masterForward1').off('click').on('click', () => masterVideo.currentTime += 1);
        $('#masterForward5').off('click').on('click', () => masterVideo.currentTime += 5);
        $('#masterPlayPause').off('click').on('click', function() {
            if (masterVideo.paused) {
                masterVideo.play();
                $(this).html('<i class="fas fa-pause"></i> Pause');
            } else {
                masterVideo.pause();
                $(this).html('<i class="fas fa-play"></i> Play');
            }
        });

        // Slave controls
        $('#slaveBackward5').off('click').on('click', () => slaveVideo.currentTime = Math.max(0, slaveVideo.currentTime - 5));
        $('#slaveBackward1').off('click').on('click', () => slaveVideo.currentTime = Math.max(0, slaveVideo.currentTime - 1));
        $('#slaveForward1').off('click').on('click', () => slaveVideo.currentTime += 1);
        $('#slaveForward5').off('click').on('click', () => slaveVideo.currentTime += 5);
        $('#slavePlayPause').off('click').on('click', function() {
            if (slaveVideo.paused) {
                slaveVideo.play();
                $(this).html('<i class="fas fa-pause"></i> Pause');
            } else {
                slaveVideo.pause();
                $(this).html('<i class="fas fa-play"></i> Play');
            }
        });

        // Offset adjustment
        $('#syncOffsetSlider').off('input').on('input', function() {
            updateOffsetFromSlider();
        });

        $('input[name="offsetType"]').off('change').on('change', function() {
            updateOffsetFromSlider();
        });

        $('#offsetMinus1').off('click').on('click', () => adjustOffset(-1));
        $('#offsetMinus05').off('click').on('click', () => adjustOffset(-0.5));
        $('#offsetPlus05').off('click').on('click', () => adjustOffset(0.5));
        $('#offsetPlus1').off('click').on('click', () => adjustOffset(1));
        $('#offsetReset').off('click').on('click', () => {
            $('#syncOffsetSlider').val(0);
            updateOffsetFromSlider();
        });

        // Reference clip select
        $('#referenceClipSelect').off('change').on('change', function() {
            const startTime = $(this).find(':selected').data('start');
            if (startTime !== undefined) {
                masterVideo.currentTime = startTime;
                masterVideo.pause();
                $('#masterPlayPause').html('<i class="fas fa-play"></i> Play');
            }
        });

        // Test sync
        $('#testSyncBtn').off('click').on('click', testSync);

        // Save sync
        $('#saveSyncBtn').off('click').on('click', saveSync);
    }

    function updateOffsetFromSlider() {
        const sliderValue = parseFloat($('#syncOffsetSlider').val());
        const isNegative = $('input[name="offsetType"]:checked').val() === 'negative';
        currentOffset = isNegative ? -sliderValue : sliderValue;
        updateOffsetDisplay();
    }

    function adjustOffset(amount) {
        const sliderValue = parseFloat($('#syncOffsetSlider').val());
        const newValue = Math.max(0, Math.min(300, sliderValue + Math.abs(amount)));
        $('#syncOffsetSlider').val(newValue);

        if (amount < 0) {
            $('#offsetNegative').prop('checked', true);
        } else if (amount > 0) {
            $('#offsetPositive').prop('checked', true);
        }

        updateOffsetFromSlider();
    }

    function updateOffsetDisplay() {
        const sign = currentOffset >= 0 ? '+' : '';
        $('#offsetValueDisplay').text(sign + currentOffset.toFixed(1));
    }

    function testSync() {
        const masterTime = masterVideo.currentTime;
        const slaveTime = masterTime + currentOffset;

        // Pause both
        masterVideo.pause();
        slaveVideo.pause();

        // Set slave time based on offset
        slaveVideo.currentTime = slaveTime;

        // Wait for seek to complete
        setTimeout(() => {
            // Play both
            masterVideo.play();
            slaveVideo.play();

            // Stop after 5 seconds
            setTimeout(() => {
                masterVideo.pause();
                slaveVideo.pause();
                $('#masterPlayPause').html('<i class="fas fa-play"></i> Play');
                $('#slavePlayPause').html('<i class="fas fa-play"></i> Play');
            }, 5000);
        }, 500);
    }

    function saveSync() {
        const referenceEvent = $('#referenceEventInput').val();

        const btn = $('#saveSyncBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: `/videos/${currentSlaveVideoId}/multi-camera/sync`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                sync_offset: currentOffset,
                reference_event: referenceEvent || null
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Video sincronizado correctamente');
                    $('#syncToolModal').modal('hide');

                    // Reload angles in main page
                    if (typeof loadAngles === 'function') {
                        loadAngles();
                    } else {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al sincronizar');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Sincronización');
            }
        });
    }

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
});
</script>
