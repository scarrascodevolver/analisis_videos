                        <!-- Toolbar de anotaciones LATERAL COMPACTO -->
                        <div id="annotationToolbar" class="annotation-toolbar-vertical" style="display: none;">
                            {{-- Fila 1: Cerrar + Color + Duración --}}
                            <div class="toolbar-header-row">
                                <button id="closeAnnotationMode" class="toolbar-btn-small close-btn" title="Cerrar (ESC)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="color" id="annotationColor" value="#ff0000" class="toolbar-color-v" title="Color">
                                <select id="annotationDuration" class="toolbar-duration-v" title="Duración">
                                    <option value="2">2s</option>
                                    <option value="4" selected>4s</option>
                                    <option value="8">8s</option>
                                    <option value="permanent">Fija</option>
                                </select>
                            </div>

                            {{-- Herramientas básicas --}}
                            <div class="toolbar-section">
                                <button id="annotationArrow" class="toolbar-btn toolbar-btn-v active" data-tool="arrow" title="Flecha">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>Flecha</span>
                                </button>
                                <button id="annotationCircle" class="toolbar-btn toolbar-btn-v" data-tool="circle" title="Círculo">
                                    <i class="fas fa-circle"></i>
                                    <span>Círculo</span>
                                </button>
                                <button id="annotationLine" class="toolbar-btn toolbar-btn-v" data-tool="line" title="Línea">
                                    <i class="fas fa-minus"></i>
                                    <span>Línea</span>
                                </button>
                                <button id="annotationFreeDraw" class="toolbar-btn toolbar-btn-v" data-tool="free_draw" title="Dibujo libre">
                                    <i class="fas fa-pencil-alt"></i>
                                    <span>Dibujar</span>
                                </button>
                                <button id="annotationText" class="toolbar-btn toolbar-btn-v" data-tool="text" title="Texto">
                                    <i class="fas fa-font"></i>
                                    <span>Texto</span>
                                </button>
                            </div>

                            {{-- Herramientas adicionales --}}
                            <div class="toolbar-section">
                                <button id="annotationRectangle" class="toolbar-btn toolbar-btn-v" data-tool="rectangle" title="Rectángulo">
                                    <i class="fas fa-square"></i>
                                    <span>Rectángulo</span>
                                </button>
                                <button id="annotationArea" class="toolbar-btn toolbar-btn-v" data-tool="area" title="Área">
                                    <i class="fas fa-draw-polygon"></i>
                                    <span>Área</span>
                                </button>
                                <button id="annotationSpotlight" class="toolbar-btn toolbar-btn-v spotlight-btn" data-tool="spotlight" title="Foco">
                                    <i class="fas fa-bullseye"></i>
                                    <span>Foco</span>
                                </button>
                            </div>

                            {{-- Símbolos rápidos --}}
                            <div class="toolbar-section toolbar-symbols">
                                <button class="toolbar-btn toolbar-btn-v symbol-btn" data-symbol="tackle" title="Tackle">
                                    <i class="fas fa-bolt" style="color: #dc3545;"></i>
                                    <span>Tackle</span>
                                </button>
                                <button class="toolbar-btn toolbar-btn-v symbol-btn" data-symbol="ball" title="Balón">
                                    <i class="fas fa-football-ball" style="color: #8B4513;"></i>
                                    <span>Balón</span>
                                </button>
                                <button class="toolbar-btn toolbar-btn-v symbol-btn" data-symbol="x" title="Error">
                                    <i class="fas fa-times" style="color: #dc3545;"></i>
                                    <span>Error</span>
                                </button>
                                <button class="toolbar-btn toolbar-btn-v symbol-btn" data-symbol="check" title="OK">
                                    <i class="fas fa-check" style="color: #28a745;"></i>
                                    <span>OK</span>
                                </button>
                            </div>

                            {{-- Acciones --}}
                            <div class="toolbar-section" style="border-bottom: none;">
                                <button id="undoAnnotation" class="toolbar-btn-small" title="Deshacer" disabled><i class="fas fa-undo"></i></button>
                                <button id="redoAnnotation" class="toolbar-btn-small" title="Rehacer" disabled><i class="fas fa-redo"></i></button>
                                <button id="saveAnnotation" class="toolbar-btn-small save-btn" title="Guardar"><i class="fas fa-save"></i></button>
                                <button id="clearAnnotations" class="toolbar-btn-small clear-btn" title="Limpiar"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>

                        <!-- Tip flotante para herramienta de área -->
                        <div id="areaTip" class="area-tip" style="display: none;">
                            <i class="fas fa-lightbulb"></i>
                            Clic para agregar puntos. <strong>Doble clic</strong> o <strong>Enter</strong> para cerrar.
                        </div>


                        <!-- Delete Annotation Button (visible solo cuando hay anotación) -->
                        <button id="deleteAnnotationBtn" class="btn btn-sm btn-danger"
                                style="position: absolute; top: 10px; right: 10px; z-index: 20; display: none;"
                                title="Eliminar anotación visible">
                            <i class="fas fa-times-circle"></i> Eliminar Anotación
                        </button>
