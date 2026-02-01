<!-- Modal de Visualizaciones -->
@if(in_array(auth()->user()->role, ['analista', 'entrenador', 'jugador']))
<div class="modal fade" id="statsModal" tabindex="-1" role="dialog" aria-labelledby="statsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--color-primary, #005461) 0%, var(--color-accent, #4B9DA9) 100%); color: white;">
                <h5 class="modal-title" id="statsModalLabel">
                    <i class="fas fa-eye"></i> Visualizaciones del Video
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-eye"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Visualizaciones</span>
                                <span class="info-box-number" id="modalTotalViews">{{ $video->view_count }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Usuarios Únicos</span>
                                <span class="info-box-number" id="modalUniqueViewers">{{ $video->unique_viewers }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3"><i class="fas fa-list"></i> Detalle por Usuario</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm" id="statsTable">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th class="text-center">Vistas</th>
                                <th>Última Visualización</th>
                            </tr>
                        </thead>
                        <tbody id="statsTableBody">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando visualizaciones...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-rugby" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endif
