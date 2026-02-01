<!-- Modal Editar Clip -->
@if(in_array(auth()->user()->role, ['analista', 'entrenador']))
<div class="modal fade" id="editClipModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #1a1a1a; color: #fff;">
            <div class="modal-header" style="border-bottom: 1px solid #333; background: #003d4a;">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Clip</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editClipForm">
                    <input type="hidden" id="editClipId">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-play"></i> Tiempo Inicio (seg)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="editClipStart" class="form-control" style="background: #252525; color: #fff; border-color: #333;" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-info" id="useCurrentStartBtn" title="Usar tiempo actual del video">
                                        <i class="fas fa-crosshairs"></i>
                                    </button>
                                </div>
                            </div>
                            <small style="color: #888;" id="editClipStartFormatted">00:00</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-stop"></i> Tiempo Fin (seg)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" id="editClipEnd" class="form-control" style="background: #252525; color: #fff; border-color: #333;" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-info" id="useCurrentEndBtn" title="Usar tiempo actual del video">
                                        <i class="fas fa-crosshairs"></i>
                                    </button>
                                </div>
                            </div>
                            <small style="color: #888;" id="editClipEndFormatted">00:00</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Título</label>
                        <input type="text" id="editClipTitle" class="form-control" style="background: #252525; color: #fff; border-color: #333;" placeholder="Título del clip...">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Notas</label>
                        <textarea id="editClipNotes" class="form-control" style="background: #252525; color: #fff; border-color: #333;" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>

                    <div class="alert alert-info py-2" style="background: #003d4a; border: none;">
                        <small>
                            <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Reproduce el video, pausa en el momento deseado y usa los botones <i class="fas fa-crosshairs"></i> para capturar el tiempo exacto.
                        </small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button type="button" class="btn btn-sm" style="background: #252525; color: #fff;" id="previewClipBtn">
                            <i class="fas fa-play-circle"></i> Preview
                        </button>
                        <div>
                            <button type="button" class="btn btn-sm" style="background: #333; color: #888;" id="adjustClipMinus">
                                <i class="fas fa-minus"></i> 0.5s
                            </button>
                            <button type="button" class="btn btn-sm" style="background: #333; color: #888;" id="adjustClipPlus">
                                <i class="fas fa-plus"></i> 0.5s
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn" style="background: #333; color: #fff;" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" style="background: #00B7B5; color: #fff;" id="saveEditClipBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
@endif
