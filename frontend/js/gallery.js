(function() {
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function loadComments(imageId) {
        fetch('/comment/' + imageId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    const list = document.getElementById('comments-' + imageId);
                    if (list) {
                        list.innerHTML = '';
                        data.comments.forEach(function(comment) {
                            const div = document.createElement('div');
                            div.className = 'comment';
                            div.innerHTML = '<span class="comment-author">@' + escapeHtml(comment.username) + '</span> ' +
                                '<span class="comment-content">' + escapeHtml(comment.content) + '</span> ' +
                                '<span class="comment-date">' + formatDate(comment.created_at) + '</span>';
                            list.appendChild(div);
                        });
                    }
                }
            })
            .catch(function() {});
    }

    function buildImageCard(image, baseUrl, isAuthenticated) {
        const shareUrl = baseUrl + '/image/' + image.id;
        const escapedUsername = escapeHtml(image.username);
        const escapedFilename = escapeHtml(image.filename);
        const dateStr = formatDate(image.created_at);
        const likeCount = image.like_count || 0;
        const likedClass = image.is_liked ? ' liked' : '';
        const disabledAttr = isAuthenticated ? '' : ' disabled';
        const imageMediaUrl = baseUrl + '/uploads/' + image.filename;
        const shareBtns = '<div class="share-buttons">' +
            '<a href="https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-fb" title="Share on Facebook">f</a>' +
            '<a href="https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent('Camagru photo by @' + image.username) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-tw" title="Share on Twitter">𝕏</a>' +
            '<a href="https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(shareUrl) + '&media=' + encodeURIComponent(imageMediaUrl) + '&description=' + encodeURIComponent('Camagru photo by @' + image.username) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-pin" title="Share on Pinterest">P</a>' +
            '<a href="https://api.whatsapp.com/send?text=' + encodeURIComponent('Camagru photo by @' + image.username + ' ' + shareUrl) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-wa" title="Share on WhatsApp">W</a>' +
            '<a href="https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent('Camagru photo by @' + image.username) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-tg" title="Share on Telegram">T</a>' +
            '</div>';
        const commentForm = isAuthenticated ? '<form class="comment-form" data-image-id="' + image.id + '">' +
            '<input type="text" name="comment" placeholder="Add a comment..." required> ' +
            '<button type="submit">Post</button></form>' : '';
        return '<div class="image-card" id="image-' + image.id + '">' +
            '<div class="image-wrapper">' +
            '<a href="/image/' + image.id + '"><img src="/uploads/' + escapedFilename + '" alt="Photo by ' + escapedUsername + '"></a>' +
            '</div>' +
            '<div class="image-info">' +
            '<div class="image-meta">' +
            '<span class="username">@' + escapedUsername + '</span>' +
            '<span class="date">' + dateStr + '</span>' +
            '</div>' +
            '<div class="image-actions">' +
            '<button class="like-btn' + likedClass + '" data-image-id="' + image.id + '"' + disabledAttr + '>' +
            '<span class="like-icon">♥</span><span class="like-count">' + likeCount + '</span></button>' +
            shareBtns +
            '</div>' +
            '<div class="comments-section">' +
            '<div class="comments-list" id="comments-' + image.id + '"></div>' + commentForm +
            '</div></div></div>';
    }

    function toggleLike(btn) {
        if (btn.disabled) return;
        const imageId = btn.dataset.imageId;
        const token = getCSRFToken();
        if (!token) { window.alertModal('CSRF token not found'); return; }
        const likeCountSpan = btn.querySelector('.like-count');
        btn.disabled = true;
        fetch('/like/' + imageId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: token })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                btn.classList.toggle('liked', data.liked);
                likeCountSpan.textContent = data.count;
            } else {
                window.alertModal(data.error || 'Failed to toggle like');
            }
            btn.disabled = false;
        })
        .catch(function() {
            window.alertModal('An error occurred');
            btn.disabled = false;
        });
    }

    function submitComment(form) {
        const imageId = form.dataset.imageId;
        const input = form.querySelector('input[name="comment"]');
        const content = input.value.trim();
        if (!content) return;
        const token = getCSRFToken();
        if (!token) { window.alertModal('CSRF token not found'); return; }
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        fetch('/comment/' + imageId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: content, csrf_token: token })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                input.value = '';
                loadComments(imageId);
            } else {
                window.alertModal(data.error || 'Failed to post comment');
            }
            submitBtn.disabled = false;
        })
        .catch(function() {
            window.alertModal('An error occurred');
            submitBtn.disabled = false;
        });
    }

    var container = document.querySelector('.gallery-container');
    if (container) {
        container.addEventListener('click', function(e) {
            var target = e.target.closest('.like-btn');
            if (target) { e.preventDefault(); toggleLike(target); return; }
        });
        container.addEventListener('submit', function(e) {
            var form = e.target.closest('.comment-form');
            if (form) { e.preventDefault(); submitComment(form); }
        });
    }

    document.querySelectorAll('.image-card').forEach(function(card) {
        var id = card.id.replace('image-', '');
        if (id) loadComments(id);
    });

    var sentinel = document.getElementById('load-more-sentinel');
    var statusEl = document.getElementById('load-more-status');
    var nextPageEl = container && container.getAttribute('data-next-page');
    var nextPage = nextPageEl ? parseInt(nextPageEl, 10) : 0;
    var loading = false;

    if (sentinel && container && nextPage) {
        var baseUrl = container.getAttribute('data-base-url') || (window.location.origin + '');
        var isAuthenticated = container.getAttribute('data-authenticated') === '1';
        var grid = container.querySelector('.gallery-grid');
        var currentPageEl = document.getElementById('current-page');
        var currentPageNum = 1;
        var userInteracted = false;
        var initialScrollY = window.scrollY || window.pageYOffset || 0;
        var loadDelayMs = 450;
        var minLoadingVisibleMs = 450;
        var pendingLoadTimer = null;

        function loadMore() {
            if (loading || !nextPage || pendingLoadTimer) return;
            pendingLoadTimer = setTimeout(function() {
                pendingLoadTimer = null;
                loadMoreNow();
            }, loadDelayMs);
        }

        function loadMoreNow() {
            if (loading || !nextPage) return;
            loading = true;
            var loadingStart = Date.now();
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.textContent = 'Loading more photos...';
                statusEl.className = 'load-more-status loading';
            }
            fetch('/?page=' + nextPage + '&format=json')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var elapsed = Date.now() - loadingStart;
                    var extraDelay = Math.max(0, minLoadingVisibleMs - elapsed);
                    setTimeout(function() {
                    if (data.images && data.images.length) {
                        var totalPages = document.getElementById('total-pages');
                        var tp = totalPages ? totalPages.textContent : '?';
                        grid.insertAdjacentHTML('beforeend',
                            '<div class="page-divider" style="grid-column:1/-1"><span>Page ' + nextPage + ' of ' + tp + '</span></div>');
                        data.images.forEach(function(img) {
                            var html = buildImageCard(img, baseUrl, isAuthenticated);
                            grid.insertAdjacentHTML('beforeend', html);
                            loadComments(img.id);
                        });
                        currentPageNum = nextPage;
                        if (currentPageEl) currentPageEl.textContent = currentPageNum;
                    }
                    nextPage = data.nextPage || 0;
                    container.setAttribute('data-next-page', nextPage || '');
                    if (statusEl) {
                        if (nextPage) {
                            statusEl.textContent = 'Scroll down for more';
                            statusEl.className = 'load-more-status';
                        } else {
                            statusEl.textContent = 'You\'ve reached the end';
                            statusEl.className = 'load-more-status end';
                            statusEl.style.display = 'block';
                        }
                    }
                    loading = false;
                    }, extraDelay);
                })
                .catch(function() {
                    var elapsed = Date.now() - loadingStart;
                    var extraDelay = Math.max(0, minLoadingVisibleMs - elapsed);
                    setTimeout(function() {
                    if (statusEl) {
                        statusEl.textContent = 'Failed to load more';
                        statusEl.className = 'load-more-status';
                        statusEl.style.display = 'block';
                    }
                    loading = false;
                    }, extraDelay);
                });
        }

        function tryLoadFromViewport() {
            if (!userInteracted || loading || !nextPage) return;
            var rect = sentinel.getBoundingClientRect();
            if (rect.top <= window.innerHeight + 200) {
                loadMore();
            }
        }

        function markInteracted() {
            var currentY = window.scrollY || window.pageYOffset || 0;
            if (!userInteracted && currentY > initialScrollY + 120) {
                userInteracted = true;
            }
            tryLoadFromViewport();
        }

        var observer = new IntersectionObserver(function(entries) {
            if (!userInteracted) return;
            if (entries[0].isIntersecting && nextPage && !loading) loadMore();
        }, { rootMargin: '200px', threshold: 0 });
        observer.observe(sentinel);
        window.addEventListener('scroll', markInteracted, { passive: true });
    }
})();
