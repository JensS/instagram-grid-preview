/**
 * Admin JavaScript for Instagram Grid Preview
 *
 * The editor mirrors Instagram's profile grid: square tiles, thin gaps,
 * corner media badges and a dark hover overlay with like/comment counts.
 */

(function($) {
    'use strict';

    let gridData = {};
    let sortableInstance = null;
    let mediaFrame = null;
    let currentCellIndex = null;
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
        pencil: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>',
        link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>'
    };

    /**
     * Escape HTML (including quotes) to prevent XSS attacks
     * @param {string} text - Text to escape
     * @return {string} Escaped HTML
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

    /**
     * Add thousands separators to a number.
     * @param {number} n
     * @return {string}
     */
    function commaNumber(n) {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Format a count the way Instagram does (commas under 10k, K/M above).
     * @param {number} count
     * @return {string}
     */
    function formatCount(count) {
        const n = Math.max(0, parseInt(count, 10) || 0);
        if (n < 10000) {
            return commaNumber(n);
        }
        if (n < 1000000) {
            return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    }

    $(document).ready(function() {
        // Initialize grid config from DOM inputs
        syncConfigFromDOM();

        initializeGridEditor();
        bindEvents();

        // Load existing grid data if editing
        if (window.igpGridData && window.igpIsEdit) {
            gridData = window.igpGridData;
            updateGridDisplay();
        }
    });

    /**
     * Sync grid config from DOM inputs to state object
     */
    function syncConfigFromDOM() {
        gridConfig.columns = parseInt($('#grid-columns').val()) || 3;
        gridConfig.rows = parseInt($('#grid-rows').val()) || 3;
        gridConfig.aspectRatio = $('#grid-aspect-ratio').val() || '1:1';
    }

    /**
     * Sync grid config from state object to DOM inputs (without triggering events)
     */
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
        // Prevent recursive calls
        if (isRegeneratingGrid) {
            return;
        }
        isRegeneratingGrid = true;

        const container = document.getElementById('igp-grid-editor');
        const columns = gridConfig.columns;
        const rows = gridConfig.rows;
        const aspectRatio = gridConfig.aspectRatio;

        // Update grid CSS
        container.setAttribute('data-columns', columns);
        container.setAttribute('data-rows', rows);
        container.setAttribute('data-aspect-ratio', aspectRatio);

        // Clear existing cells
        container.innerHTML = '';

        // Generate cells
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
                const cell = createGridCell(cellIndex, row, col);
                cellsInRow.appendChild(cell);
            }
            rowContainer.appendChild(cellsInRow);
            container.appendChild(rowContainer);
        }

        // Reinitialize sortable - destroy all existing instances first
        destroySortable();
        initializeSortable();

        // Update display with existing data
        updateGridDisplay();

        // Clear the flag
        isRegeneratingGrid = false;
    }

    function createGridCell(index, row, col) {
        const cell = document.createElement('div');
        cell.className = 'igp-grid-cell';
        cell.setAttribute('data-index', index);
        cell.setAttribute('data-row', row);
        cell.setAttribute('data-col', col);

        // Add click event for media selection
        cell.addEventListener('click', function(e) {
            if (e.target.closest('.igp-cell-action')) {
                return; // Don't open media library when clicking an action button
            }
            openMediaLibrary(index);
        });

        return cell;
    }

    function destroySortable() {
        if (!sortableInstance) {
            return;
        }
        if (Array.isArray(sortableInstance)) {
            sortableInstance.forEach(function(instance) {
                if (instance && typeof instance.destroy === 'function') {
                    instance.destroy();
                }
            });
        } else if (typeof sortableInstance.destroy === 'function') {
            sortableInstance.destroy();
        }
        sortableInstance = null;
    }

    function initializeSortable() {
        // Initialize sortable for each row instead of the entire grid
        const rows = document.querySelectorAll('.igp-cells-in-row');

        if (typeof Sortable === 'undefined') {
            return;
        }

        destroySortable();
        sortableInstance = [];

        rows.forEach(function(row) {
            const instance = Sortable.create(row, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                group: 'grid-cells', // Allow dragging between rows
                filter: function(evt, item) {
                    // Only allow dragging cells that have images, and never
                    // start a drag from an action button.
                    if (!item.classList.contains('has-image')) {
                        return true;
                    }
                    if (evt.target && evt.target.closest && evt.target.closest('.igp-cell-action')) {
                        return true;
                    }
                    return false;
                },
                onStart: function(evt) {
                    evt.item.style.cursor = 'grabbing';
                },
                onEnd: function(evt) {
                    const oldRowIndex = parseInt(evt.from.closest('.igp-grid-row').getAttribute('data-row-index'));
                    const newRowIndex = parseInt(evt.to.closest('.igp-grid-row').getAttribute('data-row-index'));
                    const oldColIndex = evt.oldIndex;
                    const columns = gridConfig.columns;
                    // Dropping at the far edge of a row can report an index
                    // equal to the column count; clamp it to the last column.
                    const newColIndex = Math.min(evt.newIndex, columns - 1);

                    const oldAbsIndex = oldRowIndex * columns + oldColIndex;
                    const newAbsIndex = newRowIndex * columns + newColIndex;

                    evt.item.style.cursor = '';

                    if (oldAbsIndex !== newAbsIndex) {
                        const oldItem = gridData[oldAbsIndex];
                        const newItem = gridData[newAbsIndex];

                        // Swap the two positions
                        if (oldItem) {
                            gridData[newAbsIndex] = oldItem;
                        } else {
                            delete gridData[newAbsIndex];
                        }

                        if (newItem) {
                            gridData[oldAbsIndex] = newItem;
                        } else {
                            delete gridData[oldAbsIndex];
                        }
                    }

                    // Rebuild the grid so every row always has exactly
                    // `columns` cells, regardless of how items were moved.
                    setTimeout(function() {
                        generateGrid();
                    }, 0);
                }
            });

            sortableInstance.push(instance);
        });
    }

    function openMediaLibrary(cellIndex) {
        currentCellIndex = cellIndex;

        // Create media frame if it doesn't exist
        if (!mediaFrame) {
            mediaFrame = wp.media({
                title: 'Select Image for Grid',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // Handle image selection
            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();

                if (currentCellIndex !== null) {
                    const existing = gridData[currentCellIndex] || {};
                    gridData[currentCellIndex] = {
                        image_id: attachment.id,
                        image_url: attachment.url,
                        thumbnail_url: attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
                        image_alt: attachment.alt || attachment.title || ''
                    };
                    // Preserve any Instagram metadata already set on the cell
                    ['link_url', 'media_type', 'likes', 'comments'].forEach(function(key) {
                        if (existing[key] !== undefined) {
                            gridData[currentCellIndex][key] = existing[key];
                        }
                    });

                    updateCellDisplay(currentCellIndex);
                    currentCellIndex = null;
                }
            });
        }

        mediaFrame.open();
    }

    /**
     * Get a cell element by its grid position (row-major order).
     * @param {number} index
     * @return {Element|null}
     */
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
        if (cell) {
            renderCell(cell, index);
        }
    }

    function renderCell(cell, index) {
        const data = gridData[index];

        if (data && data.image_url) {
            cell.classList.add('has-image');
            cell.removeAttribute('title');
            cell.innerHTML = buildCellInner(data) + buildCellActions();
            bindCellActions(cell, index);
        } else {
            cell.classList.remove('has-image');
            cell.innerHTML = '<div class="igp-cell-placeholder">' + IGP_ICONS.plus + '<span>Add image</span></div>';
        }
    }

    function buildCellInner(data) {
        const thumb = escapeHtml(data.thumbnail_url || data.image_url);
        const alt = escapeHtml(data.image_alt || '');

        let html = '<img src="' + thumb + '" alt="' + alt + '" />';

        const badge = buildBadge(data.media_type);
        if (badge) {
            html += badge;
        }

        const likes = parseInt(data.likes, 10) || 0;
        const comments = parseInt(data.comments, 10) || 0;
        if (likes > 0 || comments > 0) {
            html += '<span class="igp-cell-overlay">' +
                '<span class="igp-cell-stat">' + IGP_ICONS.heart + '<span>' + formatCount(likes) + '</span></span>' +
                '<span class="igp-cell-stat">' + IGP_ICONS.comment + '<span>' + formatCount(comments) + '</span></span>' +
                '</span>';
        }

        if (data.link_url) {
            html += '<span class="igp-cell-linkbadge" title="' + escapeHtml(data.link_url) + '">' + IGP_ICONS.link + '</span>';
        }

        return html;
    }

    function buildBadge(mediaType) {
        if (mediaType === 'carousel') {
            return '<span class="igp-badge igp-badge--carousel">' + IGP_ICONS.carousel + '</span>';
        }
        if (mediaType === 'reel') {
            return '<span class="igp-badge igp-badge--reel">' + IGP_ICONS.reel + '</span>';
        }
        if (mediaType === 'video') {
            return '<span class="igp-badge igp-badge--video">' + IGP_ICONS.video + '</span>';
        }
        return '';
    }

    function buildCellActions() {
        return '<div class="igp-cell-actions">' +
            '<button type="button" class="igp-cell-action igp-edit-cell" title="Post settings">' + IGP_ICONS.pencil + '</button>' +
            '<button type="button" class="igp-cell-action igp-remove-image" title="Remove image">' + IGP_ICONS.trash + '</button>' +
            '</div>';
    }

    function bindCellActions(cell, index) {
        const editBtn = cell.querySelector('.igp-edit-cell');
        if (editBtn) {
            editBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                openPostSettings(index);
            });
        }

        const removeBtn = cell.querySelector('.igp-remove-image');
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                removeImage(index);
            });
        }
    }

    function removeImage(index) {
        delete gridData[index];
        updateCellDisplay(index);
    }

    /**
     * Open the Instagram-style "post settings" modal for a cell.
     * @param {number} index
     */
    function openPostSettings(index) {
        const data = gridData[index];
        if (!data) {
            return;
        }

        const $modal = $(`
            <div class="igp-modal-backdrop">
                <div class="igp-modal" role="dialog" aria-modal="true" aria-label="Post settings">
                    <div class="igp-modal-header">
                        <h2>Post settings</h2>
                        <button type="button" class="igp-modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="igp-modal-body">
                        <label class="igp-field">
                            <span>Link URL</span>
                            <input type="url" class="igp-field-link" placeholder="https://example.com">
                        </label>
                        <label class="igp-field">
                            <span>Media type</span>
                            <select class="igp-field-media">
                                <option value="">Photo</option>
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
                    </div>
                    <div class="igp-modal-footer">
                        <button type="button" class="button igp-modal-remove">Remove image</button>
                        <div class="igp-modal-footer-right">
                            <button type="button" class="button igp-modal-cancel">Cancel</button>
                            <button type="button" class="button button-primary igp-modal-save">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        // Populate with the current values using .val() so user content is
        // never interpolated into the markup.
        $modal.find('.igp-field-link').val(data.link_url || '');
        $modal.find('.igp-field-media').val(data.media_type || '');
        $modal.find('.igp-field-likes').val(parseInt(data.likes, 10) || 0);
        $modal.find('.igp-field-comments').val(parseInt(data.comments, 10) || 0);

        function closeModal() {
            $(document).off('keyup.igpModal');
            $modal.remove();
        }

        $modal.on('click', function(e) {
            if (e.target === $modal[0]) {
                closeModal();
            }
        });
        $modal.find('.igp-modal-close, .igp-modal-cancel').on('click', closeModal);
        $modal.find('.igp-modal-remove').on('click', function() {
            removeImage(index);
            closeModal();
        });
        $modal.find('.igp-modal-save').on('click', function() {
            const link = $modal.find('.igp-field-link').val().trim();
            const media = $modal.find('.igp-field-media').val();
            const likes = Math.max(0, parseInt($modal.find('.igp-field-likes').val(), 10) || 0);
            const comments = Math.max(0, parseInt($modal.find('.igp-field-comments').val(), 10) || 0);

            const cell = gridData[index] || {};

            if (link) {
                cell.link_url = link;
            } else {
                delete cell.link_url;
            }

            if (media) {
                cell.media_type = media;
            } else {
                delete cell.media_type;
            }

            if (likes > 0) {
                cell.likes = likes;
            } else {
                delete cell.likes;
            }

            if (comments > 0) {
                cell.comments = comments;
            } else {
                delete cell.comments;
            }

            gridData[index] = cell;
            updateCellDisplay(index);
            closeModal();
        });

        $(document).on('keyup.igpModal', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        $('body').append($modal);
        $modal.find('.igp-field-link').trigger('focus');
    }

    function bindEvents() {
        // Grid dimension changes - update state first, then regenerate
        $('#grid-columns, #grid-rows, #grid-aspect-ratio').on('change', function() {
            syncConfigFromDOM();
            generateGrid();
        });

        // Form submission
        $('#igp-grid-form').on('submit', function(e) {
            e.preventDefault();
            saveGrid();
        });

        // Copy shortcode
        $(document).on('click', '.igp-copy-shortcode', function() {
            const $btn = $(this);
            const shortcode = $btn.data('shortcode');
            navigator.clipboard.writeText(shortcode).then(function() {
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
        });

        // Row management buttons (add above/below)
        $(document).on('click', '.igp-add-row-above', function(e) {
            e.preventDefault();
            const rowIndex = parseInt($(this).data('row'));
            addRow(rowIndex);
        });

        $(document).on('click', '.igp-add-row-below', function(e) {
            e.preventDefault();
            const rowIndex = parseInt($(this).data('row'));
            addRow(rowIndex + 1);
        });

        // Right-click a filled cell to open its post settings
        $(document).on('contextmenu', '.igp-grid-cell.has-image', function(e) {
            e.preventDefault();
            openPostSettings($(this).data('index'));
        });
    }

    function addRow(position) {
        // Prevent any issues during regeneration
        if (isRegeneratingGrid) {
            return;
        }

        const columns = gridConfig.columns;
        const currentRows = gridConfig.rows;

        // Create new grid data object
        const newGridData = {};

        // Copy existing grid data, shifting rows at or after the insertion position
        Object.keys(gridData).forEach(function(key) {
            const oldIndex = parseInt(key);
            const oldRow = Math.floor(oldIndex / columns);
            const oldCol = oldIndex % columns;

            let newIndex;
            if (oldRow >= position) {
                // Shift down by one row
                const newRow = oldRow + 1;
                newIndex = newRow * columns + oldCol;
            } else {
                // Keep in same position
                newIndex = oldIndex;
            }

            // Copy the data to the new position (deep copy to prevent reference issues)
            newGridData[newIndex] = Object.assign({}, gridData[key]);
        });

        // Update the global grid data
        gridData = newGridData;

        // Update the grid config state
        gridConfig.rows = currentRows + 1;

        // Sync the new value to DOM (won't trigger change event during regeneration)
        syncConfigToDOM();

        // Regenerate the grid display with the new row count and updated data
        generateGrid();
    }

    function saveGrid() {
        const $form = $('#igp-grid-form');
        const $submitBtn = $('#submit');

        // Validate form
        const name = $('#grid-name').val().trim();
        if (!name) {
            alert('Please enter a grid name.');
            return;
        }

        // Sync config from DOM one more time before saving
        syncConfigFromDOM();

        // Prepare data
        const formData = {
            action: 'igp_save_grid',
            nonce: igp_ajax.nonce,
            grid_id: $('#grid-id').val(),
            name: name,
            description: $('#grid-description').val(),
            columns: gridConfig.columns,
            rows: gridConfig.rows,
            aspect_ratio: gridConfig.aspectRatio,
            grid_data: JSON.stringify(gridData)
        };

        // Show loading state
        $submitBtn.prop('disabled', true).val('Saving...');
        $form.addClass('igp-loading');
        $('#igp-grid-editor').addClass('igp-loading-overlay');

        // Send AJAX request
        $.post(igp_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');

                    // Update grid ID if this was a new grid
                    if (response.data.grid_id && !$('#grid-id').val()) {
                        $('#grid-id').val(response.data.grid_id);

                        // Update URL and show shortcode
                        const newUrl = window.location.href + '&grid_id=' + response.data.grid_id;
                        window.history.replaceState({}, '', newUrl);

                        // Add shortcode section
                        addShortcodeSection(response.data.grid_id);
                    }
                } else {
                    showMessage(response.data.message || igp_ajax.strings.error_occurred, 'error');
                }
            })
            .fail(function() {
                showMessage(igp_ajax.strings.error_occurred, 'error');
            })
            .always(function() {
                $submitBtn.prop('disabled', false).val($('#grid-id').val() ? 'Update Grid' : 'Save Grid');
                $form.removeClass('igp-loading');
                $('#igp-grid-editor').removeClass('igp-loading-overlay');
            });
    }

    function showMessage(message, type) {
        // Remove existing messages
        $('.igp-message').remove();

        // Create new message
        const $message = $('<div class="igp-message ' + type + '">' + message + '</div>');
        $message.insertAfter('.wrap h1');

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut();
        }, 5000);
    }

    function addShortcodeSection(gridId) {
        if ($('.igp-shortcode-display').length === 0) {
            const shortcodeHtml = `
                <div class="igp-shortcode-display">
                    <h3>Shortcode</h3>
                    <p>Use this shortcode to display the grid on your site:</p>
                    <code>[instagram_grid id="${escapeHtml(gridId.toString())}"]</code>
                    <button type="button" class="button button-small igp-copy-shortcode" data-shortcode='[instagram_grid id="${escapeHtml(gridId.toString())}"]'>
                        Copy Shortcode
                    </button>
                </div>
            `;
            $(shortcodeHtml).insertAfter('#igp-grid-form');
        }
    }

})(jQuery);
