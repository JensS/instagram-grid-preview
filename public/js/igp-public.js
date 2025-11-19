/**
 * Public JavaScript for Instagram Grid Preview
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initializeGrids();
    });

    function initializeGrids() {
        const grids = document.querySelectorAll('.igp-grid');
        
        grids.forEach(function(grid) {
            // Add loading class
            grid.classList.add('loading');
            
            // Wait for images to load
            const images = grid.querySelectorAll('.igp-grid-image');
            let loadedImages = 0;
            
            if (images.length === 0) {
                // No images, remove loading immediately
                grid.classList.remove('loading');
                return;
            }
            
            images.forEach(function(img) {
                if (img.complete) {
                    loadedImages++;
                    if (loadedImages === images.length) {
                        grid.classList.remove('loading');
                    }
                } else {
                    img.addEventListener('load', function() {
                        loadedImages++;
                        if (loadedImages === images.length) {
                            grid.classList.remove('loading');
                        }
                    });
                    
                    img.addEventListener('error', function() {
                        loadedImages++;
                        if (loadedImages === images.length) {
                            grid.classList.remove('loading');
                        }
                    });
                }
            });
            
            // Add intersection observer for lazy loading if needed
            if ('IntersectionObserver' in window) {
                observeGrid(grid);
            }
        });
    }

    function observeGrid(grid) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    // Grid is visible, ensure all images are loaded
                    const images = entry.target.querySelectorAll('.igp-grid-image[data-src]');
                    images.forEach(function(img) {
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                    });
                    
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        observer.observe(grid);
    }

    // Add touch support for mobile devices
    function addTouchSupport() {
        const grids = document.querySelectorAll('.igp-grid');
        
        grids.forEach(function(grid) {
            grid.addEventListener('touchstart', function(e) {
                // Add touch class for styling
                grid.classList.add('touch-active');
            });
            
            grid.addEventListener('touchend', function(e) {
                // Remove touch class
                setTimeout(function() {
                    grid.classList.remove('touch-active');
                }, 150);
            });
        });
    }

    // Initialize touch support
    addTouchSupport();

    // Utility function to refresh grids (can be called externally)
    window.igpRefreshGrids = function() {
        initializeGrids();
    };

})();