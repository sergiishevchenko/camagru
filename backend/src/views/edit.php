<?php $view = 'edit'; ?>

<div class="edit-container">
    <div class="edit-layout">
        <div class="edit-header">
            <h2>Create Your Photo</h2>
            <div class="steps-guide">
                <div class="step" id="step-1"><span class="step-num">1</span> Start camera or upload</div>
                <div class="step" id="step-2"><span class="step-num">2</span> Choose overlay</div>
                <div class="step" id="step-3"><span class="step-num">3</span> Capture</div>
            </div>
        </div>
        <div class="edit-main">
            <div class="camera-section">
                <div class="video-container">
                    <video id="video" autoplay playsinline></video>
                    <canvas id="preview-canvas" class="preview-canvas" style="display: none;"></canvas>
                    <canvas id="canvas" style="display: none;"></canvas>
                </div>
                
                <div class="controls">
                    <button id="start-camera" class="button">Start Camera</button>
                    <button id="stop-camera" class="button" style="display: none;">Stop Camera</button>
                    <button id="capture" class="button" disabled>Capture</button>
                </div>
                
                <div class="upload-section">
                    <label class="upload-dropzone" id="upload-dropzone">
                        <span class="upload-icon">&#8682;</span>
                        <span class="upload-text">Drop image here or <strong>browse</strong></span>
                        <span class="upload-hint">JPG, PNG or GIF — max 5 MB</span>
                        <input type="file" id="file-input" accept="image/*">
                    </label>
                </div>
                
                <div id="preview-container" class="preview-container" style="display: none;">
                    <div class="preview-image">
                        <img id="preview" alt="Preview">
                    </div>
                    <div class="preview-actions">
                        <button id="use-preview" class="button">Use This Image</button>
                        <button id="cancel-preview" class="button preview-cancel">Cancel</button>
                    </div>
                </div>

                <div class="gif-section">
                    <div class="gif-header">
                        <span class="gif-badge">GIF</span>
                        <div>
                            <h3>Animated GIF</h3>
                            <p class="gif-hint">Capture 2–30 frames, then create a looping GIF</p>
                        </div>
                    </div>
                    <div class="gif-controls">
                        <button id="add-gif-frame" class="button gif-btn" disabled>+ Add Frame</button>
                        <button id="create-gif" class="button gif-btn gif-btn-create" disabled>Create GIF</button>
                        <button id="clear-gif-frames" class="button gif-btn gif-btn-clear">Clear</button>
                    </div>
                    <div class="gif-progress">
                        <div class="gif-progress-bar" id="gif-progress-bar"></div>
                    </div>
                    <p id="gif-frame-count" class="gif-frame-count"><span id="gif-count-num">0</span> / 30 frames</p>
                </div>
            </div>
            
            <div class="overlay-section">
                <div class="overlay-header">
                    <div>
                        <h3>Choose Overlay</h3>
                        <p class="overlay-hint">Select an effect to apply on your photo</p>
                    </div>
                    <span id="overlay-selected" class="overlay-tag" style="display: none;">&#10003; <span id="selected-overlay-name"></span></span>
                </div>
                <div class="overlay-list">
                    <?php if (empty($overlays)): ?>
                        <p class="no-photos">No overlays available. Add PNG images to <code>frontend/images/overlays/</code></p>
                    <?php else: ?>
                        <?php foreach ($overlays as $overlay): ?>
                            <div class="overlay-item" data-overlay="<?php echo e($overlay); ?>">
                                <div class="overlay-preview">
                                    <img src="/images/overlays/<?php echo e($overlay); ?>.png" alt="<?php echo e($overlay); ?>" onerror="this.style.display='none'">
                                </div>
                                <span><?php echo e($overlay); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="edit-sidebar">
            <h3>My Photos</h3>
            <?php if (empty($userImages)): ?>
                <p class="no-photos">No photos yet.</p>
            <?php else: ?>
                <div class="edit-thumbnails">
                    <?php foreach ($userImages as $img): ?>
                        <div class="edit-thumb" id="thumb-<?php echo $img['id']; ?>">
                            <a href="/image/<?php echo $img['id']; ?>">
                                <img src="/uploads/<?php echo e($img['filename']); ?>" alt="My photo">
                            </a>
                            <button class="delete-thumb" data-image-id="<?php echo $img['id']; ?>" title="Delete">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="message" class="message-toast" style="display: none;"></div>
</div>

<script src="/js/edit.js"></script>
