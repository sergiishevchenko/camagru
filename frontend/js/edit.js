(function() {
    let stream = null;
    let selectedOverlay = null;
    let capturedImage = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const startBtn = document.getElementById('start-camera');
    const stopBtn = document.getElementById('stop-camera');
    const captureBtn = document.getElementById('capture');
    const fileInput = document.getElementById('file-input');
    const previewContainer = document.getElementById('preview-container');
    const preview = document.getElementById('preview');
    const usePreviewBtn = document.getElementById('use-preview');
    const cancelPreviewBtn = document.getElementById('cancel-preview');
    const overlayItems = document.querySelectorAll('.overlay-item');
    const overlaySelected = document.getElementById('overlay-selected');
    const selectedOverlayName = document.getElementById('selected-overlay-name');
    const messageDiv = document.getElementById('message');

    function showMessage(text, isError = false) {
        messageDiv.textContent = text;
        messageDiv.className = isError ? 'message error' : 'message success';
        messageDiv.style.display = 'block';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }

    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.content;
        }
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    startBtn.addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            video.srcObject = stream;
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-block';
            captureBtn.disabled = !selectedOverlay;
        } catch (err) {
            showMessage('Error accessing camera: ' + err.message, true);
        }
    });

    stopBtn.addEventListener('click', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            video.srcObject = null;
            startBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            captureBtn.disabled = true;
        }
    });

    captureBtn.addEventListener('click', function() {
        if (!selectedOverlay) {
            showMessage('Please select an overlay first', true);
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/png');
        sendImage(imageData);
    });

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!selectedOverlay) {
            showMessage('Please select an overlay first', true);
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            preview.src = event.target.result;
            previewContainer.style.display = 'block';
            capturedImage = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    usePreviewBtn.addEventListener('click', function() {
        if (capturedImage && selectedOverlay) {
            sendImage(capturedImage);
        }
    });

    cancelPreviewBtn.addEventListener('click', function() {
        previewContainer.style.display = 'none';
        fileInput.value = '';
        capturedImage = null;
    });

    overlayItems.forEach(item => {
        item.addEventListener('click', function() {
            overlayItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            selectedOverlay = this.dataset.overlay;
            selectedOverlayName.textContent = selectedOverlay;
            overlaySelected.style.display = 'block';
            captureBtn.disabled = !stream;
        });
    });

    function sendImage(imageData) {
        const token = getCSRFToken();
        if (!token) {
            showMessage('CSRF token not found', true);
            return;
        }

        captureBtn.disabled = true;
        showMessage('Processing image...', false);

        fetch('/edit/capture', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                image: imageData,
                overlay_id: selectedOverlay,
                csrf_token: token
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Image created successfully!', false);
                setTimeout(() => {
                    window.location.href = '/';
                }, 1500);
            } else {
                showMessage(data.error || 'Failed to create image', true);
                captureBtn.disabled = false;
            }
        })
        .catch(error => {
            showMessage('Error: ' + error.message, true);
            captureBtn.disabled = false;
        });
    }
})();
