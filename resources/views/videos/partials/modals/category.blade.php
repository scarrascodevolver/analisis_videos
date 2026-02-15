<!-- Modal Crear/Editar Categoría -->
<style>
    .scope-option {
        transition: all 0.2s ease;
    }
    .scope-option:hover {
        background: #333 !important;
    }
    .scope-option input[type="radio"]:checked + div strong {
        color: #00B7B5 !important;
    }
    .scope-option:has(input[type="radio"]:checked) {
        border-color: #00B7B5 !important;
        background: rgba(0, 183, 181, 0.1) !important;
    }
    .scope-option:has(input[type="radio"]:disabled) {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background: #1a1a1a; color: #fff;">
            <div class="modal-header" style="border-bottom: 1px solid #333; background: #005461;">
                <h5 class="modal-title" id="categoryModalTitle"><i class="fas fa-plus"></i> Crear Categoría</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="catId" value="">
                    <input type="hidden" id="catVideoId" value="{{ $video->id ?? '' }}">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" style="background: #252525; color: #fff; border-color: #333;" required maxlength="50" placeholder="Ej: Try, Scrum, Tackle...">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Color <span class="text-danger">*</span></label>
                            <input type="color" name="color" id="catColor" class="form-control" style="height: 40px; background: #252525; border-color: #333;" value="#005461" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tecla rápida</label>
                            <input type="text" name="hotkey" id="catHotkey" class="form-control" style="background: #252525; color: #fff; border-color: #333;" maxlength="1" placeholder="Ej: t, s, k...">
                            <small style="color: #888;">Una letra para activar con teclado</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Visibilidad <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column" style="gap: 8px;">
                            <label class="scope-option" style="display: flex; align-items: flex-start; cursor: pointer; padding: 10px; background: #252525; border-radius: 6px; border: 2px solid transparent;">
                                <input type="radio" name="scope" value="organization" id="scopeOrg" style="margin-top: 3px; margin-right: 10px;" checked>
                                <div>
                                    <strong style="color: #fff;"><i class="fas fa-building"></i> Plantilla del club</strong>
                                    <div style="font-size: 12px; color: #888;">Todos los analistas la verán en todos los videos</div>
                                </div>
                            </label>
                            <label class="scope-option" style="display: flex; align-items: flex-start; cursor: pointer; padding: 10px; background: #252525; border-radius: 6px; border: 2px solid transparent;">
                                <input type="radio" name="scope" value="user" id="scopeUser" style="margin-top: 3px; margin-right: 10px;">
                                <div>
                                    <strong style="color: #fff;"><i class="fas fa-user"></i> Solo para mí</strong>
                                    <div style="font-size: 12px; color: #888;">Solo tú la verás, en todos tus videos</div>
                                </div>
                            </label>
                            <label class="scope-option" style="display: flex; align-items: flex-start; cursor: pointer; padding: 10px; background: #252525; border-radius: 6px; border: 2px solid transparent;">
                                <input type="radio" name="scope" value="video" id="scopeVideo" style="margin-top: 3px; margin-right: 10px;">
                                <div>
                                    <strong style="color: #fff;"><i class="fas fa-video"></i> Solo este video</strong>
                                    <div style="font-size: 12px; color: #888;">Solo aparecerá en este video</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Segundos antes (lead)</label>
                            <input type="number" name="lead_seconds" id="catLead" class="form-control" style="background: #252525; color: #fff; border-color: #333;" value="3" min="0" max="30">
                            <small style="color: #888;">Retrocede al iniciar</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Segundos después (lag)</label>
                            <input type="number" name="lag_seconds" id="catLag" class="form-control" style="background: #252525; color: #fff; border-color: #333;" value="3" min="0" max="30">
                            <small style="color: #888;">Avanza al terminar</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #333;">
                <button type="button" class="btn" style="background: #333; color: #fff;" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn" style="background: #00B7B5; color: #fff;" id="saveCategoryBtn">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>
