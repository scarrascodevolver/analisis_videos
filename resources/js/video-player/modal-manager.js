/**
 * Modal Manager - Gestión de modales de categorías y clips
 *
 * Maneja la apertura, edición, guardado y eliminación de categorías y clips
 */

import { getConfig, getVideo } from './utils.js';

/**
 * Initialize modal management functionality
 */
export function initModalManager() {
    const config = getConfig();

    // Only initialize if user has permissions
    if (!config.user.canCreateClips) {
        console.log('Modal manager not initialized (user lacks permissions)');
        return;
    }

    initCategoryModals();
    initEditClipModal();

    console.log('Modal manager initialized');
}

/**
 * Initialize category modal handlers
 */
function initCategoryModals() {
    const config = getConfig();
    const saveBtn = document.getElementById('saveCategoryBtn');
    const categoriesListModal = document.getElementById('categoriesListModal');

    // Expose globally for onclick handlers in HTML
    window.openCategoryModal = openCategoryModal;
    window.deleteCategory = deleteCategory;

    // Save category (create or edit)
    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            const catId = document.getElementById('catId').value;
            const name = document.getElementById('catName').value.trim();
            const color = document.getElementById('catColor').value;
            const hotkey = document.getElementById('catHotkey').value.trim();
            const lead_seconds = parseInt(document.getElementById('catLead').value) || 3;
            const lag_seconds = parseInt(document.getElementById('catLag').value) || 3;

            if (!name) {
                alert('El nombre es requerido');
                return;
            }

            const data = { name, color, hotkey, lead_seconds, lag_seconds };
            const isEdit = !!catId;
            const url = isEdit
                ? `/admin/clip-categories/${catId}`
                : config.routes.createCategory;
            const method = isEdit ? 'PUT' : 'POST';

            try {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    $('#categoryModal').modal('hide');
                    // Reload categories in the player
                    if (typeof window.loadCategories === 'function') {
                        window.loadCategories();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(result.message || 'Error al guardar categoría');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al guardar categoría');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar';
            }
        });
    }

    // Load categories list when manage modal opens
    $('#manageCategoriesModal').on('show.bs.modal', async function() {
        try {
            const response = await fetch(config.routes.clipCategories);
            const data = await response.json();
            const categories = data.categories || data;

            if (!Array.isArray(categories) || categories.length === 0) {
                categoriesListModal.innerHTML = '<div class="text-center py-3" style="color: #888;">No hay categorías creadas</div>';
                return;
            }

            categoriesListModal.innerHTML = categories.map(cat => `
                <div class="d-flex justify-content-between align-items-center p-2 mb-2" style="background: #252525; border-radius: 5px;" id="category-row-${cat.id}">
                    <div class="d-flex align-items-center">
                        <span class="mr-3" style="width: 30px; height: 30px; background: ${cat.color}; border-radius: 5px;"></span>
                        <div>
                            <strong>${escapeHtml(cat.name)}</strong>
                            ${cat.hotkey ? `<span class="badge ml-2" style="background: #333;">[${cat.hotkey.toUpperCase()}]</span>` : ''}
                            <br>
                            <small style="color: #888;">Lead: ${cat.lead_seconds}s | Lag: ${cat.lag_seconds}s</small>
                        </div>
                    </div>
                    <div>
                        <button type="button" onclick='openCategoryModal(${JSON.stringify(cat)})' class="btn btn-sm" style="background: #005461; color: #fff;" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" onclick='deleteCategory(${cat.id}, "${escapeHtml(cat.name)}")' class="btn btn-sm btn-danger ml-1" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('Error:', error);
            categoriesListModal.innerHTML = '<div class="text-center py-3 text-danger">Error al cargar categorías</div>';
        }
    });
}

/**
 * Open category modal (create or edit)
 */
function openCategoryModal(category = null) {
    const modalTitle = document.getElementById('categoryModalTitle');
    const catId = document.getElementById('catId');
    const catName = document.getElementById('catName');
    const catColor = document.getElementById('catColor');
    const catHotkey = document.getElementById('catHotkey');
    const catLead = document.getElementById('catLead');
    const catLag = document.getElementById('catLag');

    if (category) {
        // Edit mode
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Categoría';
        catId.value = category.id;
        catName.value = category.name;
        catColor.value = category.color;
        catHotkey.value = category.hotkey || '';
        catLead.value = category.lead_seconds;
        catLag.value = category.lag_seconds;
    } else {
        // Create mode
        modalTitle.innerHTML = '<i class="fas fa-plus"></i> Crear Categoría';
        catId.value = '';
        catName.value = '';
        catColor.value = '#005461';
        catHotkey.value = '';
        catLead.value = '3';
        catLag.value = '3';
    }

    // Close manage modal if open
    $('#manageCategoriesModal').modal('hide');

    // Open category modal
    $('#categoryModal').modal('show');
}

/**
 * Delete category
 */
async function deleteCategory(categoryId, categoryName) {
    const config = getConfig();

    if (!confirm(`¿Eliminar la categoría "${categoryName}"?\n\nLos clips existentes de esta categoría NO se eliminarán.`)) {
        return;
    }

    try {
        const response = await fetch(`/admin/clip-categories/${categoryId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Remove row from modal
            const row = document.getElementById(`category-row-${categoryId}`);
            if (row) {
                row.remove();
            }

            // Reload categories in player
            if (typeof window.loadCategories === 'function') {
                window.loadCategories();
            }
        } else {
            alert(result.message || 'Error al eliminar categoría');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar categoría');
    }
}

/**
 * Initialize edit clip modal
 */
function initEditClipModal() {
    const video = getVideo();
    const editClipModal = document.getElementById('editClipModal');
    const editClipId = document.getElementById('editClipId');
    const editClipStart = document.getElementById('editClipStart');
    const editClipEnd = document.getElementById('editClipEnd');
    const editClipTitle = document.getElementById('editClipTitle');
    const editClipNotes = document.getElementById('editClipNotes');
    const editClipStartFormatted = document.getElementById('editClipStartFormatted');
    const editClipEndFormatted = document.getElementById('editClipEndFormatted');

    if (!editClipModal) return;

    // Format seconds to MM:SS
    function formatTimeEdit(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    // Update formatted time displays
    function updateFormattedTimes() {
        const start = parseFloat(editClipStart.value) || 0;
        const end = parseFloat(editClipEnd.value) || 0;
        editClipStartFormatted.textContent = formatTimeEdit(start);
        editClipEndFormatted.textContent = formatTimeEdit(end);
    }

    // Expose globally for onclick handlers
    window.openEditClipModal = function(clipId, start, end, title, notes, categoryId) {
        editClipId.value = clipId;
        editClipStart.value = start.toFixed(1);
        editClipEnd.value = end.toFixed(1);
        editClipTitle.value = title || '';
        editClipNotes.value = notes || '';
        updateFormattedTimes();
        $('#editClipModal').modal('show');
    };

    // Input change handlers
    if (editClipStart) {
        editClipStart.addEventListener('input', updateFormattedTimes);
    }
    if (editClipEnd) {
        editClipEnd.addEventListener('input', updateFormattedTimes);
    }

    // Use current time buttons
    document.getElementById('useCurrentStartBtn')?.addEventListener('click', function() {
        if (video) {
            editClipStart.value = video.currentTime.toFixed(1);
            updateFormattedTimes();
        }
    });

    document.getElementById('useCurrentEndBtn')?.addEventListener('click', function() {
        if (video) {
            editClipEnd.value = video.currentTime.toFixed(1);
            updateFormattedTimes();
        }
    });

    // Save button
    document.getElementById('saveEditClipBtn')?.addEventListener('click', async function() {
        const config = getConfig();
        const clipId = editClipId.value;
        const start = parseFloat(editClipStart.value);
        const end = parseFloat(editClipEnd.value);
        const title = editClipTitle.value.trim();
        const notes = editClipNotes.value.trim();

        if (isNaN(start) || isNaN(end) || start >= end) {
            alert('Tiempos inválidos. El inicio debe ser menor que el fin.');
            return;
        }

        try {
            const response = await fetch(`/videos/${config.videoId}/clips/${clipId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_time: start,
                    end_time: end,
                    title: title,
                    notes: notes
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                $('#editClipModal').modal('hide');

                // Reload clips if function exists
                if (typeof window.loadClips === 'function') {
                    window.loadClips();
                } else if (typeof window.loadSidebarClips === 'function') {
                    window.loadSidebarClips();
                } else {
                    location.reload();
                }
            } else {
                alert(result.message || 'Error al guardar clip');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al guardar clip');
        }
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
