/**
 * Video Player - Main JavaScript
 * Extracted from resources/views/videos/show.blade.php
 * 
 * Requires VideoPlayer.config to be defined before loading this script.
 */

$(document).ready(function() {
    const video = document.getElementById('rugbyVideo');
    const timestampInput = document.getElementById('timestamp_seconds');
    const timestampDisplay = document.getElementById('timestampDisplay');

    // Datos de comentarios para el timeline y notificaciones
    const commentsData = VideoPlayer.config.comments;

    // ========== VIDEO VIEW TRACKING ==========
    let currentViewId = null;
    let trackingActive = false;
    let durationUpdateInterval = null;
    let viewTracked = false; // Flag para evitar contar múltiples veces

    // Track view when user watches at least 20 seconds + auto-complete at 90%
    video.addEventListener('timeupdate', function() {
        // 1. Contar vista después de 20 segundos de reproducción
        if (!viewTracked && video.currentTime >= 20) {
            trackView();
            viewTracked = true;
        }

        // 2. Auto-completar al 90% del video
        if (currentViewId && video.duration > 0) {
            const percentWatched = (video.currentTime / video.duration) * 100;
            if (percentWatched >= 90) {
                markVideoCompleted();
            }
        }
    });

    function trackView() {
        fetch(VideoPlayer.config.routes.trackView, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': VideoPlayer.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.cooldown) {
                currentViewId = data.view_id;
                trackingActive = true;

                // Update view count in UI
                updateViewCount(data.total_views, data.unique_viewers);

                // Start duration tracking
                startDurationTracking();

                console.log('View tracked successfully');
            } else if (data.cooldown) {
                console.log('View within cooldown period');
            }
        })
        .catch(error => console.error('Error tracking view:', error));
    }

    function startDurationTracking() {
        // Update duration every 10 seconds
        if (durationUpdateInterval) {
            clearInterval(durationUpdateInterval);
        }

        durationUpdateInterval = setInterval(() => {
            if (currentViewId && !video.paused) {
                updateWatchDuration();
            }
        }, 10000); // 10 seconds
    }

    function updateWatchDuration() {
        if (!currentViewId) return;

        fetch(VideoPlayer.config.routes.updateDuration, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': VideoPlayer.config.csrfToken
            },
            body: JSON.stringify({
                view_id: currentViewId,
                duration: Math.floor(video.currentTime)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Duration updated');
            }
        })
        .catch(error => console.error('Error updating duration:', error));
    }

    function markVideoCompleted() {
        if (!currentViewId) return;

        fetch(VideoPlayer.config.routes.markCompleted, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': VideoPlayer.config.csrfToken
            },
            body: JSON.stringify({
                view_id: currentViewId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Video marked as completed');
                // Only mark once
                currentViewId = null;
            }
        })
        .catch(error => console.error('Error marking completed:', error));
    }

    function updateViewCount(totalViews, uniqueViewers) {
        const viewCountElement = document.getElementById('viewCount');
        const uniqueViewersElement = document.getElementById('uniqueViewers');

        if (viewCountElement) {
            viewCountElement.textContent = totalViews;
        }
        if (uniqueViewersElement) {
            uniqueViewersElement.textContent = uniqueViewers;
        }
    }
    // ========== END VIDEO VIEW TRACKING ==========


    // Basic time formatting function
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Update timestamp input to current video time
    video.addEventListener('timeupdate', function() {
        if (document.activeElement !== timestampInput) {
            timestampInput.value = Math.floor(video.currentTime);
            timestampDisplay.textContent = formatTime(Math.floor(video.currentTime));
        }
    });

    // Use current time button
    document.getElementById('useCurrentTime').addEventListener('click', function() {
        const currentSeconds = Math.floor(video.currentTime || 0);
        timestampInput.value = currentSeconds;
        timestampDisplay.textContent = formatTime(currentSeconds);
    });

    // Update timestamp display when input changes
    timestampInput.addEventListener('input', function() {
        const seconds = parseInt(this.value) || 0;
        timestampDisplay.textContent = formatTime(seconds);
    });

    // Comment timestamp buttons
    $('.timestamp-btn').on('click', function() {
        const timestamp = $(this).data('timestamp');
        video.currentTime = timestamp;
        if (video.paused) {
            video.play();
        }
    });

    // Toggle comments section
    $('#toggleCommentsBtn').on('click', function() {
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');
        
        if (commentsSection.is(':visible')) {
            commentsSection.hide();
            videoSection.removeClass('col-lg-8').addClass('col-lg-12');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            toggleText.text('Mostrar Comentarios');
            toggleBtn.removeClass('btn-warning').addClass('btn-success');
            $('#rugbyVideo').css('height', '600px');
        } else {
            commentsSection.show();
            videoSection.removeClass('col-lg-12').addClass('col-lg-8');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleText.text('Ocultar Comentarios');
            toggleBtn.removeClass('btn-success').addClass('btn-warning');
            $('#rugbyVideo').css('height', '500px');
        }
    });

    // Timeline de comentarios
    function createTimelineMarkers() {
        const timeline = document.getElementById('timelineMarkers');
        const videoDuration = video.duration;
        
        if (!videoDuration || videoDuration === 0) {
            return;
        }

        
        // Clear existing content
        timeline.innerHTML = '';
        
        // Create progress bar
        const progressContainer = document.createElement('div');
        progressContainer.style.cssText = `
            position: relative;
            width: 100%;
            height: 100%;
            background: #dee2e6;
            border-radius: 5px;
            cursor: pointer;
        `;
        
        // Progress bar
        const progressBar = document.createElement('div');
        progressBar.id = 'progressBar';
        progressBar.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: var(--color-primary, #005461);
            border-radius: 5px;
            transition: width 0.1s ease;
        `;
        
        // Progress indicator
        const progressIndicator = document.createElement('div');
        progressIndicator.id = 'progressIndicator';
        progressIndicator.style.cssText = `
            position: absolute;
            top: -5px;
            left: 0%;
            width: 4px;
            height: 50px;
            background: var(--color-primary, #005461);
            border-radius: 2px;
            transition: left 0.1s ease;
            transform: translateX(-50%);
        `;
        
        progressContainer.appendChild(progressBar);
        progressContainer.appendChild(progressIndicator);
        
        // Add comment markers (usar commentsData actualizable)
        commentsData.forEach(comment => {
            const position = (comment.timestamp_seconds / videoDuration) * 100;
            
            const marker = document.createElement('div');
            marker.className = 'comment-marker';
            marker.setAttribute('data-timestamp', comment.timestamp_seconds);
            marker.setAttribute('data-comment', comment.comment);
            marker.style.cssText = `
                position: absolute;
                top: -5px;
                left: ${position}%;
                width: 8px;
                height: 50px;
                background: var(--color-accent, #4B9DA9);
                border: 2px solid #fff;
                border-radius: 4px;
                cursor: pointer;
                transform: translateX(-50%);
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            `;
            
            // Tooltip on hover
            marker.title = `${formatTime(comment.timestamp_seconds)}: ${comment.comment.substring(0, 50)}...`;
            
            // Click to seek
            marker.addEventListener('click', function(e) {
                e.stopPropagation();
                video.currentTime = comment.timestamp_seconds;
                if (video.paused) {
                    video.play();
                }
            });
            
            progressContainer.appendChild(marker);
        });
        
        // Timeline click to seek
        progressContainer.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            const newTime = percentage * videoDuration;
            
            video.currentTime = newTime;
        });
        
        timeline.appendChild(progressContainer);
    }
    
    // Update progress indicator and bar
    function updateProgressIndicator() {
        const progressIndicator = document.getElementById('progressIndicator');
        const progressBar = document.getElementById('progressBar');
        
        if (video.duration) {
            const percentage = (video.currentTime / video.duration) * 100;
            
            if (progressIndicator) {
                progressIndicator.style.left = percentage + '%';
            }
            
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
        }
    }

    // Initialize timeline when video metadata loads
    video.addEventListener('loadedmetadata', function() {
        if (video.duration && !isNaN(video.duration)) {
            createTimelineMarkers();
        }

        // Check for timestamp parameter in URL (from session expiry return)
        const urlParams = new URLSearchParams(window.location.search);
        const startTime = urlParams.get('t');
        if (startTime && !isNaN(startTime)) {
            const timeInSeconds = parseInt(startTime);
            if (timeInSeconds > 0 && timeInSeconds < video.duration) {
                video.currentTime = timeInSeconds;

                // Show notification that video was restored
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Video restaurado desde ${formatTime(timeInSeconds)}`, 'Sesión Recuperada');
                }

                // Clean URL parameter
                const newUrl = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    });

    // Update timeline progress
    video.addEventListener('timeupdate', function() {
        updateProgressIndicator();
        checkAndShowCommentNotifications();
        checkAndShowAnnotations(); // Nueva función para anotaciones
    });

    // 🔧 FIX: Actualizar anotaciones cuando el usuario mueve la línea de tiempo (video pausado)
    video.addEventListener('seeked', function() {
        checkAndShowAnnotations();
        checkAndShowCommentNotifications();
        updateProgressIndicator();
    });

    // Force timeline creation if video is already loaded
    if (video.readyState >= 2) {
        createTimelineMarkers();
    }
    
    // Also try after a delay
    setTimeout(function() {
        if (video.duration && !isNaN(video.duration) && !document.getElementById('progressBar')) {
            createTimelineMarkers();
        }
    }, 1000);

    // Comment notifications system
    let lastCheckedTime = -1;
    let activeCommentIds = new Set(); // Track currently visible notifications

    function checkAndShowCommentNotifications() {
        const currentTime = Math.floor(video.currentTime);

        // Only check once per second
        if (currentTime === lastCheckedTime) return;
        lastCheckedTime = currentTime;

        // Find comments at current time (exact match or ±1 second)
        const currentComments = commentsData.filter(comment =>
            Math.abs(comment.timestamp_seconds - currentTime) <= 1
        );

        // Get IDs of comments that should be visible now
        const currentCommentIds = new Set(currentComments.map(c => c.id));

        // Only update notifications if the set of comments has changed
        if (!setsAreEqual(activeCommentIds, currentCommentIds)) {
            // Remove notifications that should no longer be visible
            activeCommentIds.forEach(commentId => {
                if (!currentCommentIds.has(commentId)) {
                    const notification = document.getElementById(`notification-${commentId}`);
                    if (notification && notification.parentNode) {
                        notification.style.opacity = '0';
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.parentNode.removeChild(notification);
                            }
                        }, 300);
                    }
                }
            });

            // Show new notifications
            currentComments.forEach(comment => {
                if (!activeCommentIds.has(comment.id)) {
                    showCommentNotification(comment);
                }
            });

            // Update active comment IDs
            activeCommentIds = currentCommentIds;
        }
    }

    // Helper function to compare sets
    function setsAreEqual(set1, set2) {
        if (set1.size !== set2.size) return false;
        for (let item of set1) {
            if (!set2.has(item)) return false;
        }
        return true;
    }
    
    function showCommentNotification(comment) {
        const notificationArea = document.getElementById('commentNotifications');
        
        if (!video.duration) return;
        
        // Calculate position relative to the notifications area
        const notificationAreaWidth = notificationArea.offsetWidth;
        const relativePosition = (comment.timestamp_seconds / video.duration) * notificationAreaWidth;

        // Create notification element
        const notification = document.createElement('div');
        notification.id = `notification-${comment.id}`;
        notification.className = 'comment-notification';

        // Category colors
        const categoryColors = {
            'tecnico': 'info',
            'tactico': 'warning',
            'fisico': 'success',
            'mental': 'purple',
            'general': 'secondary'
        };

        const priorityColors = {
            'critica': 'danger',
            'alta': 'warning',
            'media': 'info',
            'baja': 'secondary'
        };

        // Responsive width and positioning for mobile
        const isMobileView = window.innerWidth <= 768;
        const notificationWidth = isMobileView ? 280 : 320;
        const minWidth = isMobileView ? 200 : 250;
        const padding = isMobileView ? '8px 12px' : '12px 15px';

        // Smart positioning to keep notification within bounds
        let leftPosition = relativePosition;
        let transformX = '-50%';

        if (isMobileView) {
            const halfWidth = notificationWidth / 2;
            const margin = 10; // minimum margin from edges

            if (relativePosition < halfWidth + margin) {
                // Too close to left edge - align to left
                leftPosition = margin;
                transformX = '0%';
            } else if (relativePosition > notificationAreaWidth - halfWidth - margin) {
                // Too close to right edge - align to right
                leftPosition = notificationAreaWidth - margin;
                transformX = '-100%';
            }
        }

        notification.style.cssText = `
            position: absolute;
            top: 10px;
            left: ${leftPosition}px;
            transform: translateX(${transformX});
            max-width: ${notificationWidth}px;
            min-width: ${minWidth}px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid var(--color-accent, #4B9DA9);
            border-radius: 12px;
            padding: ${padding};
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadeIn 0.5s ease;
            pointer-events: auto;
            backdrop-filter: blur(3px);
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-${categoryColors[comment.category] || 'secondary'} mr-2" style="font-size: 10px;">
                            ${comment.category.charAt(0).toUpperCase() + comment.category.slice(1)}
                        </span>
                        <span class="badge badge-${priorityColors[comment.priority] || 'secondary'}" style="font-size: 10px;">
                            ${comment.priority.charAt(0).toUpperCase() + comment.priority.slice(1)}
                        </span>
                    </div>
                    <p class="mb-2 text-dark" style="font-size: 13px; line-height: 1.3; font-weight: 500;">
                        ${comment.comment.length > 80 ? comment.comment.substring(0, 80) + '...' : comment.comment}
                    </p>
                    <small class="text-muted" style="font-size: 11px;">
                        <i class="fas fa-user"></i> ${comment.user.name} 
                        <span class="ml-2"><i class="fas fa-clock"></i> ${formatTime(comment.timestamp_seconds)}</span>
                    </small>
                </div>
                <button class="btn btn-sm btn-link text-muted p-1 ml-2" 
                        style="font-size: 12px; opacity: 0.8;" 
                        onclick="closeNotification(${comment.id})"
                        title="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    function hideAllNotifications() {
        const notificationArea = document.getElementById('commentNotifications');
        while (notificationArea.firstChild) {
            notificationArea.removeChild(notificationArea.firstChild);
        }
        // Clear tracking when hiding all notifications
        activeCommentIds.clear();
    }
    
    // Close notification function (global)
    window.closeNotification = function(commentId) {
        const notification = document.getElementById(`notification-${commentId}`);
        if (notification && notification.parentNode) {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
            // Remove from tracking
            activeCommentIds.delete(commentId);
        }
    };

    // PSEUDO-FULLSCREEN SYSTEM FOR MOBILE
    let isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    let isPseudoFullscreen = false;


    // Show mobile fullscreen button only on mobile devices
    if (isMobile) {
        document.getElementById('mobileFullscreenBtn').style.display = 'inline-block';

        // Disable native fullscreen on mobile
        video.addEventListener('webkitbeginfullscreen', function(e) {
            e.preventDefault();
            enterPseudoFullscreen();
        });

        // Hide native fullscreen button
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
    }

    // Mobile fullscreen button click
    document.getElementById('mobileFullscreenBtn').addEventListener('click', function() {
        if (isPseudoFullscreen) {
            exitPseudoFullscreen();
        } else {
            enterPseudoFullscreen();
        }
    });

    function enterPseudoFullscreen() {
        const videoSection = document.getElementById('videoSection');
        const videoContainer = videoSection.querySelector('.video-container');

        isPseudoFullscreen = true;

        // Create pseudo-fullscreen overlay
        const overlay = document.createElement('div');
        overlay.id = 'pseudoFullscreenOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: black;
            z-index: 9999;
            display: flex;
            flex-direction: column;
        `;

        // Clone video container
        const clonedContainer = videoContainer.cloneNode(true);
        clonedContainer.style.cssText = `
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        // Update video size
        const clonedVideo = clonedContainer.querySelector('#rugbyVideo');
        clonedVideo.style.cssText = `
            width: 100%;
            height: 100%;
            max-height: calc(100vh - 200px);
            object-fit: contain;
        `;

        // Add exit button
        const exitBtn = document.createElement('button');
        exitBtn.innerHTML = '<i class="fas fa-times"></i>';
        exitBtn.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            font-size: 24px;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10000;
        `;
        exitBtn.onclick = exitPseudoFullscreen;

        // Add comments area at bottom
        const commentsArea = document.createElement('div');
        commentsArea.id = 'pseudoFullscreenComments';
        commentsArea.style.cssText = `
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px;
            overflow-y: auto;
            border-top: 2px solid var(--color-accent, #4B9DA9);
        `;
        commentsArea.innerHTML = '<h6><i class="fas fa-comments"></i> Comentarios en tiempo real</h6>';

        overlay.appendChild(exitBtn);
        overlay.appendChild(clonedContainer);
        overlay.appendChild(commentsArea);
        document.body.appendChild(overlay);

        // Hide original video
        videoContainer.style.display = 'none';

        // Update button icon
        document.getElementById('mobileFullscreenBtn').innerHTML = '<i class="fas fa-compress"></i>';

    }

    function exitPseudoFullscreen() {
        const overlay = document.getElementById('pseudoFullscreenOverlay');
        if (overlay) {
            overlay.remove();
        }

        // Show original video
        document.getElementById('videoSection').querySelector('.video-container').style.display = 'block';

        isPseudoFullscreen = false;

        // Update button icon
        document.getElementById('mobileFullscreenBtn').innerHTML = '<i class="fas fa-expand"></i>';

    }

    // Update comment notification system for pseudo-fullscreen
    const originalCheckAndShowCommentNotifications = checkAndShowCommentNotifications;
    checkAndShowCommentNotifications = function() {
        const currentTime = Math.floor(video.currentTime);

        // Only check once per second
        if (currentTime === lastCheckedTime) return;
        lastCheckedTime = currentTime;

        // Find comments at current time (exact match or ±1 second)
        const currentComments = commentsData.filter(comment =>
            Math.abs(comment.timestamp_seconds - currentTime) <= 1
        );

        // Get IDs of comments that should be visible now
        const currentCommentIds = new Set(currentComments.map(c => c.id));

        // Only update notifications if the set of comments has changed
        if (!setsAreEqual(activeCommentIds, currentCommentIds)) {
            // Remove notifications that should no longer be visible
            activeCommentIds.forEach(commentId => {
                if (!currentCommentIds.has(commentId)) {
                    // For pseudo-fullscreen, remove from special area
                    if (isPseudoFullscreen) {
                        const commentsArea = document.querySelector('#pseudoFullscreenOverlay .comments-area');
                        if (commentsArea) {
                            const pseudoNotification = commentsArea.querySelector('.pseudo-fullscreen-comment');
                            if (pseudoNotification) {
                                pseudoNotification.remove();
                            }
                        }
                    } else {
                        // For normal mode, remove from notification area
                        const notification = document.getElementById(`notification-${commentId}`);
                        if (notification && notification.parentNode) {
                            notification.style.opacity = '0';
                            setTimeout(() => {
                                if (notification.parentNode) {
                                    notification.parentNode.removeChild(notification);
                                }
                            }, 300);
                        }
                    }
                }
            });

            // Show new notifications
            currentComments.forEach(comment => {
                if (!activeCommentIds.has(comment.id)) {
                    if (isPseudoFullscreen) {
                        showCommentInPseudoFullscreen(comment);
                    } else {
                        showCommentNotification(comment);
                    }
                }
            });

            // Update active comment IDs
            activeCommentIds = currentCommentIds;
        }
    };

    function showCommentInPseudoFullscreen(comment) {
        const commentsArea = document.getElementById('pseudoFullscreenComments');
        if (!commentsArea) return;

        const notification = document.createElement('div');
        notification.className = 'pseudo-fullscreen-comment';
        notification.style.cssText = `
            background: rgba(0, 183, 181, 0.2);
            border: 1px solid var(--color-accent, #4B9DA9);
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            animation: slideInFromBottom 0.5s ease;
        `;

        notification.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="mb-1">
                        <span class="badge badge-success">${comment.category}</span>
                        <span class="badge badge-warning ml-1">${comment.priority}</span>
                    </div>
                    <p class="mb-1" style="font-size: 14px;">${comment.comment}</p>
                    <small style="opacity: 0.8;">
                        <i class="fas fa-user"></i> ${comment.user.name} • ${formatTime(comment.timestamp_seconds)}
                    </small>
                </div>
                <button onclick="this.parentNode.parentNode.remove()" class="btn btn-sm btn-link text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        commentsArea.appendChild(notification);

        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 8000);
    }


    // Funcionalidad del botón "Comentar aquí"
    $('#addCommentBtn').on('click', function() {
        const video = document.getElementById('rugbyVideo');
        const currentTime = video ? video.currentTime : 0;

        // Mostrar sección de comentarios si está oculta
        const commentsSection = $('#commentsSection');
        const videoSection = $('#videoSection');
        const toggleBtn = $('#toggleCommentsBtn');
        const toggleText = $('#toggleCommentsText');
        const toggleIcon = toggleBtn.find('i');

        if (!commentsSection.is(':visible')) {
            // Mostrar sección de comentarios
            commentsSection.show();
            videoSection.removeClass('col-lg-12').addClass('col-lg-8');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            toggleText.text('Ocultar Comentarios');
            toggleBtn.removeClass('btn-success').addClass('btn-warning');
            $('#rugbyVideo').css('height', '500px');
        }

        // Scroll hacia la sección de comentarios
        setTimeout(() => {
            $('html, body').animate({
                scrollTop: $('#commentsSection').offset().top - 20
            }, 800);

            // Focus en el textarea de comentario y pre-llenar timestamp
            setTimeout(() => {
                $('#timestampDisplay').text(formatTime(currentTime));
                $('input[name="timestamp_seconds"]').val(Math.floor(currentTime));
                $('textarea[name="comment"]').focus();

                // Highlight del formulario brevemente
                const commentForm = $('#commentForm');
                commentForm.addClass('border border-success').css('background-color', '#f8fff9');
                setTimeout(() => {
                    commentForm.removeClass('border border-success').css('background-color', '');
                }, 2000);

                // Mostrar notificación
                if (typeof toastr !== 'undefined') {
                    toastr.info(`Timestamp establecido en ${formatTime(currentTime)}`, 'Listo para comentar');
                }
            }, 900);
        }, 100);
    });

    // Manejar borrado de comentarios
    $(document).on('click', '.delete-comment-btn', function() {
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
            success: function(response) {
                if (response.success) {
                    // Remover el elemento del DOM con animación
                    $commentElement.fadeOut(300, function() {
                        $(this).remove();

                        // Actualizar contador de comentarios
                        const currentCount = parseInt($('.card-title:contains("Comentarios")').text().match(/\((\d+)\)/)[1]);
                        const newCount = currentCount - 1;
                        $('.card-title:contains("Comentarios")').html(`<i class="fas fa-comments"></i> Comentarios (${newCount})`);
                    });

                    // SINCRONIZAR TIMELINE Y NOTIFICACIONES
                    // 1. Remover del array commentsData
                    const commentIndex = commentsData.findIndex(comment => comment.id == commentId);
                    if (commentIndex !== -1) {
                        commentsData.splice(commentIndex, 1);
                    }

                    // 2. Limpiar notificaciones activas del comentario borrado
                    activeCommentIds.delete(parseInt(commentId));

                    // 3. Remover notificación visible si existe
                    const notification = document.getElementById(`notification-${commentId}`);
                    if (notification && notification.parentNode) {
                        notification.style.opacity = '0';
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.parentNode.removeChild(notification);
                            }
                        }, 300);
                    }

                    // 4. Recrear timeline sin el marcador eliminado
                    if (video.duration && !isNaN(video.duration)) {
                        createTimelineMarkers();
                    }

                    // Mostrar mensaje de éxito
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Comentario eliminado exitosamente');
                    }
                }
            },
            error: function(xhr) {
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

    // ===========================
    // SISTEMA DE RESPUESTAS A COMENTARIOS
    // ===========================

    // Mostrar/ocultar formulario de respuesta
    $(document).on('click', '.reply-btn', function() {
        const commentId = $(this).data('comment-id');
        const replyForm = $(`#replyForm${commentId}`);

        // Ocultar otros formularios de respuesta abiertos
        $('.reply-form').not(replyForm).slideUp();

        // Toggle del formulario actual
        replyForm.slideToggle(300, function() {
            if (replyForm.is(':visible')) {
                const textarea = replyForm.find('textarea');
                textarea.focus();

                // Auto-scroll suave al textarea
                $('html, body').animate({
                    scrollTop: textarea.offset().top - 100
                }, 500);
            }
        });
    });

    // Enviar respuesta via AJAX
    $(document).on('submit', '.reply-form-submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const commentId = form.data('comment-id');
        const videoId = form.data('video-id');
        const textarea = form.find('textarea[name="reply_comment"]');
        const replyText = textarea.val().trim();
        const submitBtn = form.find('button[type="submit"]');

        // Prevenir doble-submit
        if (submitBtn.prop('disabled')) {
            return; // Ya está enviando, ignorar
        }

        // Validación
        if (!replyText) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Por favor escribe una respuesta');
            }
            return;
        }

        // Deshabilitar botón durante envío
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
                timestamp_seconds: 0, // Las respuestas no tienen timestamp propio
                category: 'general', // Las respuestas heredan categoría del padre
                priority: 'media' // Prioridad por defecto para respuestas
            },
            success: function(response) {
                if (response.success) {
                    // Limpiar textarea
                    textarea.val('');

                    // Ocultar formulario
                    form.closest('.reply-form').slideUp();

                    // Crear HTML de la respuesta
                    const userName = VideoPlayer.config.user.name;
                    const userRole = VideoPlayer.config.user.role;
                    const userId = VideoPlayer.config.user.id;
                    const badgeClass = userRole === 'analista' ? 'primary' : (userRole === 'entrenador' ? 'success' : 'info');
                    const roleLabel = userRole.charAt(0).toUpperCase() + userRole.slice(1);

                    const replyHtml = `
                        <div class="reply comment-item border-left border-primary pl-3 mb-2" data-reply-id="${response.comment.id}" style="display:none;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1">${replyText}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> ${userName}
                                        <span class="badge badge-sm badge-${badgeClass}">
                                            ${roleLabel}
                                        </span>
                                        - Hace unos segundos
                                    </small>
                                    <!-- Botón para responder a esta respuesta -->
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

                            <!-- Reply Form para respuestas anidadas -->
                            <div class="reply-form mt-2" id="replyForm${response.comment.id}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="${response.comment.id}" data-video-id="${videoId}">
                                    <input type="hidden" name="_token" value="${VideoPlayer.config.csrfToken}">
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>

                            <!-- Contenedor para respuestas anidadas -->
                            <div class="replies ml-3 mt-2"></div>
                        </div>
                    `;

                    // Buscar o crear la sección de respuestas
                    // Primero buscar el contenedor de respuestas más cercano (inmediatamente después del reply-form)
                    let repliesSection = form.closest('.reply-form').next('.replies');

                    // Si no existe, buscar dentro del comment-item padre
                    if (repliesSection.length === 0) {
                        repliesSection = form.closest('.comment-item').find('> .replies').first();
                    }

                    // Si aún no existe, crear uno nuevo
                    if (repliesSection.length === 0) {
                        const isNestedReply = form.closest('.reply').length > 0;
                        const marginClass = isNestedReply ? 'ml-3' : 'ml-4';
                        const repliesSectionHtml = `<div class="replies ${marginClass} mt-3"></div>`;
                        form.closest('.reply-form').after(repliesSectionHtml);
                        repliesSection = form.closest('.reply-form').next('.replies');
                    }

                    // Agregar respuesta con animación
                    repliesSection.append(replyHtml);
                    repliesSection.find('.reply:last').slideDown(300);

                    // Incrementar contador de comentarios
                    const commentCountElement = $('.card-title:contains("Comentarios")');
                    const currentCountMatch = commentCountElement.text().match(/\((\d+)\)/);
                    if (currentCountMatch) {
                        const currentCount = parseInt(currentCountMatch[1]);
                        const newCount = currentCount + 1;
                        commentCountElement.html(`<i class="fas fa-comments"></i> Comentarios (${newCount})`);
                    }

                    // Mostrar mensaje de éxito
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Respuesta agregada exitosamente');
                    }
                }
            },
            error: function(xhr) {
                console.error('Error al enviar respuesta:', xhr);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Error de validación: ';
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
            complete: function() {
                // Re-habilitar botón
                submitBtn.prop('disabled', false).html('<i class="fas fa-reply"></i> Responder');
            }
        });
    });

    // ===========================
    // ENVÍO DE COMENTARIOS PRINCIPALES VIA AJAX (SIN RECARGA)
    // ===========================
    $('#commentForm').on('submit', function(e) {
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

        // Validación
        if (!commentText) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Por favor escribe un comentario');
            }
            return;
        }

        // Prevenir doble-submit
        if (submitBtn.prop('disabled')) {
            return;
        }

        // Deshabilitar botón durante envío
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
            success: function(response) {
                if (response.success) {
                    // Limpiar formulario
                    textarea.val('');
                    timestampInput.val(0);
                    $('#timestampDisplay').text('00:00');
                    categorySelect.val('tecnico');
                    prioritySelect.val('media');

                    // Datos del usuario actual
                    const userName = VideoPlayer.config.user.name;
                    const userRole = VideoPlayer.config.user.role;
                    const userId = VideoPlayer.config.user.id;
                    const badgeClass = userRole === 'analista' ? 'primary' : (userRole === 'entrenador' ? 'success' : 'info');
                    const roleLabel = userRole.charAt(0).toUpperCase() + userRole.slice(1);

                    // Formatear timestamp
                    const minutes = Math.floor(timestampSeconds / 60);
                    const seconds = timestampSeconds % 60;
                    const formattedTimestamp = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                    // Badge de categoría
                    const categoryBadgeClass = category === 'tecnico' ? 'info' :
                        (category === 'tactico' ? 'warning' :
                        (category === 'fisico' ? 'success' : 'purple'));

                    // Badge de prioridad
                    const priorityBadgeClass = priority === 'critica' ? 'danger' :
                        (priority === 'alta' ? 'warning' :
                        (priority === 'media' ? 'info' : 'secondary'));

                    // HTML del nuevo comentario
                    const commentHtml = `
                        <div class="comment-item border-bottom p-2" data-timestamp="${timestampSeconds}" data-comment-id="${response.comment.id}" style="display:none;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <button class="btn btn-sm btn-primary timestamp-btn mr-2"
                                                data-timestamp="${timestampSeconds}">
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
                                        <span class="badge badge-sm badge-${badgeClass}">
                                            ${roleLabel}
                                        </span>
                                        - Hace unos segundos
                                    </small>
                                    <button class="btn btn-sm btn-link text-rugby p-0 ml-2 reply-btn"
                                            data-comment-id="${response.comment.id}"
                                            title="Responder">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </div>
                                <button class="btn btn-sm btn-outline-danger delete-comment-btn"
                                        data-comment-id="${response.comment.id}"
                                        title="Eliminar comentario">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <!-- Reply Form -->
                            <div class="reply-form mt-2" id="replyForm${response.comment.id}" style="display: none;">
                                <form class="reply-form-submit" data-comment-id="${response.comment.id}" data-video-id="${videoId}">
                                    <input type="hidden" name="_token" value="${VideoPlayer.config.csrfToken}">
                                    <textarea class="form-control form-control-sm mb-2" name="reply_comment" rows="2"
                                              placeholder="Escribe tu respuesta..." required></textarea>
                                    <button class="btn btn-rugby btn-sm" type="submit">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                </form>
                            </div>

                            <!-- Replies container -->
                            <div class="replies ml-4 mt-3"></div>
                        </div>
                    `;

                    // Agregar al inicio de la lista de comentarios
                    const commentsList = $('.card-body.p-0[style*="max-height: 400px"]');
                    commentsList.prepend(commentHtml);
                    commentsList.find('.comment-item:first').slideDown(300);

                    // Actualizar contador de comentarios
                    const commentCountElement = $('.card-title:contains("Comentarios")');
                    const currentCountMatch = commentCountElement.text().match(/\((\d+)\)/);
                    if (currentCountMatch) {
                        const currentCount = parseInt(currentCountMatch[1]);
                        const newCount = currentCount + 1;
                        commentCountElement.html(`<i class="fas fa-list"></i> Comentarios (${newCount})`);
                    }

                    // Agregar marcador al timeline si existe la función
                    if (typeof addTimelineMarker === 'function') {
                        addTimelineMarker(timestampSeconds, priority);
                    } else {
                        // Actualizar timeline manualmente si no existe la función
                        const video = document.getElementById('rugbyVideo');
                        const timeline = document.querySelector('.video-timeline');
                        if (video && timeline && video.duration > 0) {
                            const position = (timestampSeconds / video.duration) * 100;
                            const markerColor = priority === 'critica' ? '#dc3545' :
                                (priority === 'alta' ? '#ffc107' : '#28a745');
                            const marker = document.createElement('div');
                            marker.className = 'timeline-marker';
                            marker.style.cssText = `
                                position: absolute;
                                left: ${position}%;
                                top: 0;
                                width: 8px;
                                height: 100%;
                                background: ${markerColor};
                                cursor: pointer;
                                border-radius: 2px;
                                opacity: 0.8;
                            `;
                            marker.setAttribute('data-timestamp', timestampSeconds);
                            marker.setAttribute('title', `${formattedTimestamp} - ${category}`);
                            timeline.appendChild(marker);
                        }
                    }

                    // Mostrar mensaje de éxito
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Comentario agregado exitosamente');
                    }
                }
            },
            error: function(xhr) {
                console.error('Error al enviar comentario:', xhr);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Error de validación: ';
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
            complete: function() {
                // Re-habilitar botón
                submitBtn.prop('disabled', false).html('<i class="fas fa-comment"></i> Agregar');
            }
        });
    });

    // ===========================
    // SISTEMA DE ANOTACIONES
    // ===========================
    let annotationMode = false;
    let fabricCanvas = null;
    let currentTool = 'arrow';
    let isDrawing = false;
    let currentAnnotation = null;
    let savedAnnotations = []; // Array de anotaciones guardadas
    let currentDisplayedAnnotations = []; // CAMBIADO: Array de anotaciones mostradas actualmente
    let hasTemporaryDrawing = false; // Flag para dibujos temporales

    // DEBUG: Hacer variables accesibles globalmente
    window.savedAnnotations = savedAnnotations;
    window.currentDisplayedAnnotations = currentDisplayedAnnotations;
    window.hasTemporaryDrawing = hasTemporaryDrawing;

    // Inicializar sistema de anotaciones
    function initAnnotationSystem() {
        const canvas = document.getElementById('annotationCanvas');
        const video = document.getElementById('rugbyVideo');
        const videoContainer = video.parentElement;

        // Configurar canvas dimensions
        function resizeCanvas() {
            canvas.width = video.offsetWidth;
            canvas.height = video.offsetHeight;
            canvas.style.width = video.offsetWidth + 'px';
            canvas.style.height = video.offsetHeight + 'px';

            if (fabricCanvas) {
                fabricCanvas.setDimensions({
                    width: video.offsetWidth,
                    height: video.offsetHeight
                });

                // MOVER canvas container DENTRO del video container
                const canvasContainer = document.querySelector('.canvas-container');
                if (canvasContainer && canvasContainer.parentElement !== videoContainer) {
                    videoContainer.appendChild(canvasContainer);
                    canvasContainer.style.cssText = `
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        pointer-events: none;
                        z-index: 5;
                    `;
                }
            }
        }

        // Initialize Fabric.js canvas
        fabricCanvas = new fabric.Canvas('annotationCanvas');
        fabricCanvas.selection = false;
        fabricCanvas.isDrawingMode = false;

        // FORCE move canvas container to video container immediately
        setTimeout(() => {
            const canvasContainer = document.querySelector('.canvas-container');
            if (canvasContainer && canvasContainer.parentElement !== videoContainer) {
                videoContainer.appendChild(canvasContainer);
            }
        }, 100);

        // Initial resize
        resizeCanvas();

        // Resize on window resize and video load
        window.addEventListener('resize', resizeCanvas);
        video.addEventListener('loadedmetadata', resizeCanvas);


        // Cargar anotaciones existentes
        loadExistingAnnotations();
    }

    // Cargar anotaciones existentes del video
    function loadExistingAnnotations() {
        $.ajax({
            url: `/api/annotations/video/${VideoPlayer.config.videoId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    savedAnnotations = response.annotations;
                    window.savedAnnotations = savedAnnotations; // DEBUG: Actualizar global

                    // Renderizar lista de anotaciones en el sidebar
                    renderAnnotationsList();

                    // 🔧 FIX: Forzar actualización del canvas basado en timestamp actual
                    // Esto limpia dibujos temporales y muestra solo anotaciones guardadas activas
                    if (fabricCanvas) {
                        checkAndShowAnnotations();
                    }
                }
            },
            error: function(xhr) {
                console.error('❌ Error cargando anotaciones:', xhr);
            }
        });
    }

    // Renderizar lista de anotaciones en el sidebar
    function renderAnnotationsList() {
        const annotationsList = document.getElementById('annotationsList');
        const annotationsCount = document.getElementById('annotationsCount');
        const noAnnotationsMessage = document.getElementById('noAnnotationsMessage');

        // Actualizar contador
        annotationsCount.textContent = savedAnnotations.length;

        // Limpiar solo los items de anotaciones (no borrar noAnnotationsMessage)
        const existingItems = annotationsList.querySelectorAll('.annotation-item');
        existingItems.forEach(item => item.remove());

        if (savedAnnotations.length === 0) {
            // Mostrar mensaje de sin anotaciones
            noAnnotationsMessage.style.display = 'block';
            return;
        }

        // Ocultar mensaje y crear lista
        noAnnotationsMessage.style.display = 'none';

        // Crear items de anotaciones ordenados por timestamp
        const sortedAnnotations = [...savedAnnotations].sort((a, b) =>
            parseFloat(a.timestamp) - parseFloat(b.timestamp)
        );

        sortedAnnotations.forEach(annotation => {
            const timestamp = parseFloat(annotation.timestamp);
            const duration = parseInt(annotation.duration_seconds) || 4;
            const isPermanent = annotation.is_permanent;

            const item = document.createElement('div');
            item.className = 'annotation-item border-bottom p-2';
            item.setAttribute('data-annotation-id', annotation.id);

            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <button class="btn btn-sm btn-info timestamp-btn-annotation mr-2"
                                data-timestamp="${timestamp}"
                                title="Ir al momento de esta anotación">
                            <i class="fas fa-clock"></i> ${formatTime(timestamp)}
                        </button>
                        <span class="badge badge-${isPermanent ? 'primary' : 'secondary'} ml-1">
                            ${isPermanent ? 'Permanente' : duration + 's'}
                        </span>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-user"></i> ${annotation.user ? annotation.user.name : 'Desconocido'}
                        </small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger delete-annotation-btn"
                            data-annotation-id="${annotation.id}"
                            title="Eliminar anotación">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            annotationsList.appendChild(item);
        });
    }

    // Event delegation para botones de timestamp (FUERA de renderAnnotationsList)
    $(document).on('click', '.timestamp-btn-annotation', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const timestamp = $(this).data('timestamp');
        video.currentTime = timestamp;
        if (video.paused) {
            video.play();
        }
    });

    // Función para eliminar anotación
    // FIX: Simplificado para evitar race conditions con checkAndShowAnnotations()
    function deleteAnnotation(annotationId) {
        if (!confirm('¿Estás seguro de eliminar esta anotación? Esta acción no se puede deshacer.')) {
            return;
        }

        // Deshabilitar botones de eliminar para evitar clicks múltiples
        $('.delete-annotation-btn').prop('disabled', true).addClass('disabled');
        $('#deleteAnnotationBtn').prop('disabled', true).addClass('disabled');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: `/api/annotations/${annotationId}`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    // Remover del array savedAnnotations
                    const index = savedAnnotations.findIndex(a => a.id == annotationId);

                    if (index !== -1) {
                        savedAnnotations.splice(index, 1);
                        window.savedAnnotations = savedAnnotations;
                    }

                    // Actualizar lista en sidebar
                    renderAnnotationsList();

                    // Mostrar mensaje de éxito
                    if (typeof toastr !== 'undefined') {
                        if (response.already_deleted) {
                            toastr.info('Esta anotación ya había sido eliminada');
                        } else {
                            toastr.success('Anotación eliminada exitosamente');
                        }
                    }

                    // Actualizar anotaciones visibles
                    checkAndShowAnnotations();

                    // Re-habilitar botones de eliminar
                    $('.delete-annotation-btn').prop('disabled', false).removeClass('disabled');
                    $('#deleteAnnotationBtn').prop('disabled', false).removeClass('disabled');
                }
            },
            error: function(xhr) {
                console.error('❌ Error eliminando anotación');
                console.error('Status:', xhr.status);

                // 🔧 FIX: Re-habilitar botones de eliminar después de error
                $('.delete-annotation-btn').prop('disabled', false).removeClass('disabled');
                $('#deleteAnnotationBtn').prop('disabled', false).removeClass('disabled');

                if (xhr.status === 500 || xhr.status === 404) {
                    loadExistingAnnotations();

                    if (typeof toastr !== 'undefined') {
                        toastr.warning('La anotación ya no existe. Lista actualizada.');
                    }
                } else if (xhr.status === 403) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('No tienes permisos para eliminar esta anotación');
                    } else {
                        alert('No tienes permisos para eliminar esta anotación');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error al eliminar la anotación');
                    } else {
                        alert('Error al eliminar la anotación');
                    }
                }
            }
        });
    }

    // Event listener con delegación - lee el ID dinámicamente del botón
    $(document).off('click', '#deleteAnnotationBtn').on('click', '#deleteAnnotationBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const annotationId = $(this).data('annotation-id');

        if (annotationId) {
            // Solo hay una anotación visible
            deleteAnnotation(annotationId);
        } else if (currentDisplayedAnnotations.length > 0) {
            // Hay múltiples anotaciones visibles - mostrar selector
            let message = '¿Cuál anotación deseas eliminar?\n\n';
            currentDisplayedAnnotations.forEach((ann, index) => {
                const userName = ann.user ? ann.user.name : 'Desconocido';
                const timestamp = formatTime(parseFloat(ann.timestamp));
                const type = ann.is_permanent ? 'Permanente' : `${ann.duration_seconds}s`;
                message += `${index + 1}. ${timestamp} - ${type} (${userName})\n`;
            });
            message += `\nIngresa el número (1-${currentDisplayedAnnotations.length}):`;

            const choice = prompt(message);
            const choiceNum = parseInt(choice);

            if (choiceNum >= 1 && choiceNum <= currentDisplayedAnnotations.length) {
                const selectedAnnotation = currentDisplayedAnnotations[choiceNum - 1];
                deleteAnnotation(selectedAnnotation.id);
            } else if (choice !== null) {
                alert('Número inválido');
            }
        }
    });

    // Event delegation para botones de eliminar en lista
    $(document).on('click', '.delete-annotation-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const annotationId = $(this).data('annotation-id');
        if (annotationId) {
            deleteAnnotation(annotationId);
        }
    });

    // Toggle annotation mode
    $('#toggleAnnotationMode').on('click', function() {
        if (!annotationMode) {
            enterAnnotationMode();
        } else {
            exitAnnotationMode();
        }
    });

    function enterAnnotationMode() {
        annotationMode = true;

        // Pause video
        const video = document.getElementById('rugbyVideo');
        video.pause();

        // Show toolbar
        $('#annotationToolbar').fadeIn(300);

        // Enable canvas interactions - SIMPLIFICADO
        $('.canvas-container').css('pointer-events', 'auto');

        // Enable fabric canvas pointer events
        if (fabricCanvas) {
            fabricCanvas.upperCanvasEl.style.pointerEvents = 'auto';
            fabricCanvas.lowerCanvasEl.style.pointerEvents = 'auto';
        }

        // Change button state
        $('#toggleAnnotationMode')
            .removeClass('btn-warning')
            .addClass('btn-success')
            .html('<i class="fas fa-check"></i> Anotando');

        // Initialize if not done
        if (!fabricCanvas) {
            initAnnotationSystem();
        }

    }

    function exitAnnotationMode() {
        annotationMode = false;

        // Hide toolbar
        $('#annotationToolbar').fadeOut(300);

        // Disable canvas interactions - SIMPLIFICADO
        $('.canvas-container').css('pointer-events', 'none');

        if (fabricCanvas) {
            fabricCanvas.upperCanvasEl.style.pointerEvents = 'none';
            fabricCanvas.lowerCanvasEl.style.pointerEvents = 'none';
        }

        // Change button state
        $('#toggleAnnotationMode')
            .removeClass('btn-success')
            .addClass('btn-warning')
            .html('<i class="fas fa-paint-brush"></i> Anotar');

        // 🔧 FIX: Resetear displayedAnnotations para forzar re-renderizado
        // Esto asegura que checkAndShowAnnotations() detecte cambios y limpie el canvas
        currentDisplayedAnnotations = [];

    }

    // Close annotation mode
    $('#closeAnnotationMode').on('click', exitAnnotationMode);

    // Tool selection
    $('.toolbar-btn[data-tool]').on('click', function() {
        const tool = $(this).data('tool');
        currentTool = tool;

        // Update active state
        $('.toolbar-btn[data-tool]').removeClass('active');
        $(this).addClass('active');

        // Configure canvas for tool
        if (fabricCanvas) {
            fabricCanvas.isDrawingMode = false;
            fabricCanvas.selection = false;

            if (tool === 'free_draw') {
                fabricCanvas.isDrawingMode = true;
                fabricCanvas.freeDrawingBrush.width = 3;
                fabricCanvas.freeDrawingBrush.color = '#ff0000';
            }
        }

    });

    // Drawing functionality
    function startDrawing(tool, startX, startY) {
        const selectedColor = document.getElementById('annotationColor').value;
        const options = {
            left: startX,
            top: startY,
            fill: 'transparent',
            stroke: selectedColor,
            strokeWidth: 3,
            selectable: true,
            evented: true
        };

        switch (tool) {
            case 'arrow':
                currentAnnotation = new fabric.Line([startX, startY, startX, startY], {
                    ...options,
                    strokeWidth: 4
                });
                break;
            case 'circle':
                currentAnnotation = new fabric.Circle({
                    ...options,
                    radius: 1,
                    left: startX,
                    top: startY
                });
                break;
            case 'line':
                currentAnnotation = new fabric.Line([startX, startY, startX, startY], options);
                break;
            case 'rectangle':
                currentAnnotation = new fabric.Rect({
                    ...options,
                    width: 1,
                    height: 1
                });
                break;
            case 'text':
                const textValue = prompt('Ingresa el texto:') || 'Texto';
                currentAnnotation = new fabric.Text(textValue, {
                    left: startX,
                    top: startY,
                    fill: selectedColor,
                    fontSize: 20,
                    fontFamily: 'Arial',
                    selectable: true,
                    evented: true
                });
                break;
        }

        if (currentAnnotation) {
            fabricCanvas.add(currentAnnotation);
            fabricCanvas.renderAll();
            hasTemporaryDrawing = true; // Marcar que hay dibujo temporal
        }
    }

    function updateDrawing(tool, currentX, currentY, startX, startY) {
        if (!currentAnnotation) return;

        switch (tool) {
            case 'arrow':
            case 'line':
                currentAnnotation.set({
                    x2: currentX,
                    y2: currentY
                });
                break;
            case 'circle':
                const radius = Math.sqrt(Math.pow(currentX - startX, 2) + Math.pow(currentY - startY, 2));
                currentAnnotation.set({
                    radius: radius
                });
                break;
            case 'rectangle':
                currentAnnotation.set({
                    width: Math.abs(currentX - startX),
                    height: Math.abs(currentY - startY),
                    left: Math.min(startX, currentX),
                    top: Math.min(startY, currentY)
                });
                break;
        }

        fabricCanvas.renderAll();
    }

    // Canvas mouse events
    let startX, startY;

    if (typeof fabric !== 'undefined') {
        $(document).ready(function() {
            // Wait for canvas to be available
            setTimeout(() => {
                const canvas = document.getElementById('annotationCanvas');
                if (canvas) {
                    initAnnotationSystem();

                    fabricCanvas.on('mouse:down', function(event) {
                        if (!annotationMode || currentTool === 'free_draw') return;

                        const pointer = fabricCanvas.getPointer(event.e);
                        startX = pointer.x;
                        startY = pointer.y;

                        // Herramienta texto no necesita dragging
                        if (currentTool === 'text') {
                            startDrawing(currentTool, startX, startY);
                            return;
                        }

                        isDrawing = true;
                        startDrawing(currentTool, startX, startY);
                    });

                    fabricCanvas.on('mouse:move', function(event) {
                        if (!annotationMode || !isDrawing || currentTool === 'free_draw') return;

                        const pointer = fabricCanvas.getPointer(event.e);
                        updateDrawing(currentTool, pointer.x, pointer.y, startX, startY);
                    });

                    fabricCanvas.on('mouse:up', function(event) {
                        if (!annotationMode) return;

                        isDrawing = false;
                        currentAnnotation = null;

                        // Add arrow head for arrow tool
                        if (currentTool === 'arrow' && fabricCanvas.getObjects().length > 0) {
                            const line = fabricCanvas.getObjects()[fabricCanvas.getObjects().length - 1];
                            if (line.type === 'line') {
                                addArrowHead(line);
                            }
                        }
                    });
                }
            }, 500);
        });
    }

    function addArrowHead(line) {
        const x1 = line.x1;
        const y1 = line.y1;
        const x2 = line.x2;
        const y2 = line.y2;
        const selectedColor = document.getElementById('annotationColor').value;

        const angle = Math.atan2(y2 - y1, x2 - x1);
        const headLength = 20;

        const arrowHead = new fabric.Polygon([
            {x: x2, y: y2},
            {x: x2 - headLength * Math.cos(angle - Math.PI/6), y: y2 - headLength * Math.sin(angle - Math.PI/6)},
            {x: x2 - headLength * Math.cos(angle + Math.PI/6), y: y2 - headLength * Math.sin(angle + Math.PI/6)}
        ], {
            fill: selectedColor,
            stroke: selectedColor,
            strokeWidth: 2,
            selectable: true,
            evented: true
        });

        fabricCanvas.add(arrowHead);
        fabricCanvas.renderAll();
    }

    // Save annotation
    $('#saveAnnotation').on('click', function() {
        if (!fabricCanvas || fabricCanvas.getObjects().length === 0) {
            alert('No hay anotaciones para guardar');
            return;
        }

        const video = document.getElementById('rugbyVideo');
        const timestamp = video.currentTime;
        const selectedDuration = document.getElementById('annotationDuration').value;

        // Get canvas data
        const annotationData = {
            canvas_data: fabricCanvas.toJSON(),
            canvas_width: fabricCanvas.width,
            canvas_height: fabricCanvas.height,
            video_width: video.videoWidth,
            video_height: video.videoHeight
        };

        // Prepare data
        const postData = {
            video_id: VideoPlayer.config.videoId,
            timestamp: timestamp,
            annotation_type: 'canvas',
            annotation_data: annotationData
        };

        // Add duration settings
        if (selectedDuration === 'permanent') {
            postData.is_permanent = true;
        } else {
            postData.duration_seconds = parseInt(selectedDuration);
            postData.is_permanent = false;
        }

        // Save via API
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: '/api/annotations',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(postData),
            success: function(response) {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Anotación guardada exitosamente');
                    }

                    // Recargar anotaciones guardadas
                    loadExistingAnnotations();

                    // Salir automáticamente del modo anotación
                    exitAnnotationMode();

                    // Reset flag temporal después de guardar
                    hasTemporaryDrawing = false;

                } else {
                    console.error('❌ Error al guardar:', response);
                    alert('Error al guardar la anotación');
                }
            },
            error: function(xhr) {
                console.error('❌ Error AJAX:', xhr);
                console.error('❌ Status:', xhr.status);
                console.error('❌ Response:', xhr.responseText);
                console.error('❌ Data sent:', JSON.stringify({
                    video_id: VideoPlayer.config.videoId,
                    timestamp: timestamp,
                    annotation_type: 'canvas',
                    annotation_data: annotationData
                }));

                let errorMsg = 'Error de conexión al guardar la anotación';
                if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMsg = errorData.message;
                        }
                        if (errorData.errors) {
                            console.error('❌ Validation errors:', errorData.errors);
                            errorMsg += '\nErrores: ' + JSON.stringify(errorData.errors);
                        }
                    } catch (e) {
                        errorMsg += '\nResponse: ' + xhr.responseText;
                    }
                }
                alert(errorMsg);
            }
        });
    });

    // Clear annotations
    $('#clearAnnotations').on('click', function() {
        if (fabricCanvas) {
            fabricCanvas.clear();
            hasTemporaryDrawing = false; // Reset flag temporal
        }
    });

    // Función para mostrar/ocultar anotaciones según timestamp y duración
    function checkAndShowAnnotations() {
        if (annotationMode || !fabricCanvas) {
            return; // No mostrar en modo edición
        }

        // Si hay dibujo temporal, no interferir
        if (hasTemporaryDrawing) {
            return;
        }

        const currentTime = video.currentTime;

        // ✨ CAMBIO PRINCIPAL: Usar .filter() para obtener TODAS las anotaciones activas
        const activeAnnotations = savedAnnotations.filter(annotation => {
            const startTime = parseFloat(annotation.timestamp);
            const durationSeconds = parseInt(annotation.duration_seconds) || 4;
            const endTime = annotation.is_permanent ? Infinity : startTime + durationSeconds;

            // 🔧 FIX: Tolerancia de 0.15 segundos para manejar imprecisiones de milisegundos
            // Problema: currentTime puede ser 5.878967 mientras startTime es 5.88 (guardado en BD)
            // Solución: Permitir que la anotación se active 150ms antes del timestamp exacto
            const TOLERANCE = 0.15;
            const isActive = currentTime >= (startTime - TOLERANCE) && currentTime <= endTime;

            return isActive;
        });

        // Comparar si el conjunto de anotaciones cambió
        const activeIds = activeAnnotations.map(a => a.id).sort().join(',');
        const displayedIds = currentDisplayedAnnotations.map(a => a.id).sort().join(',');

        if (activeIds !== displayedIds) {
            if (activeAnnotations.length > 0) {
                // Mostrar todas las anotaciones activas
                displayMultipleAnnotations(activeAnnotations);

                // ⚠️ LÍNEA CRÍTICA - Actualizar referencia de anotaciones mostradas
                currentDisplayedAnnotations = activeAnnotations;

                // Mostrar botón de eliminar con dropdown si hay múltiples
                const deleteBtn = document.getElementById('deleteAnnotationBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = 'block';

                    // Si solo hay 1, mostrar ID directo
                    if (activeAnnotations.length === 1) {
                        $(deleteBtn).data('annotation-id', activeAnnotations[0].id);
                        deleteBtn.innerHTML = '<i class="fas fa-times-circle"></i> Eliminar Anotación';
                    } else {
                        // Si hay múltiples, mostrar contador
                        $(deleteBtn).removeData('annotation-id');
                        deleteBtn.innerHTML = `<i class="fas fa-times-circle"></i> ${activeAnnotations.length} Anotaciones`;
                    }
                }
            } else {
                // No hay anotaciones activas
                clearDisplayedAnnotation();
                currentDisplayedAnnotations = [];

                // Ocultar botón de eliminar
                const deleteBtn = document.getElementById('deleteAnnotationBtn');
                if (deleteBtn) {
                    deleteBtn.style.display = 'none';
                    deleteBtn.removeAttribute('data-annotation-id');
                }
            }
        }
    }

    // Nueva función para mostrar múltiples anotaciones simultáneamente
    function displayMultipleAnnotations(annotations) {
        if (!fabricCanvas) return;

        // Limpiar canvas actual
        fabricCanvas.clear();

        // Cargar todas las anotaciones en el canvas
        annotations.forEach((annotation, index) => {
            if (annotation.annotation_data && annotation.annotation_data.canvas_data) {
                // Cargar cada anotación como un grupo de objetos
                const canvasData = annotation.annotation_data.canvas_data;

                // Usar loadFromJSON con merge=true para agregar al canvas existente
                fabric.util.enlivenObjects(canvasData.objects || [], function(objects) {
                    objects.forEach(function(obj) {
                        fabricCanvas.add(obj);
                    });
                    fabricCanvas.renderAll();
                }, null);
            }
        });

    }

    // Función heredada para compatibilidad (ahora usa la nueva lógica)
    function displayAnnotation(annotation) {
        displayMultipleAnnotations([annotation]);
    }

    function clearDisplayedAnnotation() {
        if (!fabricCanvas) return;
        fabricCanvas.clear();
    }

    // DEBUG: Hacer funciones accesibles globalmente
    window.checkAndShowAnnotations = checkAndShowAnnotations;
    window.displayAnnotation = displayAnnotation;
    window.clearDisplayedAnnotation = clearDisplayedAnnotation;

    // ========== STATS MODAL HANDLER ==========
    if (VideoPlayer.config.user.canViewStats) {
    $('#statsModal').on('show.bs.modal', function () {
        loadVideoStats();
    });

    function loadVideoStats() {
        fetch(VideoPlayer.config.routes.stats, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': VideoPlayer.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update totals
                $('#modalTotalViews').text(data.total_views);
                $('#modalUniqueViewers').text(data.unique_viewers);

                // Update table
                const tbody = $('#statsTableBody');
                tbody.empty();

                if (data.stats.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <i class="fas fa-info-circle"></i> No hay visualizaciones registradas aún
                            </td>
                        </tr>
                    `);
                } else {
                    data.stats.forEach(stat => {
                        // Usar timestamp Unix si está disponible, sino parsear fecha
                        const lastViewed = stat.last_viewed_timestamp
                            ? formatRelativeTimeFromTimestamp(stat.last_viewed_timestamp)
                            : formatRelativeTime(stat.last_viewed);
                        tbody.append(`
                            <tr>
                                <td><i class="fas fa-user"></i> ${stat.user.name}</td>
                                <td class="text-center"><span class="badge badge-success">${stat.view_count}x</span></td>
                                <td><i class="fas fa-clock"></i> ${lastViewed}</td>
                            </tr>
                        `);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            $('#statsTableBody').html(`
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error al cargar visualizaciones
                    </td>
                </tr>
            `);
        });
    }

    // Función que usa timestamp Unix (independiente de timezone)
    function formatRelativeTimeFromTimestamp(timestamp) {
        const nowTimestamp = Math.floor(Date.now() / 1000); // Timestamp actual en segundos
        const diffSecs = nowTimestamp - timestamp;
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffSecs < 60) return 'Hace unos segundos';
        if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;

        // Mostrar horas y minutos para mayor precisión
        if (diffHours < 24) {
            const remainingMins = diffMins % 60;

            // Si tiene horas y minutos
            if (diffHours > 0 && remainingMins > 0) {
                return `Hace ${diffHours}h ${remainingMins}min`;
            }
            // Si solo tiene horas exactas (sin minutos restantes)
            if (diffHours > 0 && remainingMins === 0) {
                return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
            }
            // Si tiene menos de 1 hora (solo minutos)
            return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        }

        if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        if (diffDays < 30) {
            const weeks = Math.floor(diffDays / 7);
            return `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
        }
        const months = Math.floor(diffDays / 30);
        return `Hace ${months} mes${months > 1 ? 'es' : ''}`;
    }

    // Función legacy que parsea string de fecha (puede tener problemas de timezone)
    function formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffSecs < 60) return 'Hace unos segundos';
        if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;

        // Mostrar horas y minutos para mayor precisión
        if (diffHours < 24) {
            const remainingMins = diffMins % 60;

            // Si tiene horas y minutos
            if (diffHours > 0 && remainingMins > 0) {
                return `Hace ${diffHours}h ${remainingMins}min`;
            }
            // Si solo tiene horas exactas (sin minutos restantes)
            if (diffHours > 0 && remainingMins === 0) {
                return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
            }
            // Si tiene menos de 1 hora (solo minutos)
            return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        }

        if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        if (diffDays < 30) {
            const weeks = Math.floor(diffDays / 7);
            return `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
        }
        const months = Math.floor(diffDays / 30);
        return `Hace ${months} mes${months > 1 ? 'es' : ''}`;
    }
    }
    // ========== END STATS MODAL HANDLER ==========

    // ========== TRIBUTE.JS - AUTOCOMPLETADO DE MENCIONES ==========
    // Cargar usuarios disponibles para mencionar
    const allUsers = VideoPlayer.config.allUsers;

    // Configurar Tribute.js para el textarea de comentarios
    const tribute = new Tribute({
        values: allUsers.map(user => ({
            key: user.name,
            value: user.name,
            role: user.role
        })),
        selectTemplate: function(item) {
            // Agregar espacio después de la mención para evitar sugerencias continuas
            return '@' + item.original.value + ' ';
        },
        menuItemTemplate: function(item) {
            const badgeClass = item.original.role === 'jugador' ? 'badge-info' :
                              (item.original.role === 'entrenador' ? 'badge-success' :
                              (item.original.role === 'analista' ? 'badge-primary' : 'badge-secondary'));

            return `
                <div class="d-flex justify-content-between align-items-center">
                    <span>${item.original.value}</span>
                    <span class="badge ${badgeClass} ml-2">${item.original.role}</span>
                </div>
            `;
        },
        noMatchTemplate: function() {
            return '<span style="visibility: hidden;"></span>';
        },
        lookup: 'key',
        fillAttr: 'value',
        allowSpaces: true,
        menuShowMinLength: 0
    });

    // Attach tribute to comment textareas
    tribute.attach(document.querySelectorAll('textarea[name="comment"], textarea[name="reply_comment"]'));

    // ========== END TRIBUTE.JS ==========

});
