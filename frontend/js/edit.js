(function() {
    let stream = null;
    let selectedOverlays = [];
    let overlayChoiceMade = false;
    let capturedImage = null;
    let capturedImageObj = null;
    let overlayImages = [];
    let previewAnimationId = null;
    let gifFrames = [];

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const previewCanvas = document.getElementById('preview-canvas');
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
    const addGifFrameBtn = document.getElementById('add-gif-frame');
    const createGifBtn = document.getElementById('create-gif');
    const clearGifFramesBtn = document.getElementById('clear-gif-frames');
    const gifFrameCountEl = document.getElementById('gif-frame-count');

    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');

    function updateSteps() {
        var hasSource = !!(stream || capturedImage);
        var hasOverlay = overlayChoiceMade;
        if (step1) {
            step1.className = hasSource ? 'step done' : 'step active';
        }
        if (step2) {
            step2.className = hasSource && hasOverlay ? 'step done' : hasSource ? 'step active' : 'step';
        }
        if (step3) {
            step3.className = hasSource && hasOverlay ? 'step active' : 'step';
        }
    }

    updateSteps();

    function showMessage(text, isError = false) {
        messageDiv.textContent = text;
        messageDiv.className = isError ? 'message-toast error' : 'message-toast success';
        messageDiv.style.display = 'block';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 4000);
    }

    function getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.content;
        }
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function getPreviewSource() {
        if (capturedImage) return capturedImage;
        if (stream && video.readyState >= 2) return video;
        return null;
    }

    function drawLivePreview() {
        if (!stream || video.readyState < 2) {
            previewCanvas.style.display = 'none';
            previewAnimationId = requestAnimationFrame(drawLivePreview);
            return;
        }
        var w = video.videoWidth;
        var h = video.videoHeight;
        if (previewCanvas.width !== w || previewCanvas.height !== h) {
            previewCanvas.width = w;
            previewCanvas.height = h;
        }
        var ctx = previewCanvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        for (var i = 0; i < overlayImages.length; i++) {
            var o = overlayImages[i];
            if (o && o.complete && o.naturalWidth) {
                ctx.drawImage(o, 0, 0, w, h);
            }
        }
        previewCanvas.style.display = 'block';
        previewAnimationId = requestAnimationFrame(drawLivePreview);
    }

    function updateUploadPreview() {
        if (!capturedImageObj || !capturedImageObj.complete || !capturedImageObj.naturalWidth) return;
        var w = capturedImageObj.naturalWidth;
        var h = capturedImageObj.naturalHeight;
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(capturedImageObj, 0, 0);
        for (var i = 0; i < overlayImages.length; i++) {
            var o = overlayImages[i];
            if (o && o.complete && o.naturalWidth) {
                ctx.drawImage(o, 0, 0, w, h);
            }
        }
        preview.src = canvas.toDataURL('image/png');
    }

    function startLivePreview() {
        if (capturedImage && capturedImageObj) {
            updateUploadPreview();
            return;
        }
        if (!previewAnimationId) {
            previewAnimationId = requestAnimationFrame(drawLivePreview);
        }
    }

    function stopLivePreview() {
        if (previewAnimationId) {
            cancelAnimationFrame(previewAnimationId);
            previewAnimationId = null;
        }
        if (previewCanvas) {
            previewCanvas.style.display = 'none';
        }
    }

    function loadOverlayForPreview(name) {
        if (!name) {
            overlayImages = [];
            stopLivePreview();
            if (capturedImage) {
                preview.src = capturedImage;
            }
            return;
        }
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            overlayImages = [img];
            startLivePreview();
        };
        img.onerror = function() {
            overlayImages = [];
        };
        img.src = '/images/overlays/' + name + '.png';
    }

    function loadOverlaysForPreview(names) {
        if (!Array.isArray(names) || names.length === 0) {
            overlayImages = [];
            stopLivePreview();
            if (capturedImage) preview.src = capturedImage;
            return;
        }
        overlayImages = [];
        var remaining = names.length;
        names.forEach(function(name) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                overlayImages.push(img);
                remaining--;
                if (remaining <= 0) startLivePreview();
            };
            img.onerror = function() {
                remaining--;
                if (remaining <= 0) startLivePreview();
            };
            img.src = '/images/overlays/' + name + '.png';
        });
    }

    function getRawFrameAsBase64() {
        const source = getPreviewSource();
        if (!source) return null;
        let w, h;
        if (source === video) {
            w = video.videoWidth;
            h = video.videoHeight;
        } else if (capturedImageObj && capturedImageObj.complete) {
            w = capturedImageObj.naturalWidth;
            h = capturedImageObj.naturalHeight;
        } else return null;
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        if (source === video) {
            ctx.drawImage(video, 0, 0);
        } else {
            ctx.drawImage(capturedImageObj, 0, 0);
        }
        return canvas.toDataURL('image/png');
    }

    var gifProgressBar = document.getElementById('gif-progress-bar');
    var gifCountNum = document.getElementById('gif-count-num');

    function updateGifUI() {
        var count = gifFrames.length;
        if (gifCountNum) gifCountNum.textContent = count;
        if (gifFrameCountEl && !gifCountNum) gifFrameCountEl.textContent = count + ' / 30 frames';
        if (createGifBtn) createGifBtn.disabled = count < 2 || count > 30;
        if (gifProgressBar) gifProgressBar.style.width = Math.min(100, (count / 30) * 100) + '%';
    }

    if (addGifFrameBtn) {
        addGifFrameBtn.addEventListener('click', function() {
            const base64 = getRawFrameAsBase64();
            if (!base64) {
                showMessage('Could not capture frame', true);
                return;
            }
            if (gifFrames.length >= 30) {
                showMessage('Maximum 30 frames', true);
                return;
            }
            gifFrames.push(base64);
            updateGifUI();
        });
    }
    if (createGifBtn) {
        createGifBtn.addEventListener('click', function() {
            if (gifFrames.length < 2) {
                showMessage('Add at least 2 frames', true);
                return;
            }
            const token = getCSRFToken();
            if (!token) {
                showMessage('CSRF token not found', true);
                return;
            }
            createGifBtn.disabled = true;
            showMessage('Creating GIF...', false);
            fetch('/edit/gif', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ frames: gifFrames, overlay_id: selectedOverlays, csrf_token: token })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showMessage('GIF created!', false);
                    setTimeout(function() { window.location.href = '/'; }, 1500);
                } else {
                    showMessage(data.error || 'Failed to create GIF', true);
                    createGifBtn.disabled = false;
                }
            })
            .catch(function(err) {
                showMessage('Error: ' + err.message, true);
                createGifBtn.disabled = false;
            });
        });
    }
    if (clearGifFramesBtn) {
        clearGifFramesBtn.addEventListener('click', function() {
            gifFrames = [];
            updateGifUI();
        });
    }

    startBtn.addEventListener('click', async function() {
        startBtn.disabled = true;
        startBtn.textContent = 'Starting...';
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Camera not supported. Use HTTPS or localhost.');
            }
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            video.srcObject = stream;
            await video.play();
            startBtn.style.display = 'none';
            stopBtn.style.display = 'inline-block';
            captureBtn.disabled = false;
            if (addGifFrameBtn) addGifFrameBtn.disabled = false;
            if (selectedOverlays.length) loadOverlaysForPreview(selectedOverlays.slice());
            updateSteps();
            showMessage('Camera ready! You can capture now.', false);
            var overlaySection = document.querySelector('.overlay-section');
            if (overlaySection) {
                setTimeout(function() {
                    overlaySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        } catch (err) {
            startBtn.disabled = false;
            startBtn.textContent = 'Start Camera';
            var msg = 'Camera error: ' + err.message;
            if (err.name === 'NotAllowedError') {
                msg = 'Camera access denied. Allow camera in browser settings and try again.';
            } else if (err.name === 'NotFoundError') {
                msg = 'No camera found. You can upload an image instead.';
            } else if (err.name === 'NotReadableError') {
                msg = 'Camera is busy (used by another app). Close it and try again.';
            }
            showMessage(msg, true);
        }
    });

    stopBtn.addEventListener('click', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            video.srcObject = null;
            startBtn.style.display = 'inline-block';
            startBtn.disabled = false;
            startBtn.textContent = 'Start Camera';
            stopBtn.style.display = 'none';
            captureBtn.disabled = true;
            if (addGifFrameBtn) addGifFrameBtn.disabled = true;
            if (!capturedImage) stopLivePreview();
            updateSteps();
        }
    });

    captureBtn.addEventListener('click', function() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        const imageData = canvas.toDataURL('image/png');
        sendImage(imageData);
    });

    var dropzone = document.getElementById('upload-dropzone');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(function(evt) {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function(evt) {
            dropzone.addEventListener(evt, function(e) {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });
        dropzone.addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            capturedImage = event.target.result;
            capturedImageObj = new Image();
            capturedImageObj.onload = function() {
                previewContainer.style.display = 'block';
                captureBtn.disabled = false;
                if (addGifFrameBtn) addGifFrameBtn.disabled = false;
                if (selectedOverlays.length) {
                    updateUploadPreview();
                } else {
                    preview.src = capturedImage;
                }
            };
            capturedImageObj.src = capturedImage;
            updateSteps();
        };
        reader.readAsDataURL(file);
    });

    usePreviewBtn.addEventListener('click', function() {
        if (fileInput.files && fileInput.files[0]) {
            sendUploadedFile(fileInput.files[0]);
        } else if (capturedImage) {
            sendImage(capturedImage);
        }
    });

    cancelPreviewBtn.addEventListener('click', function() {
        previewContainer.style.display = 'none';
        fileInput.value = '';
        capturedImage = null;
        capturedImageObj = null;
        stopLivePreview();
        updateSteps();
    });

    overlayItems.forEach(item => {
        item.addEventListener('click', function() {
            overlayChoiceMade = true;
            var val = (this.dataset.overlay || '').trim();
            if (val === '') {
                selectedOverlays = [];
                overlayItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            } else {
                var idx = selectedOverlays.indexOf(val);
                if (idx >= 0) {
                    selectedOverlays.splice(idx, 1);
                    this.classList.remove('active');
                } else {
                    selectedOverlays.push(val);
                    this.classList.add('active');
                }
                overlayItems.forEach(function(i) {
                    if ((i.dataset.overlay || '').trim() === '') i.classList.remove('active');
                });
            }

            if (selectedOverlays.length) {
                selectedOverlayName.textContent = selectedOverlays.length === 1 ? selectedOverlays[0] : (selectedOverlays.length + ' selected');
                overlaySelected.style.display = 'inline-flex';
                loadOverlaysForPreview(selectedOverlays.slice());
            } else {
                selectedOverlayName.textContent = 'None';
                overlaySelected.style.display = 'inline-flex';
                loadOverlaysForPreview([]);
            }

            captureBtn.disabled = (!stream && !capturedImage);
            if (addGifFrameBtn) addGifFrameBtn.disabled = (!stream && !capturedImage);
            updateSteps();
        });
    });

    document.querySelectorAll('.delete-thumb').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var self = this;
            var imageId = self.dataset.imageId;
            window.confirmModal('Delete this photo?').then(function(ok) {
                if (!ok) return;
                var token = getCSRFToken();
                if (!token) return;
                self.disabled = true;
                fetch('/image/' + imageId, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: token })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        var thumb = document.getElementById('thumb-' + imageId);
                        if (thumb) thumb.remove();
                    } else {
                        showMessage(data.error || 'Failed to delete', true);
                    }
                })
                .catch(function() {
                    showMessage('Error deleting image', true);
                });
            });
        });
    });

    function sendUploadedFile(file) {
        const token = getCSRFToken();
        if (!token) {
            showMessage('CSRF token not found', true);
            return;
        }

        captureBtn.disabled = true;
        if (usePreviewBtn) usePreviewBtn.disabled = true;
        showMessage('Processing image...', false);

        var formData = new FormData();
        formData.append('image', file);
        selectedOverlays.forEach(function(o) {
            formData.append('overlay_ids[]', o);
        });
        formData.append('csrf_token', token);

        fetch('/edit/upload', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    try { return JSON.parse(text); } catch(e) { throw new Error('Server error (' + response.status + ')'); }
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                showMessage('Image created!', false);
                setTimeout(function() { window.location.href = '/'; }, 1500);
            } else {
                showMessage(data.error || 'Failed to create image', true);
                captureBtn.disabled = false;
                if (usePreviewBtn) usePreviewBtn.disabled = false;
            }
        })
        .catch(function(error) {
            showMessage('Error: ' + error.message, true);
            captureBtn.disabled = false;
            if (usePreviewBtn) usePreviewBtn.disabled = false;
        });
    }

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
                overlay_id: selectedOverlays,
                csrf_token: token
            })
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    try { return JSON.parse(text); } catch(e) { throw new Error('Server error (' + response.status + ')'); }
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                showMessage('Image created!', false);
                setTimeout(function() { window.location.href = '/'; }, 1500);
            } else {
                showMessage(data.error || 'Failed to create image', true);
                captureBtn.disabled = false;
            }
        })
        .catch(function(error) {
            showMessage('Error: ' + error.message, true);
            captureBtn.disabled = false;
        });
    }
})();
