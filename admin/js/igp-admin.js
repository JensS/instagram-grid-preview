/**
 * Admin JavaScript for Instagram Grid Preview
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

    /**
     * Escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @return {string} Escaped HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
            cellsInRow.style.display = 'grid';
            cellsInRow.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
            cellsInRow.style.gap = '5px';
            cellsInRow.style.width = '100%';

            for (let col = 0; col < columns; col++) {
                const cellIndex = row * columns + col;
                const cell = createGridCell(cellIndex, row, col);
                cellsInRow.appendChild(cell);
            }
            rowContainer.appendChild(cellsInRow);
            container.appendChild(rowContainer);
        }
        
        // Reinitialize sortable - destroy all existing instances first
        if (sortableInstance) {
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
        
        const placeholder = document.createElement('div');
        placeholder.className = 'igp-cell-placeholder';
        placeholder.textContent = 'Click to add image';
        
        cell.appendChild(placeholder);
        
        // Add click event for media selection
        cell.addEventListener('click', function(e) {
            if (e.target.classList.contains('igp-remove-image')) {
                return; // Don't open media library when clicking remove button
            }
            openMediaLibrary(index);
        });
        
        return cell;
    }

    function initializeSortable() {
        // Initialize sortable for each row instead of the entire grid
        const rows = document.querySelectorAll('.igp-cells-in-row');
        
        if (typeof Sortable !== 'undefined') {
            // Destroy existing sortable instances if they exist
            if (sortableInstance) {
                if (Array.isArray(sortableInstance)) {
                    sortableInstance.forEach(instance => instance.destroy());
                } else {
                    sortableInstance.destroy();
                }
            }
            
            // Create sortable instances for each row
            sortableInstance = [];
            
            rows.forEach((row, rowIndex) => {
                const instance = Sortable.create(row, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    group: 'grid-cells', // Allow dragging between rows
                    filter: function(evt, item, originalEvent) {
                        // Only allow dragging cells that have images
                        return !item.classList.contains('has-image');
                    },
                    onStart: function(evt) {
                        // Add visual feedback when dragging starts
                        evt.item.style.cursor = 'grabbing';
                    },
                    onEnd: function(evt) {
                        // Get the new and old positions
                        const oldRowIndex = parseInt(evt.from.closest('.igp-grid-row').getAttribute('data-row-index'));
                        const newRowIndex = parseInt(evt.to.closest('.igp-grid-row').getAttribute('data-row-index'));
                        const oldColIndex = evt.oldIndex;
                        const newColIndex = evt.newIndex;
                        const columns = gridConfig.columns;
                        
                        // Calculate absolute indices
                        const oldAbsIndex = oldRowIndex * columns + oldColIndex;
                        const newAbsIndex = newRowIndex * columns + newColIndex;
                        
                        // Reset cursor
                        evt.item.style.cursor = '';
                        
                        // Always update the data structure, even if moved within the same row
                        if (oldAbsIndex !== newAbsIndex) {
                            const item = gridData[oldAbsIndex];

                            // Create a complete representation of the grid
                            const tempArray = [];
                            const totalCells = columns * gridConfig.rows;
                            
                            // Fill with existing data or null
                            for (let i = 0; i < totalCells; i++) {
                                tempArray.push(gridData[i] || null);
                            }
                            
                            // Remove from old position
                            tempArray.splice(oldAbsIndex, 1, null);
                            
                            // Insert at new position
                            tempArray.splice(newAbsIndex, 0, item);
                            
                            // Remove the extra null that was pushed down
                            tempArray.pop();
                            
                            // Rebuild gridData object, skipping nulls
                            gridData = {};
                            tempArray.forEach((item, index) => {
                                if (item !== null) {
                                    gridData[index] = item;
                                }
                            });
                            
                            // Update all cells
                            updateGridDisplay();
                        }
                    }
                });
                
                sortableInstance.push(instance);
            });
        }
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
                    gridData[currentCellIndex] = {
                        image_id: attachment.id,
                        image_url: attachment.url,
                        thumbnail_url: attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
                        image_alt: attachment.alt || attachment.title || ''
                    };
                    
                    updateCellDisplay(currentCellIndex);
                    currentCellIndex = null;
                }
            });
        }
        
        mediaFrame.open();
    }

    function updateGridDisplay() {
        const cells = document.querySelectorAll('.igp-grid-cell');
        
        cells.forEach(function(cell, index) {
            updateCellDisplay(index);
        });
    }

    function updateCellDisplay(index) {
        const cell = document.querySelector(`[data-index="${index}"]`);
        if (!cell) return;

        const data = gridData[index];

        if (data && data.image_url) {
            // Has image
            cell.classList.add('has-image');
            let cellContent = `
                <img src="${escapeHtml(data.thumbnail_url || data.image_url)}" alt="${escapeHtml(data.image_alt || '')}" />
                <button type="button" class="igp-remove-image" data-index="${escapeHtml(index.toString())}">×</button>
            `;

            // Add URL indicator if image has a link
            if (data.link_url) {
                cellContent += `
                    <div class="igp-link-indicator" title="Linked to: ${escapeHtml(data.link_url)}">
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                `;
            }

            cell.innerHTML = cellContent;

            // Add tooltip with instructions
            cell.title = 'Right-click to add/edit URL link';

            // Add remove button event
            const removeBtn = cell.querySelector('.igp-remove-image');
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                removeImage(index);
            });
        } else {
            // No image
            cell.classList.remove('has-image');
            cell.innerHTML = '<div class="igp-cell-placeholder">Click to add image</div>';
        }
    }

    function removeImage(index) {
        delete gridData[index];
        updateCellDisplay(index);
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
            const shortcode = $(this).data('shortcode');
            navigator.clipboard.writeText(shortcode).then(function() {
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            }.bind(this));
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

        // Right-click context menu for URL linking
        $(document).on('contextmenu', '.igp-grid-cell.has-image', function(e) {
            e.preventDefault();
            const cellIndex = $(this).data('index');
            promptForImageUrl(cellIndex);
        });
    }
    
    function promptForImageUrl(cellIndex) {
        // Get current URL if exists
        const currentUrl = gridData[cellIndex] && gridData[cellIndex].link_url ? gridData[cellIndex].link_url : '';
        
        // Prompt for URL
        const url = prompt('Enter URL for this image:', currentUrl);
        
        // Update if not cancelled
        if (url !== null) {
            if (!gridData[cellIndex]) {
                return; // No image in this cell
            }
            
            // Update or remove URL
            if (url.trim() === '') {
                delete gridData[cellIndex].link_url;
            } else {
                gridData[cellIndex].link_url = url.trim();
            }
            
            // Update visual indicator
            updateCellDisplay(cellIndex);
        }
    }
    
    function addRow(position) {
        // Prevent any issues during regeneration
        if (isRegeneratingGrid) {
            console.warn('Grid is already regenerating, skipping addRow');
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