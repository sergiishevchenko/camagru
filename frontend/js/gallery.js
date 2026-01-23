(function() {
    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.content;
        }
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function loadComments(imageId) {
        fetch(`/comment/${imageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsList = document.getElementById(`comments-${imageId}`);
                    if (commentsList) {
                        commentsList.innerHTML = '';
                        data.comments.forEach(comment => {
                            const commentDiv = document.createElement('div');
                            commentDiv.className = 'comment';
                            commentDiv.innerHTML = `
                                <span class="comment-author">@${escapeHtml(comment.username)}</span>
                                <span class="comment-content">${escapeHtml(comment.content)}</span>
                                <span class="comment-date">${formatDate(comment.created_at)}</span>
                            `;
                            commentsList.appendChild(commentDiv);
                        });
                    }
                }
            })
            .catch(error => console.error('Error loading comments:', error));
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;

            const imageId = this.dataset.imageId;
            const token = getCSRFToken();
            
            if (!token) {
                alert('CSRF token not found');
                return;
            }

            const wasLiked = this.classList.contains('liked');
            const likeCountSpan = this.querySelector('.like-count');
            const currentCount = parseInt(likeCountSpan.textContent) || 0;

            this.disabled = true;

            fetch(`/like/${imageId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: token
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('liked', data.liked);
                    likeCountSpan.textContent = data.count;
                } else {
                    alert(data.error || 'Failed to toggle like');
                }
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                this.disabled = false;
            });
        });
    });

    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const imageId = this.dataset.imageId;
            const input = this.querySelector('input[name="comment"]');
            const content = input.value.trim();
            
            if (!content) return;

            const token = getCSRFToken();
            if (!token) {
                alert('CSRF token not found');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            fetch(`/comment/${imageId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content: content,
                    csrf_token: token
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(imageId);
                } else {
                    alert(data.error || 'Failed to post comment');
                }
                submitBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                submitBtn.disabled = false;
            });
        });
    });

    document.querySelectorAll('.delete-image').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            const imageId = this.dataset.imageId;
            const token = getCSRFToken();
            
            if (!token) {
                alert('CSRF token not found');
                return;
            }

            this.disabled = true;

            fetch(`/image/${imageId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: token
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const imageCard = document.getElementById(`image-${imageId}`);
                    if (imageCard) {
                        imageCard.remove();
                    }
                } else {
                    alert(data.error || 'Failed to delete image');
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                this.disabled = false;
            });
        });
    });

    document.querySelectorAll('.image-card').forEach(card => {
        const imageId = card.id.replace('image-', '');
        loadComments(imageId);
    });
})();
