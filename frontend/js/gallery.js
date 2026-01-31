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
            .catch(function(e) { console.error('Error loading comments:', e); });
    }

    function buildImageCard(image, baseUrl, isAuthenticated) {
        const shareUrl = baseUrl + '/image/' + image.id;
        const escapedUsername = escapeHtml(image.username);
        const escapedFilename = escapeHtml(image.filename);
        const dateStr = formatDate(image.created_at);
        const likeCount = image.like_count || 0;
        const likedClass = image.is_liked ? ' liked' : '';
        const disabledAttr = isAuthenticated ? '' : ' disabled';
        const deleteBtn = image.is_owner ? '<button class="delete-image" data-image-id="' + image.id + '">×</button>' : '';
        const shareBtns = '<div class="share-buttons">' +
            '<a href="https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-fb" title="Share on Facebook">f</a>' +
            '<a href="https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent('Camagru photo by @' + image.username) + '" target="_blank" rel="noopener noreferrer" class="share-btn share-tw" title="Share on Twitter">𝕏</a>' +
            '</div>';
        const commentForm = isAuthenticated ? '<form class="comment-form" data-image-id="' + image.id + '">' +
            '<input type="text" name="comment" placeholder="Add a comment..." required> ' +
            '<button type="submit">Post</button></form>' : '';
        return '<div class="image-card" id="image-' + image.id + '">' +
            '<div class="image-wrapper">' +
            '<img src="/uploads/' + escapedFilename + '" alt="Photo by ' + escapedUsername + '">' + deleteBtn +
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
        if (!token) { alert('CSRF token not found'); return; }
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
                alert(data.error || 'Failed to toggle like');
            }
            btn.disabled = false;
        })
        .catch(function() {
            alert('An error occurred');
            btn.disabled = false;
        });
    }

    function submitComment(form) {
        const imageId = form.dataset.imageId;
        const input = form.querySelector('input[name="comment"]');
        const content = input.value.trim();
        if (!content) return;
        const token = getCSRFToken();
        if (!token) { alert('CSRF token not found'); return; }
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
                alert(data.error || 'Failed to post comment');
            }
            submitBtn.disabled = false;
        })
        .catch(function() {
            alert('An error occurred');
            submitBtn.disabled = false;
        });
    }

    function deleteImage(btn) {
        if (!confirm('Are you sure you want to delete this image?')) return;
        const imageId = btn.dataset.imageId;
        const token = getCSRFToken();
        if (!token) { alert('CSRF token not found'); return; }
        btn.disabled = true;
        fetch('/image/' + imageId, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: token })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                const card = document.getElementById('image-' + imageId);
                if (card) card.remove();
            } else {
                alert(data.error || 'Failed to delete image');
                btn.disabled = false;
            }
        })
        .catch(function() {
            alert('An error occurred');
            btn.disabled = false;
        });
    }

    var container = document.querySelector('.gallery-container');
    if (container) {
        container.addEventListener('click', function(e) {
            var target = e.target.closest('.like-btn');
            if (target) { e.preventDefault(); toggleLike(target); return; }
            target = e.target.closest('.delete-image');
            if (target) { e.preventDefault(); deleteImage(target); return; }
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

        function loadMore() {
            if (loading || !nextPage) return;
            loading = true;
            if (statusEl) {
                statusEl.style.display = 'block';
                statusEl.textContent = 'Loading...';
            }
            fetch('/?page=' + nextPage + '&format=json')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.images && data.images.length) {
                        data.images.forEach(function(img) {
                            var html = buildImageCard(img, baseUrl, isAuthenticated);
                            grid.insertAdjacentHTML('beforeend', html);
                            loadComments(img.id);
                        });
                    }
                    nextPage = data.nextPage || 0;
                    container.setAttribute('data-next-page', nextPage || '');
                    if (statusEl) {
                        statusEl.textContent = nextPage ? '' : 'No more photos';
                        if (!nextPage) statusEl.style.display = 'block';
                    }
                    loading = false;
                })
                .catch(function() {
                    if (statusEl) {
                        statusEl.textContent = 'Failed to load more';
                        statusEl.style.display = 'block';
                    }
                    loading = false;
                });
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting && nextPage && !loading) loadMore();
        }, { rootMargin: '200px', threshold: 0 });
        observer.observe(sentinel);
    }
})();
