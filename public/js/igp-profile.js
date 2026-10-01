/**
 * Public JavaScript for the Instagram-style profile page.
 *
 * Handles the post viewer: slideshow (images + video), navigation and
 * caption / like / comment display.
 */

(function() {
    'use strict';

    var dataEl = document.getElementById('igp-profile-data');
    if (!dataEl) {
        return;
    }

    var data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (e) {
        return;
    }

    var posts = data.posts || {};
    var profile = data.profile || {};
    var i18n = data.i18n || {};

    var ICONS = {
        heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78l8.84 8.84 8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        heartFilled: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
        comment: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        share: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        bookmark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        prev: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>'
    };

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Escape caption text and turn @handles into blue Instagram links.
     */
    function formatCaption(text) {
        return escapeHtml(text)
            .replace(/(^|[^\w])@([A-Za-z0-9._]+)/g, function(match, prefix, handle) {
                return prefix + '<a class="igp-mention" href="https://www.instagram.com/' + handle + '/" target="_blank" rel="noopener noreferrer">@' + handle + '</a>';
            })
            .replace(/\n/g, '<br>');
    }

    function formatCount(n) {
        n = Math.max(0, parseInt(n, 10) || 0);
        if (n < 10000) {
            return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        if (n < 1000000) {
            return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    }

    var viewer = null;
    var state = { index: null, slide: 0 };

    function buildViewer() {
        var html = '' +
            '<div class="igp-viewer-panel">' +
                '<div class="igp-viewer-media" data-media></div>' +
                '<div class="igp-viewer-info">' +
                    '<div class="igp-viewer-head" data-head></div>' +
                    '<div class="igp-viewer-body" data-body></div>' +
                    '<div class="igp-viewer-actions">' +
                        '<span data-action-heart>' + ICONS.heart + '</span>' +
                        ICONS.comment + ICONS.share +
                        '<span style="margin-left:auto">' + ICONS.bookmark + '</span>' +
                    '</div>' +
                    '<div class="igp-viewer-likes" data-likes></div>' +
                    '<div class="igp-viewer-date" data-date></div>' +
                    '<form class="igp-viewer-comment" data-comment-form>' +
                        '<input type="text" placeholder="' + escapeHtml(i18n.add_comment || 'Add a comment...') + '">' +
                        '<button type="submit">' + escapeHtml(i18n.post || 'Post') + '</button>' +
                    '</form>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="igp-viewer-close" aria-label="' + escapeHtml(i18n.close || 'Close') + '">' + ICONS.close + '</button>';

        viewer = document.createElement('div');
        viewer.className = 'igp-viewer';
        viewer.setAttribute('hidden', '');
        viewer.innerHTML = html;

        viewer.querySelector('.igp-viewer-close').addEventListener('click', close);
        viewer.addEventListener('click', function(e) {
            if (e.target === viewer) {
                close();
            }
        });
        viewer.querySelector('[data-comment-form]').addEventListener('submit', function(e) {
            e.preventDefault();
        });

        document.body.appendChild(viewer);
    }

    function renderHead() {
        var head = viewer.querySelector('[data-head]');
        var avatar = profile.avatar_url
            ? '<img src="' + escapeHtml(profile.avatar_url) + '" alt="">'
            : '<span class="igp-viewer-head-avatar">' + escapeHtml((profile.display_name || profile.username || '?').charAt(0)) + '</span>';
        head.innerHTML = avatar + '<span class="igp-viewer-username">' + escapeHtml(profile.username || '') + '</span>';
    }

    function renderSlide() {
        var mediaEl = viewer.querySelector('[data-media]');
        var post = posts[state.index];
        if (!post) {
            return;
        }

        var slides = post.media || [];
        var slide = slides[state.slide];
        if (!slide) {
            return;
        }

        mediaEl.innerHTML = '';

        var el;
        if (slide.type === 'video') {
            el = document.createElement('video');
            el.className = 'igp-viewer-slide';
            el.src = slide.url;
            el.controls = true;
            el.autoplay = true;
            el.playsInline = true;
            el.muted = true;
            if (slide.thumbnail_url) {
                el.poster = slide.thumbnail_url;
            }
        } else {
            el = document.createElement('img');
            el.className = 'igp-viewer-slide';
            el.src = slide.display_url || slide.url;
            if (slide.display_srcset) {
                el.srcset = slide.display_srcset;
                el.sizes = '(max-width: 768px) 100vw, 700px';
            }
            el.alt = slide.alt || '';
        }
        mediaEl.appendChild(el);

        if (slides.length > 1) {
            var prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'igp-viewer-nav igp-viewer-nav--prev';
            prev.setAttribute('aria-label', i18n.prev || 'Previous');
            prev.innerHTML = ICONS.prev;
            prev.addEventListener('click', prevSlide);

            var next = document.createElement('button');
            next.type = 'button';
            next.className = 'igp-viewer-nav igp-viewer-nav--next';
            next.setAttribute('aria-label', i18n.next || 'Next');
            next.innerHTML = ICONS.next;
            next.addEventListener('click', nextSlide);

            mediaEl.appendChild(prev);
            mediaEl.appendChild(next);

            var dots = document.createElement('div');
            dots.className = 'igp-viewer-dots';
            for (var i = 0; i < slides.length; i++) {
                var dot = document.createElement('span');
                dot.className = 'igp-viewer-dot' + (i === state.slide ? ' is-active' : '');
                dots.appendChild(dot);
            }
            mediaEl.appendChild(dots);
        }
    }

    function renderInfo() {
        var post = posts[state.index];
        if (!post) {
            return;
        }

        var body = viewer.querySelector('[data-body]');
        var captionHtml = '';
        if (post.caption) {
            captionHtml = '<div class="igp-viewer-caption"><span class="igp-viewer-caption-user">' +
                escapeHtml(profile.username || '') + '</span>' + formatCaption(post.caption) + '</div>';
        }
        if (post.link_url) {
            captionHtml += '<div class="igp-viewer-caption" style="margin-top:8px"><a href="' +
                escapeHtml(post.link_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(post.link_url) + '</a></div>';
        }
        if (post.comments > 0) {
            captionHtml += '<span class="igp-viewer-viewcomments">' + escapeHtml(
                (i18n.view_all ? i18n.view_all + ' ' : 'View all ') + formatCount(post.comments) + ' ' + (i18n.comments || 'comments')
            ) + '</span>';
        }
        body.innerHTML = captionHtml;

        var likes = viewer.querySelector('[data-likes]');
        likes.textContent = formatCount(post.likes || 0) + ' ' + (i18n.likes || 'likes');

        viewer.querySelector('[data-date]').textContent = post.date || '';
    }

    function open(index) {
        if (!posts[index]) {
            return;
        }

        if (!viewer) {
            buildViewer();
        }

        state.index = index;
        state.slide = 0;
        renderHead();
        renderSlide();
        renderInfo();

        viewer.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeydown);
    }

    function close() {
        if (!viewer) {
            return;
        }
        var video = viewer.querySelector('video');
        if (video) {
            video.pause();
        }
        viewer.setAttribute('hidden', '');
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKeydown);
        state.index = null;
    }

    function nextSlide() {
        var post = posts[state.index];
        if (!post || !post.media || post.media.length < 2) {
            return;
        }
        pauseVideo();
        state.slide = (state.slide + 1) % post.media.length;
        renderSlide();
    }

    function prevSlide() {
        var post = posts[state.index];
        if (!post || !post.media || post.media.length < 2) {
            return;
        }
        pauseVideo();
        state.slide = (state.slide - 1 + post.media.length) % post.media.length;
        renderSlide();
    }

    function pauseVideo() {
        var video = viewer.querySelector('video');
        if (video) {
            video.pause();
        }
    }

    function onKeydown(e) {
        if (state.index === null) {
            return;
        }
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        } else if (e.key === 'ArrowLeft') {
            prevSlide();
        }
    }

    function bindTiles() {
        var tiles = document.querySelectorAll('.igp-grid-cell[data-post-index]');
        Array.prototype.forEach.call(tiles, function(tile) {
            var index = tile.getAttribute('data-post-index');
            tile.addEventListener('click', function() {
                open(index);
            });
            tile.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open(index);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindTiles);
    } else {
        bindTiles();
    }
})();
