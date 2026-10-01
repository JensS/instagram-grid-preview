/**
 * Admin JavaScript for Instagram Grid Preview
 *
 * Editor for Instagram-style profiles: multi-slide posts (images + video),
 * captions, media-type badges and hover like/comment counts.
 */

(function($) {
    'use strict';

    let gridData = {};
    let sortableInstance = null;
    let mediaFrame = null;
    let posterFrame = null;
    let avatarFrame = null;
    let posterTarget = null;
    let currentCellIndex = null;
    let appendToCell = false;
    let activeModalIndex = null;
    let isRegeneratingGrid = false; // Flag to prevent recursive grid regeneration

    // Grid configuration state - single source of truth
    let gridConfig = {
        columns: 3,
        rows: 3,
        aspectRatio: '1:1'
    };

    // Inline SVG icons matching Instagram's iconography
    const IGP_ICONS = {
        heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78l8.84 8.84 8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        comment: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        carousel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="3" width="14" height="14" rx="2"/><path d="M17 21H5a2 2 0 0 1-2-2V7"/></svg>',
        reel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M10 8.5l6 3.5-6 3.5z" fill="currentColor" stroke="none"/></svg>',
        video: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
        plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
        link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>',
        camera: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>'
    };

    /**
     * Escape HTML (including quotes) to prevent XSS attacks
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function commaNumber(n) {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function formatCount(count) {
        const n = Math.max(0, parseInt(count, 10) || 0);
        if (n < 10000) return commaNumber(n);
        if (n < 1000000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    }

    /**
     * Effective media type for a post (badge + auto detection).
     */
    function effectiveMediaType(post) {
        if (!post) return 'photo';
        if (['carousel', 'reel', 'video'].indexOf(post.media_type) !== -1) {
            return post.media_type;
        }
        const media = post.media || [];
        if (media.length > 1) return 'carousel';
        if (media.length === 1 && media[0].type === 'video') return 'video';
        return 'photo';
    }

    $(document).ready(function() {
        syncConfigFromDOM();
        initializeGridEditor();
        bindEvents();

        if (window.igpGridData) {
            gridData = window.igpGridData;
            updateGridDisplay();
        }
    });

    function syncConfigFromDOM() {
        gridConfig.columns = parseInt($('#grid-columns').val()) || 3;
        gridConfig.rows = parseInt($('#grid-rows').val()) || 3;
        gridConfig.aspectRatio = $('#grid-aspect-ratio').val() || '1:1';
    }

    function syncConfigToDOM() {
        $('#grid-columns').val(gridConfig.columns);
        $('#grid-rows').val(gridConfig.rows);
        $('#grid-aspect-ratio').val(gridConfig.aspectRatio);
    }

    function initializeGridEditor() {
        generateGrid();
        initializeSortable();
    }

    function generateGrid() {
        if (isRegeneratingGrid) return;
        isRegeneratingGrid = true;

        const container = document.getElementById('igp-grid-editor');
        const columns = gridConfig.columns;
        const rows = gridConfig.rows;

        container.setAttribute('data-columns', columns);
        container.setAttribute('data-rows', rows);
        container.setAttribute('data-aspect-ratio', gridConfig.aspectRatio);
        container.innerHTML = '';

        for (let row = 0; row < rows; row++) {
            const rowContainer = document.createElement('div');
            rowContainer.className = 'igp-grid-row';
            rowContainer.setAttribute('data-row-index', row);

            const rowControls = document.createElement('div');
            rowControls.className = 'igp-row-controls';
            rowControls.innerHTML = `
                <button type="button" class="button button-small igp-add-row-above" data-row="${row}" title="Add new row above this row">Add Row Above</button>
                <button type="button" class="button button-small igp-add-row-below" data-row="${row}" title="Add new row below this row">Add Row Below</button>
            `;
            rowContainer.appendChild(rowControls);

            const cellsInRow = document.createElement('div');
            cellsInRow.className = 'igp-cells-in-row';
            cellsInRow.style.setProperty('--columns', columns);

            for (let col = 0; col < columns; col++) {
                const cellIndex = row * columns + col;
                cellsInRow.appendChild(createGridCell(cellIndex, row, col));
            }
            rowContainer.appendChild(cellsInRow);
            container.appendChild(rowContainer);
        }

        destroySortable();
        initializeSortable();
        updateGridDisplay();
        isRegeneratingGrid = false;
    }

    function createGridCell(index, row, col) {
        const cell = document.createElement('div');
        cell.className = 'igp-grid-cell';
        cell.setAttribute('data-index', index);
        cell.setAttribute('data-row', row);
        cell.setAttribute('data-col', col);

        cell.addEventListener('click', function(e) {
            if (e.target.closest('.igp-cell-action')) {
                return;
            }
            if (cell.classList.contains('has-image')) {
                openPostSettings(index);
            } else {
                openMediaLibrary(index, false);
            }
        });

        return cell;
    }

    function destroySortable() {
        if (!sortableInstance) return;
        if (Array.isArray(sortableInstance)) {
            sortableInstance.forEach(function(instance) {
                if (instance && typeof instance.destroy === 'function') instance.destroy();
            });
        } else if (typeof sortableInstance.destroy === 'function') {
            sortableInstance.destroy();
        }
        sortableInstance = null;
    }

    function initializeSortable() {
        const rows = document.querySelectorAll('.igp-cells-in-row');
        if (typeof Sortable === 'undefined') return;

        destroySortable();
        sortableInstance = [];

        rows.forEach(function(row) {
            const instance = Sortable.create(row, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                group: 'grid-cells',
                filter: function(evt, item) {
                    if (!item.classList.contains('has-image')) return true;
                    if (evt.target && evt.target.closest && evt.target.closest('.igp-cell-action')) return true;
                    return false;
                },
                onStart: function(evt) {
                    evt.item.style.cursor = 'grabbing';
                },
                onEnd: function(evt) {
                    const oldRowIndex = parseInt(evt.from.closest('.igp-grid-row').getAttribute('data-row-index'));
                    const newRowIndex = parseInt(evt.to.closest('.igp-grid-row').getAttribute('data-row-index'));
                    const columns = gridConfig.columns;
                    const oldAbsIndex = oldRowIndex * columns + evt.oldIndex;
                    const newAbsIndex = newRowIndex * columns + Math.min(evt.newIndex, columns - 1);

                    evt.item.style.cursor = '';

                    if (oldAbsIndex !== newAbsIndex) {
                        const oldItem = gridData[oldAbsIndex];
                        const newItem = gridData[newAbsIndex];
                        if (oldItem) { gridData[newAbsIndex] = oldItem; } else { delete gridData[newAbsIndex]; }
                        if (newItem) { gridData[oldAbsIndex] = newItem; } else { delete gridData[oldAbsIndex]; }
                    }

                    setTimeout(function() { generateGrid(); }, 0);
                }
            });
            sortableInstance.push(instance);
        });
    }

    /**
     * Convert a WP media attachment into a slide object.
     */
    function attachmentToSlide(att) {
        const isVideo = att.type === 'video';
        let thumb = att.url;
        if (isVideo) {
            // Videos have no native poster; leave it empty unless WordPress
            // generated one, so the editor can ask for it explicitly.
            thumb = (att.image && att.image.src) ? att.image.src : '';
        } else if (att.sizes) {
            thumb = (att.sizes.medium_large || att.sizes.large || att.sizes.medium || att.sizes.thumbnail || {}).url || att.url;
        }
        return {
            type: isVideo ? 'video' : 'image',
            id: att.id,
            url: att.url,
            thumbnail_url: thumb,
            alt: att.alt || att.title || ''
        };
    }

    /**
     * Return the shared "add media" frame, creating it once.
     * Reusing a single frame avoids stacking media modals (and the
     * mediaelement teardown errors that come with them).
     */
    function getMediaFrame() {
        if (mediaFrame) {
            return mediaFrame;
        }

        mediaFrame = wp.media({
            title: 'Select media',
            button: { text: 'Add to post' },
            multiple: true,
            library: { type: ['image', 'video'] }
        });

        mediaFrame.on('select', function() {
            const selection = mediaFrame.state().get('selection');
            const slides = [];
            selection.each(function(model) {
                slides.push(attachmentToSlide(model.toJSON()));
            });

            const cellIndex = currentCellIndex;
            const append = appendToCell;
            currentCellIndex = null;
            appendToCell = false;

            if (!slides.length || cellIndex === null) {
                return;
            }

            // Defer so the media modal can finish closing before we touch
            // the DOM.
            setTimeout(function() {
                if (append && gridData[cellIndex]) {
                    gridData[cellIndex].media = (gridData[cellIndex].media || []).concat(slides);
                } else {
                    gridData[cellIndex] = {
                        media: slides,
                        caption: '',
                        likes: 0,
                        comments: 0,
                        link_url: ''
                    };
                }
                updateCellDisplay(cellIndex);
                if (activeModalIndex === cellIndex) {
                    refreshModalSlides();
                }
            }, 0);
        });

        return mediaFrame;
    }

    function openMediaLibrary(cellIndex, append) {
        currentCellIndex = cellIndex;
        appendToCell = !!append;
        getMediaFrame().open();
    }

    function getCellByIndex(index) {
        return document.querySelectorAll('.igp-grid-cell')[index] || null;
    }

    function updateGridDisplay() {
        const cells = document.querySelectorAll('.igp-grid-cell');
        const columns = gridConfig.columns;
        cells.forEach(function(cell, index) {
            cell.setAttribute('data-index', index);
            cell.setAttribute('data-row', Math.floor(index / columns));
            cell.setAttribute('data-col', index % columns);
            renderCell(cell, index);
        });
    }

    function updateCellDisplay(index) {
        const cell = getCellByIndex(index);
        if (cell) renderCell(cell, index);
    }

    function renderCell(cell, index) {
        const post = gridData[index];
        const media = post && post.media ? post.media : [];

        if (post && media.length) {
            cell.classList.add('has-image');
            cell.removeAttribute('title');

            const first = media[0];
            const thumb = first.thumbnail_url || '';
            let html = '';
            if (thumb) {
                html += '<img src="' + escapeHtml(thumb) + '" alt="' + escapeHtml(first.alt || '') + '" />';
            } else {
                html += '<span class="igp-cell-media-placeholder">' + IGP_ICONS.video + '</span>';
            }

            const badge = buildBadge(effectiveMediaType(post));
            if (badge) html += badge;

            // Carousel count bubble
            if (media.length > 1) {
                html += '<span class="igp-cell-count"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="3" width="14" height="14" rx="2"/><path d="M17 21H5a2 2 0 0 1-2-2V7"/></svg>' + media.length + '</span>';
            }

            const likes = parseInt(post.likes, 10) || 0;
            const comments = parseInt(post.comments, 10) || 0;
            if (likes > 0 || comments > 0) {
                html += '<span class="igp-cell-overlay">' +
                    '<span class="igp-cell-stat">' + IGP_ICONS.heart + '<span>' + formatCount(likes) + '</span></span>' +
                    '<span class="igp-cell-stat">' + IGP_ICONS.comment + '<span>' + formatCount(comments) + '</span></span>' +
                    '</span>';
            }

            if (post.link_url) {
                html += '<span class="igp-cell-linkbadge" title="' + escapeHtml(post.link_url) + '">' + IGP_ICONS.link + '</span>';
            }

            html += '<div class="igp-cell-actions">' +
                '<button type="button" class="igp-cell-action igp-remove-image" title="Remove post">' + IGP_ICONS.trash + '</button>' +
                '</div>';

            cell.innerHTML = html;

            const removeBtn = cell.querySelector('.igp-remove-image');
            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    removeImage(index);
                });
            }
        } else {
            cell.classList.remove('has-image');
            cell.innerHTML = '<div class="igp-cell-placeholder">' + IGP_ICONS.plus + '<span>Add media</span></div>';
        }
    }

    function buildBadge(type) {
        if (type === 'carousel') return '<span class="igp-badge">' + IGP_ICONS.carousel + '</span>';
        if (type === 'reel') return '<span class="igp-badge">' + IGP_ICONS.reel + '</span>';
        if (type === 'video') return '<span class="igp-badge">' + IGP_ICONS.video + '</span>';
        return '';
    }

    function removeImage(index) {
        delete gridData[index];
        updateCellDisplay(index);
    }

    /**
     * Open the post settings modal (slides, caption, engagement).
     */
    function openPostSettings(index) {
        const post = gridData[index];
        if (!post) return;

        activeModalIndex = index;

        const $modal = $(`
            <div class="igp-modal-backdrop">
                <div class="igp-modal igp-modal--post" role="dialog" aria-modal="true" aria-label="Post settings">
                    <div class="igp-modal-header">
                        <h2>Post</h2>
                        <button type="button" class="igp-modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="igp-modal-body">
                        <div class="igp-slides" data-slides></div>
                        <button type="button" class="button igp-add-media">Add media</button>
                        <label class="igp-field">
                            <span>Caption</span>
                            <textarea class="igp-field-caption" rows="3"></textarea>
                        </label>
                        <label class="igp-field">
                            <span>Media type</span>
                            <select class="igp-field-media">
                                <option value="">Auto</option>
                                <option value="photo">Photo</option>
                                <option value="carousel">Carousel</option>
                                <option value="reel">Reel</option>
                                <option value="video">Video</option>
                            </select>
                        </label>
                        <div class="igp-field-row">
                            <label class="igp-field">
                                <span>Likes</span>
                                <input type="number" min="0" step="1" class="igp-field-likes">
                            </label>
                            <label class="igp-field">
                                <span>Comments</span>
                                <input type="number" min="0" step="1" class="igp-field-comments">
                            </label>
                        </div>
                        <label class="igp-field">
                            <span>Link URL</span>
                            <input type="url" class="igp-field-link" placeholder="https://example.com">
                        </label>
                    </div>
                    <div class="igp-modal-footer">
                        <button type="button" class="button igp-modal-remove">Remove post</button>
                        <div class="igp-modal-footer-right">
                            <button type="button" class="button igp-modal-cancel">Cancel</button>
                            <button type="button" class="button button-primary igp-modal-save">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $modal.find('.igp-field-caption').val(post.caption || '');
        $modal.find('.igp-field-media').val(post.media_type || '');
        $modal.find('.igp-field-likes').val(parseInt(post.likes, 10) || 0);
        $modal.find('.igp-field-comments').val(parseInt(post.comments, 10) || 0);
        $modal.find('.igp-field-link').val(post.link_url || '');

        $('body').append($modal);
        refreshModalSlides();

        function closeModal() {
            $(document).off('keyup.igpModal');
            activeModalIndex = null;
            $modal.remove();
        }

        $modal.on('click', function(e) {
            if (e.target === $modal[0]) closeModal();
        });
        $modal.find('.igp-modal-close, .igp-modal-cancel').on('click', closeModal);
        $modal.find('.igp-add-media').on('click', function() {
            openMediaLibrary(activeModalIndex, true);
        });
        $modal.find('.igp-modal-remove').on('click', function() {
            removeImage(index);
            closeModal();
        });
        $modal.find('.igp-modal-save').on('click', function() {
            const cell = gridData[index] || { media: [] };
            cell.caption = $modal.find('.igp-field-caption').val();
            const media = $modal.find('.igp-field-media').val();
            const likes = Math.max(0, parseInt($modal.find('.igp-field-likes').val(), 10) || 0);
            const comments = Math.max(0, parseInt($modal.find('.igp-field-comments').val(), 10) || 0);
            const link = $modal.find('.igp-field-link').val().trim();

            if (media) { cell.media_type = media; } else { delete cell.media_type; }
            if (likes > 0) { cell.likes = likes; } else { delete cell.likes; }
            if (comments > 0) { cell.comments = comments; } else { delete cell.comments; }
            if (link) { cell.link_url = link; } else { delete cell.link_url; }

            gridData[index] = cell;
            updateCellDisplay(index);
            closeModal();
        });

        $(document).on('keyup.igpModal', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    }

    function refreshModalSlides() {
        if (activeModalIndex === null) return;
        const $slides = $('.igp-modal--post [data-slides]');
        const post = gridData[activeModalIndex];
        if (!$slides.length || !post) return;

        $slides.empty();
        (post.media || []).forEach(function(slide, i) {
            const $item = $('<div class="igp-slide-item"></div>');

            if (slide.thumbnail_url) {
                $item.append('<img src="' + escapeHtml(slide.thumbnail_url) + '" alt="">');
            } else {
                $item.append('<span class="igp-slide-empty">' + (slide.type === 'video' ? IGP_ICONS.video : IGP_ICONS.plus) + '</span>');
            }

            if (slide.type === 'video') {
                $item.append('<button type="button" class="igp-slide-poster" data-slide="' + i + '" title="Set poster image">' + IGP_ICONS.camera + '</button>');
            }

            $item.append('<button type="button" class="igp-slide-remove" data-slide="' + i + '" aria-label="Remove">&times;</button>');
            $slides.append($item);
        });

        $slides.off('click', '.igp-slide-remove').on('click', '.igp-slide-remove', function() {
            const slideIndex = parseInt($(this).data('slide'), 10);
            const current = gridData[activeModalIndex];
            current.media.splice(slideIndex, 1);
            if (!current.media.length) {
                removeImage(activeModalIndex);
                $('.igp-modal-backdrop').remove();
                activeModalIndex = null;
                return;
            }
            updateCellDisplay(activeModalIndex);
            refreshModalSlides();
        });

        $slides.off('click', '.igp-slide-poster').on('click', '.igp-slide-poster', function() {
            openPosterPicker(activeModalIndex, parseInt($(this).data('slide'), 10));
        });
    }

    /**
     * Return the shared poster frame, creating it once.
     */
    function getPosterFrame() {
        if (posterFrame) {
            return posterFrame;
        }

        posterFrame = wp.media({
            title: 'Select poster image',
            button: { text: 'Use as poster' },
            multiple: false,
            library: { type: 'image' }
        });

        posterFrame.on('select', function() {
            const att = posterFrame.state().get('selection').first().toJSON();
            const target = posterTarget;
            posterTarget = null;

            let url = att.url;
            if (att.sizes) {
                url = (att.sizes.large || att.sizes.medium_large || att.sizes.medium || att.sizes.thumbnail || {}).url || att.url;
            }

            // Defer so the media modal can finish closing before we touch
            // the DOM.
            setTimeout(function() {
                if (!target) {
                    return;
                }
                const post = gridData[target.cell];
                if (post && post.media && post.media[target.slide]) {
                    post.media[target.slide].thumbnail_url = url;
                    post.media[target.slide].poster_id = att.id;
                    updateCellDisplay(target.cell);
                    refreshModalSlides();
                }
            }, 0);
        });

        return posterFrame;
    }

    /**
     * Choose a poster image for a video slide.
     */
    function openPosterPicker(cellIndex, slideIndex) {
        const post = gridData[cellIndex];
        if (!post || !post.media || !post.media[slideIndex]) {
            return;
        }

        posterTarget = { cell: cellIndex, slide: slideIndex };
        getPosterFrame().open();
    }

    // ---------- Avatar picker ----------
    function updateAvatarPreview(url) {
        const $preview = $('#igp-avatar-preview');
        if (url) {
            $preview.removeClass('is-empty').html('<img src="' + escapeHtml(url) + '" alt="">');
            $('#igp-remove-avatar').show();
        } else {
            $preview.addClass('is-empty').html('<span class="dashicons dashicons-format-image"></span>');
            $('#igp-remove-avatar').hide();
        }
    }

    function bindEvents() {
        $('#grid-columns, #grid-rows, #grid-aspect-ratio').on('change', function() {
            syncConfigFromDOM();
            generateGrid();
        });

        $('#igp-grid-form').on('submit', function(e) {
            e.preventDefault();
            saveGrid();
        });

        // Copy profile URL
        $(document).on('click', '.igp-copy-url', function() {
            const $btn = $(this);
            navigator.clipboard.writeText($btn.data('url')).then(function() {
                const original = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() { $btn.text(original); }, 2000);
            });
        });

        $(document).on('click', '.igp-add-row-above', function(e) {
            e.preventDefault();
            addRow(parseInt($(this).data('row')));
        });

        $(document).on('click', '.igp-add-row-below', function(e) {
            e.preventDefault();
            addRow(parseInt($(this).data('row')) + 1);
        });

        // Avatar
        $('#igp-select-avatar').on('click', function() {
            if (!avatarFrame) {
                avatarFrame = wp.media({
                    title: 'Select avatar',
                    button: { text: 'Use image' },
                    multiple: false,
                    library: { type: 'image' }
                });
                avatarFrame.on('select', function() {
                    const att = avatarFrame.state().get('selection').first().toJSON();
                    let url = att.url;
                    if (att.sizes) {
                        url = (att.sizes.medium || att.sizes.thumbnail || {}).url || att.url;
                    }
                    setTimeout(function() {
                        $('#igp-profile-avatar-url').val(url);
                        updateAvatarPreview(url);
                    }, 0);
                });
            }
            avatarFrame.open();
        });

        $('#igp-remove-avatar').on('click', function() {
            $('#igp-profile-avatar-url').val('');
            updateAvatarPreview('');
        });
    }

    function addRow(position) {
        if (isRegeneratingGrid) return;

        const columns = gridConfig.columns;
        const newGridData = {};

        Object.keys(gridData).forEach(function(key) {
            const oldIndex = parseInt(key);
            const oldRow = Math.floor(oldIndex / columns);
            const oldCol = oldIndex % columns;
            let newIndex;
            if (oldRow >= position) {
                newIndex = (oldRow + 1) * columns + oldCol;
            } else {
                newIndex = oldIndex;
            }
            newGridData[newIndex] = Object.assign({}, gridData[key]);
        });

        gridData = newGridData;
        gridConfig.rows = gridConfig.rows + 1;
        syncConfigToDOM();
        generateGrid();
    }

    function collectProfileData() {
        return {
            username: $('#igp-profile-username').val() || '',
            display_name: $('#igp-profile-display-name').val() || '',
            bio: $('#igp-profile-bio').val() || '',
            website: $('#igp-profile-website').val() || '',
            avatar_url: $('#igp-profile-avatar-url').val() || '',
            followers: parseInt($('#igp-profile-followers').val(), 10) || 0,
            following: parseInt($('#igp-profile-following').val(), 10) || 0
        };
    }

    function saveGrid() {
        const $form = $('#igp-grid-form');
        const $submitBtn = $('#submit');

        const name = $('#grid-name').val().trim();
        if (!name) {
            alert('Please enter an internal name.');
            return;
        }

        syncConfigFromDOM();

        const formData = {
            action: 'igp_save_grid',
            nonce: igp_ajax.nonce,
            grid_id: $('#grid-id').val(),
            name: name,
            description: $('#grid-description').val(),
            columns: gridConfig.columns,
            rows: gridConfig.rows,
            aspect_ratio: gridConfig.aspectRatio,
            grid_data: JSON.stringify(gridData),
            profile_data: JSON.stringify(collectProfileData())
        };

        $submitBtn.prop('disabled', true).val('Saving...');
        $form.addClass('igp-loading');
        $('#igp-grid-editor').addClass('igp-loading-overlay');

        $.post(igp_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    if (response.data.grid_id) {
                        const id = response.data.grid_id;
                        const url = window.igpProfileBase + id + '/';
                        $('#grid-id').val(id);
                        $('#igp-profile-url').text(url);
                        $('.igp-copy-url').data('url', url).attr('data-url', url);
                        $('#igp-profile-url-display').show();
                        $('#igp-view-profile').attr('href', url).show();

                        if (!window.igpIsEdit) {
                            window.igpIsEdit = true;
                            const newUrl = window.location.href + '&grid_id=' + id;
                            window.history.replaceState({}, '', newUrl);
                        }
                    }
                } else {
                    showMessage(response.data.message || igp_ajax.strings.error_occurred, 'error');
                }
            })
            .fail(function() {
                showMessage(igp_ajax.strings.error_occurred, 'error');
            })
            .always(function() {
                $submitBtn.prop('disabled', false).val($('#grid-id').val() ? 'Update Profile' : 'Save Profile');
                $form.removeClass('igp-loading');
                $('#igp-grid-editor').removeClass('igp-loading-overlay');
            });
    }

    function showMessage(message, type) {
        $('.igp-message').remove();
        const $message = $('<div class="igp-message ' + type + '"></div>').text(message);
        $message.insertAfter('.wrap h1');
        setTimeout(function() { $message.fadeOut(); }, 5000);
    }

})(jQuery);
