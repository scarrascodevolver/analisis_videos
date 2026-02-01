@extends('layouts.app')

@section('main_content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.organizations') }}">Organizaciones</a></li>
                    <li class="breadcrumb-item active">Configuraciones: {{ $organization->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-cog text-primary mr-2"></i>
                Configuraciones de Organización
            </h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <h5><i class="icon fas fa-ban"></i> Errores de validación</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form action="{{ route('super-admin.organizations.settings.update', $organization) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Regional Settings Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-globe mr-2"></i>Regional Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="timezone">Timezone <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('timezone') is-invalid @enderror"
                                    id="timezone"
                                    name="timezone"
                                    required>
                                @foreach($timezones as $region => $zones)
                                    <optgroup label="{{ $region }}">
                                        @foreach($zones as $value => $label)
                                            <option value="{{ $value }}"
                                                    {{ old('timezone', $organization->timezone) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Select the timezone for this organization. This affects video compression schedules.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Compression Settings Card -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 bg-success text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-video mr-2"></i>Video Compression Strategy
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="d-block mb-3">Compression Strategy <span class="text-danger">*</span></label>

                            <div class="custom-control custom-radio mb-2">
                                <input type="radio"
                                       id="strategy_immediate"
                                       name="compression_strategy"
                                       value="immediate"
                                       class="custom-control-input"
                                       {{ old('compression_strategy', $organization->compression_strategy) == 'immediate' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="strategy_immediate">
                                    <strong>Immediate</strong>
                                    <small class="d-block text-muted">Compress all videos immediately on upload</small>
                                </label>
                            </div>

                            <div class="custom-control custom-radio mb-2">
                                <input type="radio"
                                       id="strategy_nocturnal"
                                       name="compression_strategy"
                                       value="nocturnal"
                                       class="custom-control-input"
                                       {{ old('compression_strategy', $organization->compression_strategy) == 'nocturnal' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="strategy_nocturnal">
                                    <strong>Nocturnal</strong>
                                    <small class="d-block text-muted">Compress all videos only during time window</small>
                                </label>
                            </div>

                            <div class="custom-control custom-radio mb-3">
                                <input type="radio"
                                       id="strategy_hybrid"
                                       name="compression_strategy"
                                       value="hybrid"
                                       class="custom-control-input"
                                       {{ old('compression_strategy', $organization->compression_strategy) == 'hybrid' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="strategy_hybrid">
                                    <strong>Hybrid (Recommended)</strong>
                                    <small class="d-block text-muted">Small videos immediate, large videos nocturnal</small>
                                </label>
                            </div>

                            @error('compression_strategy')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nocturnal/Hybrid Settings Row -->
        <div class="row">
            <!-- Time Window Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow" id="time-window-card">
                    <div class="card-header py-3 bg-info text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-clock mr-2"></i>Compression Time Window
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="compression_start_hour">Start Hour <span class="text-danger">*</span></label>
                                    <select class="form-control @error('compression_start_hour') is-invalid @enderror"
                                            id="compression_start_hour"
                                            name="compression_start_hour"
                                            required>
                                        @for($i = 0; $i <= 23; $i++)
                                            <option value="{{ $i }}"
                                                    {{ old('compression_start_hour', $organization->compression_start_hour) == $i ? 'selected' : '' }}>
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00
                                            </option>
                                        @endfor
                                    </select>
                                    @error('compression_start_hour')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="compression_end_hour">End Hour <span class="text-danger">*</span></label>
                                    <select class="form-control @error('compression_end_hour') is-invalid @enderror"
                                            id="compression_end_hour"
                                            name="compression_end_hour"
                                            required>
                                        @for($i = 0; $i <= 23; $i++)
                                            <option value="{{ $i }}"
                                                    {{ old('compression_end_hour', $organization->compression_end_hour) == $i ? 'selected' : '' }}>
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}:00
                                            </option>
                                        @endfor
                                    </select>
                                    @error('compression_end_hour')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light mb-0" id="duration-display">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Duration: <span id="window-duration">{{ $organization->compression_end_hour - $organization->compression_start_hour }} hours</span></strong>
                            available for compression
                        </div>

                        <small class="form-text text-muted mt-2">
                            Videos will only be compressed during this time window (in organization's timezone)
                        </small>
                    </div>
                </div>
            </div>

            <!-- Hybrid Threshold Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow" id="hybrid-threshold-card">
                    <div class="card-header py-3 bg-warning text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-balance-scale mr-2"></i>Hybrid Strategy Threshold
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="compression_hybrid_threshold">Threshold (MB) <span class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('compression_hybrid_threshold') is-invalid @enderror"
                                   id="compression_hybrid_threshold"
                                   name="compression_hybrid_threshold"
                                   min="100"
                                   max="10000"
                                   step="50"
                                   value="{{ old('compression_hybrid_threshold', $organization->compression_hybrid_threshold) }}"
                                   required>
                            @error('compression_hybrid_threshold')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-light mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            <ul class="mb-0 pl-3">
                                <li><strong>Videos &lt; <span id="threshold-value">{{ $organization->compression_hybrid_threshold }}</span> MB:</strong> Compress immediately</li>
                                <li><strong>Videos &ge; <span id="threshold-value-2">{{ $organization->compression_hybrid_threshold }}</span> MB:</strong> Wait for time window</li>
                            </ul>
                        </div>

                        <small class="form-text text-muted mt-2">
                            Only applies when Hybrid strategy is selected. Range: 100-10000 MB.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('super-admin.organizations.edit', $organization) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Organization
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 for timezone dropdown
    $('#timezone').select2({
        theme: 'bootstrap4',
        placeholder: 'Select timezone',
        width: '100%'
    });

    // Update UI based on compression strategy
    function updateStrategyUI() {
        const strategy = $('input[name="compression_strategy"]:checked').val();

        if (strategy === 'immediate') {
            $('#time-window-card').addClass('opacity-50');
            $('#hybrid-threshold-card').addClass('opacity-50');
            $('#compression_start_hour, #compression_end_hour, #compression_hybrid_threshold').prop('disabled', true);
        } else if (strategy === 'nocturnal') {
            $('#time-window-card').removeClass('opacity-50');
            $('#hybrid-threshold-card').addClass('opacity-50');
            $('#compression_start_hour, #compression_end_hour').prop('disabled', false);
            $('#compression_hybrid_threshold').prop('disabled', true);
        } else if (strategy === 'hybrid') {
            $('#time-window-card').removeClass('opacity-50');
            $('#hybrid-threshold-card').removeClass('opacity-50');
            $('#compression_start_hour, #compression_end_hour, #compression_hybrid_threshold').prop('disabled', false);
        }
    }

    // Calculate and display window duration
    function updateWindowDuration() {
        const start = parseInt($('#compression_start_hour').val());
        const end = parseInt($('#compression_end_hour').val());
        const duration = end > start ? end - start : 0;
        $('#window-duration').text(duration + ' hours');
    }

    // Update threshold display values
    function updateThresholdDisplay() {
        const threshold = $('#compression_hybrid_threshold').val();
        $('#threshold-value, #threshold-value-2').text(threshold);
    }

    // Event listeners
    $('input[name="compression_strategy"]').change(updateStrategyUI);
    $('#compression_start_hour, #compression_end_hour').change(updateWindowDuration);
    $('#compression_hybrid_threshold').on('input', updateThresholdDisplay);

    // Initial UI state
    updateStrategyUI();
    updateWindowDuration();
    updateThresholdDisplay();
});
</script>
@endpush
@endsection
