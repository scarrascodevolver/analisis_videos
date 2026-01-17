/**
 * Video Player - Comments Module
 * Handles comment submissions, replies, and deletions via AJAX
 */

import { formatTime, getVideo, getConfig } from './utils.js';
import { createTimelineMarkers } from './timeline.js';
import { setNotificationsEnabled, hideAllNotifications } from './notifications.js';

// Reference to comments data (will be updated dynamically)
let commentsData = [];

/**
 * Initialize comments functionality
 * @param {Array} initialComments - Initial comments data
 */
export function initComments(initialComments = []) {
    commentsData = initialComments;

    initTimestampButtons();
    initToggleComments();
    initAddCommentButton();
    initDeleteComment();
    initReplySystem();
    initCommentForm();
}

/**
 * Get current comments data
 * @returns {Array}
 */
export function getCommentsDataRef() {
    return commentsData;
}

/**
 * Update comments data reference
 * @param {Array} newComments
 */
export function updateCommentsData(newComments) {
    commentsData = newComments;
}

/**
 * Initialize timestamp buttons click handlers
 */
function initTimestampButtons() {
    const video = getVideo();

    $(document).on('click', '.timestamp-btn', function () {
        const timestamp = $(this).data('timestamp');
        if (video) {
            video.currentTime = timestamp;
            if (video.paused) {
                video.play();
            }
        }
    });
}

/**
 * Initialize toggle comments section button
 * Oculta/muestra el panel de comentarios Y las notificaciones del timeline
 */
function initToggleComments() {
    // Track state for notifications (for players who don't have commentsSection)
    let notificationsVisible = true;

    $('#toggleCommentsBtn').on('click', function () {
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');

        // Check if commentsSection exists (analysts/coaches have it, players don't)
        const hasSidebar = commentsSection.length > 0;

        if (hasSidebar) {
            // Analysts/Coaches: toggle sidebar + notifications
            if (commentsSection.is(':visible')) {
                commentsSection.hide();
                videoSection.removeClass('col-lg-8').addClass('col-lg-12');
                toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                toggleText.text('Mostrar Comentarios');
                toggleBtn.removeClass('btn-rugby-outline').addClass('btn-rugby-light');
                $('#rugbyVideo').css('height', '600px');
                // Disable timeline notifications
                setNotificationsEnabled(false);
            } else {
                commentsSection.show();
                videoSection.removeClass('col-lg-12').addClass('col-lg-8');
                toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                toggleText.text('Ocultar Comentarios');
                toggleBtn.removeClass('btn-rugby-light').addClass('btn-rugby-outline');
                $('#rugbyVideo').css('height', '500px');
                // Enable timeline notifications
                setNotificationsEnabled(true);
            }
        } else {
            // Players: only toggle notifications (no sidebar)
            notificationsVisible = !notificationsVisible;

            if (notificationsVisible) {
                toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
                toggleText.text('Ocultar Comentarios');
                toggleBtn.removeClass('btn-rugby-light').addClass('btn-rugby-outline');
                setNotificationsEnabled(true);
            } else {
                toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
                toggleText.text('Mostrar Comentarios');
                toggleBtn.removeClass('btn-rugby-outline').addClass('btn-rugby-light');
                setNotificationsEnabled(false);
                hideAllNotifications();
            }
        }
    });
}

/**
 * Initialize "Add Comment Here" button
 */
function initAddCommentButton() {
    const video = getVideo();

    $('#addCommentBtn').on('click', function () {
        const currentTime = video ? video.currentTime : 0;

        // Show comments section if hidden
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');

        if (!commentsSection.is(':visible')) {
            commentsSection.show();
            videoSection.removeClass('col-lg-12').addClass('col-lg-8');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleText.text('Ocultar Comentarios');
            toggleBtn.removeClass('btn-rugby-light').addClass('btn-rugby-outline');
            $('#rugbyVideo').css('height', '500px');
        }

        // Scroll to comments section
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('#commentsSection').offset().top - 20
            }, 800);

            setTimeout(() => {
                $('#timestampDisplay').text(formatTime(currentTime));
                $('input[name="timestamp_seconds"]').val(Math.floor(currentTime));
                $('textarea[name="comment"]').focus();

                // Highlight form briefly
                const commentForm = $('#commentForm');
                commentForm.addClass('border border-success').css('background-color', '#f8fff9');
                setTimeout(() => {
                    commentForm.removeClass('border border-success').css('background-color', '');
                }, 2000);

                if (typeof toastr !== 'undefined') {
                    toastr.info(`Timestamp establecido en ${formatTime(currentTime)}`, 'Listo para comentar');
                }
            }, 900);
        }, 100);
    });
}

/**
 * Initialize delete comment functionality
 */
function initDeleteComment() {
    const video = getVideo();

    $(document).on('click', '.delete-comment-btn', function () {
        const commentId = $(this).data('comment-id');
        const $commentElement = $(this).closest('.comment-item, .reply');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: `/comments/${commentId}`,
            type: 'DELETE',
            success: function (response) {
                if (response.success) {
                    $commentElement.fadeOut(300, function () {
                        $(this).remove();

                        // Update comment counter
                        const currentCount = parseInt($('.card-title:contains("Comentarios")').text().match(/\((\d+)\)/)[1]);
                        const newCount = currentCount - 1;
                        $('.card-title:contains("Comentarios")').html(`<i class="fas fa-comments"></i> Comentarios (${newCount})`);
                    });

                    // Remove from commentsData array
                    const commentIndex = commentsData.findIndex(comment => comment.id == commentId);
                    if (commentIndex !== -1) {
                        commentsData.splice(commentIndex, 1);
                    }

                    // Remove visible notification if exists
                    const notification = document.getElementById(`notification-${commentId}`);
                    if (notification && notification.parentNode) {
                        notification.style.opacity = '0';
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.parentNode.removeChild(notification);
                            }
                        }, 300);
                    }

                    // Recreate timeline without deleted marker
                    if (video && video.duration && !isNaN(video.duration)) {
                        createTimelineMarkers();
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success('Comentario eliminado exitosamente');
                    }
                }
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('No tienes permisos para eliminar este comentario');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error al eliminar el comentario');
                    }
                }
            }
        });
    });
}

/**
 * Initialize reply system
 */
function initReplySystem() {
    const config = getConfig();

    // Show/hide reply form
    $(document).on('click', '.reply-btn', function () {
        const commentId = $(this).data('comment-id');
        const replyForm = $(`#replyForm${commentId}`);

        // Hide other open reply forms
        $('.reply-form').not(replyForm).slideUp();

        // Toggle current form
        replyForm.slideToggle(300, function () {
            if (replyForm.is(':visible')) {
                const textarea = replyForm.find('textarea');
                textarea.focus();

                $('html, body').animate({
                    scrollTop: textarea.offset().top - 100
                }, 500);
            }
        });
    });

    // Submit reply via AJAX
    $(document).on('submit', '.reply-form-submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const commentId = form.data('comment-id');
        const videoId = form.data('video-id');
        const textarea = form.find('textarea[name="reply_comment"]');
        const replyText = textarea.val().trim();
        const submitBtn = form.find('button[type="submit"]');

        if (submitBtn.prop('disabled')) {
            return;
        }

        if (!replyText) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Por favor escribe una respuesta');
            }
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: `/videos/${videoId}/comments`,
            type: 'POST',
            data: {
                comment: replyText,
                parent_id: commentId,
                timestamp_seconds: 0,
                category: 'general',
                priority: 'media'
            },
            success: function (response) {
                if (response.success) {
                    textarea.val('');
                    form.closest('.reply-form').slideUp();

                    const userName = config.user.name;
                    const userRole = config.user.role;
                    const badgeClass = userRole === 'analista' ? 'primary' : (userRole === 'entrenador' ? 'success' : 'info');
                    const roleLabel = userRole.charAt(0).toUpperCase() + userRole.slice(1);

                    const replyHtml = `
                        <div class="reply comment-item border-left border-primary pl-3 mb-2" data-reply-id="${response.comment.id}" style="display:none;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1">${replyText}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> ${userName}
                                        <span class="badge badge-sm badge-${badgeClass}">${roleLabel}</span>
                                        - Hace unos segundos
                                    </small>
                                    <button class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                                            data-comment-id="${response.comment.id}"
                                            title="Responder a esta respuesta">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                                        data-comment-id="${response.comment.id}"
                                        title="Eliminar respuesta">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="reply-form mt-2" id="replyForm${response.comment.id}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="${response.comment.id}" data-video-id="${videoId}">
                                    <input type="hidden" name="_token" value="${config.csrfToken}">
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>
                            <div class="replies ml-3 mt-2"></div>
                        </div>
                    `;

                    let repliesSection = form.closest('.reply-form').next('.replies');
                    if (repliesSection.length === 0) {
                        repliesSection = form.closest('.comment-item').find('> .replies').first();
                    }
                    if (repliesSection.length === 0) {
                        const isNestedReply = form.closest('.reply').length > 0;
                        const marginClass = isNestedReply ? 'ml-3' : 'ml-4';
                        form.closest('.reply-form').after(`<div class="replies ${marginClass} mt-3"></div>`);
                        repliesSection = form.closest('.reply-form').next('.replies');
                    }

                    repliesSection.append(replyHtml);
                    repliesSection.find('.reply:last').slideDown(300);

                    // Update comment counter
                    const commentCountElement = $('.card-title:contains("Comentarios")');
                    const currentCountMatch = commentCountElement.text().match(/\((\d+)\)/);
                    if (currentCountMatch) {
                        const newCount = parseInt(currentCountMatch[1]) + 1;
                        commentCountElement.html(`<i class="fas fa-comments"></i> Comentarios (${newCount})`);
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success('Respuesta agregada exitosamente');
                    }
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Error de validacion: ';
                    Object.values(errors).forEach(error => {
                        errorMsg += error[0] + ' ';
                    });
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error al enviar la respuesta. Por favor intenta de nuevo.');
                    }
                }
            },
            complete: function () {
                submitBtn.prop('disabled', false).html('<i class="fas fa-reply"></i> Responder');
            }
        });
    });
}

/**
 * Initialize main comment form submission
 */
function initCommentForm() {
    const config = getConfig();
    const video = getVideo();

    $('#commentForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const videoId = form.data('video-id');
        const submitBtn = form.find('button[type="submit"]');
        const textarea = form.find('textarea[name="comment"]');
        const timestampInput = form.find('#timestamp_seconds');
        const categorySelect = form.find('select[name="category"]');
        const prioritySelect = form.find('select[name="priority"]');

        const commentText = textarea.val().trim();
        const timestampSeconds = parseInt(timestampInput.val()) || 0;
        const category = categorySelect.val();
        const priority = prioritySelect.val();

        if (!commentText) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Por favor escribe un comentario');
            }
            return;
        }

        if (submitBtn.prop('disabled')) {
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: `/videos/${videoId}/comments`,
            type: 'POST',
            data: {
                comment: commentText,
                timestamp_seconds: timestampSeconds,
                category: category,
                priority: priority
            },
            success: function (response) {
                if (response.success) {
                    // Clear form
                    textarea.val('');
                    timestampInput.val(0);
                    $('#timestampDisplay').text('00:00');
                    categorySelect.val('tecnico');
                    prioritySelect.val('media');

                    const userName = config.user.name;
                    const userRole = config.user.role;
                    const badgeClass = userRole === 'analista' ? 'primary' : (userRole === 'entrenador' ? 'success' : 'info');
                    const roleLabel = userRole.charAt(0).toUpperCase() + userRole.slice(1);

                    const formattedTimestamp = formatTime(timestampSeconds);

                    const categoryBadgeClass = category === 'tecnico' ? 'info' :
                        (category === 'tactico' ? 'warning' :
                            (category === 'fisico' ? 'success' : 'purple'));

                    const priorityBadgeClass = priority === 'critica' ? 'danger' :
                        (priority === 'alta' ? 'warning' :
                            (priority === 'media' ? 'info' : 'secondary'));

                    const commentHtml = `
                        <div class="comment-item border-bottom p-2" data-timestamp="${timestampSeconds}" data-comment-id="${response.comment.id}" style="display:none;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <button class="btn btn-sm btn-primary timestamp-btn mr-2" data-timestamp="${timestampSeconds}">
                                            ${formattedTimestamp}
                                        </button>
                                        <span class="badge badge-${categoryBadgeClass}">
                                            ${category.charAt(0).toUpperCase() + category.slice(1)}
                                        </span>
                                        <span class="badge badge-${priorityBadgeClass} ml-1">
                                            ${priority.charAt(0).toUpperCase() + priority.slice(1)}
                                        </span>
                                    </div>
                                    <p class="mb-1">${commentText}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> ${userName}
                                        <span class="badge badge-sm badge-${badgeClass}">${roleLabel}</span>
                                        - Hace unos segundos
                                    </small>
                                    <button class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                                            data-comment-id="${response.comment.id}" title="Responder">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                                        data-comment-id="${response.comment.id}" title="Eliminar comentario">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="reply-form mt-2" id="replyForm${response.comment.id}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="${response.comment.id}" data-video-id="${videoId}">
                                    <input type="hidden" name="_token" value="${config.csrfToken}">
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>
                            <div class="replies ml-4 mt-3"></div>
                        </div>
                    `;

                    // Add to comments list
                    const commentsList = $('.card-body.p-0[style*="max-height: 400px"]');
                    commentsList.prepend(commentHtml);
                    commentsList.find('.comment-item:first').slideDown(300);

                    // Update counter
                    const commentCountElement = $('.card-title:contains("Comentarios")');
                    const currentCountMatch = commentCountElement.text().match(/\((\d+)\)/);
                    if (currentCountMatch) {
                        const newCount = parseInt(currentCountMatch[1]) + 1;
                        commentCountElement.html(`<i class="fas fa-list"></i> Comentarios (${newCount})`);
                    }

                    // Add to commentsData for timeline/notifications
                    commentsData.push({
                        id: response.comment.id,
                        timestamp_seconds: timestampSeconds,
                        comment: commentText,
                        category: category,
                        priority: priority,
                        user: { name: userName }
                    });

                    // Recreate timeline with new marker
                    if (video && video.duration && !isNaN(video.duration)) {
                        createTimelineMarkers();
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success('Comentario agregado exitosamente');
                    }
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Error de validacion: ';
                    Object.values(errors).forEach(error => {
                        errorMsg += error[0] + ' ';
                    });
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error al enviar el comentario. Por favor intenta de nuevo.');
                    }
                }
            },
            complete: function () {
                submitBtn.prop('disabled', false).html('<i class="fas fa-comment"></i> Agregar');
            }
        });
    });
}
