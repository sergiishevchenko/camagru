<?php $view = 'edit'; ?>

<div class="edit-container">
    <h2>Create Your Photo</h2>
    
    <div class="edit-wrapper">
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
                <p>Or upload an image:</p>
                <input type="file" id="file-input" accept="image/*">
            </div>
            
            <div id="preview-container" style="display: none;">
                <img id="preview" alt="Preview">
                <button id="use-preview" class="button">Use This Image</button>
                <button id="cancel-preview" class="button">Cancel</button>
            </div>

            <div class="gif-section">
                <h3>Animated GIF</h3>
                <p class="gif-hint">Add 2–30 frames, then create GIF.</p>
                <div class="gif-controls">
                    <button id="add-gif-frame" class="button" disabled>Add frame</button>
                    <button id="create-gif" class="button" disabled>Create GIF</button>
                    <button id="clear-gif-frames" class="button">Clear</button>
                </div>
                <p id="gif-frame-count" class="gif-frame-count">0 frames</p>
            </div>
        </div>
        
        <div class="overlay-section">
            <h3>Choose Overlay</h3>
            <div class="overlay-list">
                <?php if (empty($overlays)): ?>
                    <p>No overlays available. Please add overlay images to /frontend/images/overlays/</p>
                <?php else: ?>
                    <?php foreach ($overlays as $overlay): ?>
                        <div class="overlay-item" data-overlay="<?php echo e($overlay); ?>">
                            <img src="/images/overlays/<?php echo e($overlay); ?>.png" alt="<?php echo e($overlay); ?>" onerror="this.style.display='none'">
                            <span><?php echo e($overlay); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <p id="overlay-selected" class="overlay-selected" style="display: none;">Selected: <span id="selected-overlay-name"></span></p>
        </div>
    </div>
    
    <div id="message" class="message" style="display: none;"></div>
</div>

<script src="/js/edit.js"></script>
