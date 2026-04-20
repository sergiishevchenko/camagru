<?php

function resizeDown($srcImage, $maxDim = 1920) {
    $w = imagesx($srcImage);
    $h = imagesy($srcImage);
    if ($w <= 0 || $h <= 0) return $srcImage;
    if ($w <= $maxDim && $h <= $maxDim) return $srcImage;
    $scale = ($w >= $h) ? ($maxDim / $w) : ($maxDim / $h);
    $nw = max(1, (int) floor($w * $scale));
    $nh = max(1, (int) floor($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagealphablending($dst, true);
    imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($srcImage);
    return $dst;
}

function flattenToOpaque($srcImage) {
    $w = imagesx($srcImage);
    $h = imagesy($srcImage);
    $flat = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $white);
    imagealphablending($flat, true);
    imagecopy($flat, $srcImage, 0, 0, 0, 0, $w, $h);
    imagedestroy($srcImage);
    return $flat;
}

function applyOverlay($sourceImage, $overlayId) {
    if (empty($overlayId)) return $sourceImage;
    $ids = [];
    if (is_array($overlayId)) {
        $ids = $overlayId;
    } else {
        $ids = explode(',', (string)$overlayId);
    }
    $clean = [];
    foreach ($ids as $id) {
        $id = trim((string)$id);
        if ($id !== '') $clean[] = $id;
    }
    if (empty($clean)) return $sourceImage;

    $sw = imagesx($sourceImage);
    $sh = imagesy($sourceImage);

    foreach ($clean as $one) {
        $overlayPath = __DIR__ . '/../../public/images/overlays/' . $one . '.png';
        if (!file_exists($overlayPath)) continue;

        $overlayImage = imagecreatefrompng($overlayPath);
        if ($overlayImage === false) continue;

        $ow = imagesx($overlayImage);
        $oh = imagesy($overlayImage);

        $resized = imagecreatetruecolor($sw, $sh);
        imagesavealpha($resized, true);
        $trans = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $trans);
        imagecopyresampled($resized, $overlayImage, 0, 0, 0, 0, $sw, $sh, $ow, $oh);
        imagedestroy($overlayImage);

        imagealphablending($sourceImage, true);
        imagecopy($sourceImage, $resized, 0, 0, 0, 0, $sw, $sh);
        imagedestroy($resized);
    }

    return $sourceImage;
}

function processImageWithOverlay($base64Image, $overlayId = null) {
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (empty($base64Image)) {
        return ['success' => false, 'error' => 'Empty image data received'];
    }

    $stripped = preg_replace('#^data:image/[a-zA-Z0-9+.-]+;base64,#i', '', $base64Image);
    $imageData = base64_decode($stripped, true);
    if ($imageData === false || strlen($imageData) < 8) {
        return ['success' => false, 'error' => 'Invalid base64 image data (len=' . strlen($base64Image) . ')'];
    }

    $sourceImage = @imagecreatefromstring($imageData);
    if ($sourceImage === false) {
        return ['success' => false, 'error' => 'Unsupported image format (decoded=' . strlen($imageData) . ' bytes, header=' . bin2hex(substr($imageData, 0, 4)) . ')'];
    }

    $sourceImage = resizeDown($sourceImage);
    $sourceImage = flattenToOpaque($sourceImage);
    $sourceImage = applyOverlay($sourceImage, $overlayId);

    $filename = uniqid('img_', true) . '.png';
    $filepath = $uploadDir . $filename;

    if (!imagepng($sourceImage, $filepath)) {
        imagedestroy($sourceImage);
        return ['success' => false, 'error' => 'Failed to save image'];
    }

    imagedestroy($sourceImage);

    return ['success' => true, 'filename' => $filename];
}

function processUploadedImage($uploadedFile, $overlayId = null) {
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
            finfo_close($finfo);
        }
    }
    if (empty($mimeType)) {
        $mimeType = $uploadedFile['type'] ?? '';
    }
    if (empty($mimeType)) {
        $ext = strtolower(pathinfo($uploadedFile['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') $mimeType = 'image/jpeg';
        elseif ($ext === 'png') $mimeType = 'image/png';
        elseif ($ext === 'gif') $mimeType = 'image/gif';
    }

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    $maxSize = 5 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $sourceImage = null;
    if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
        $sourceImage = imagecreatefromjpeg($uploadedFile['tmp_name']);
    } elseif ($mimeType === 'image/png') {
        $sourceImage = imagecreatefrompng($uploadedFile['tmp_name']);
    } elseif ($mimeType === 'image/gif') {
        $sourceImage = imagecreatefromgif($uploadedFile['tmp_name']);
    }

    if ($sourceImage === false) {
        return ['success' => false, 'error' => 'Failed to create image from file'];
    }

    $sourceImage = resizeDown($sourceImage);
    $sourceImage = flattenToOpaque($sourceImage);
    $sourceImage = applyOverlay($sourceImage, $overlayId);

    $filename = uniqid('img_', true) . '.png';
    $filepath = $uploadDir . $filename;

    if (!imagepng($sourceImage, $filepath)) {
        imagedestroy($sourceImage);
        return ['success' => false, 'error' => 'Failed to save image'];
    }

    imagedestroy($sourceImage);

    return ['success' => true, 'filename' => $filename];
}

function getAvailableOverlays() {
    $overlayDir = __DIR__ . '/../../public/images/overlays/';
    $overlays = [];
    
    if (is_dir($overlayDir)) {
        $files = scandir($overlayDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                $overlays[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
    }
    
    return $overlays;
}

function createAnimatedGif(array $base64Frames, $overlayId = null, $delayTicks = 10) {
    if (!class_exists('Imagick')) {
        return ['success' => false, 'error' => 'Imagick extension is required for GIF creation'];
    }
    if (count($base64Frames) < 2 || count($base64Frames) > 30) {
        return ['success' => false, 'error' => 'Between 2 and 30 frames required'];
    }
    $overlayBlobs = [];
    if (!empty($overlayId)) {
        $ids = [];
        if (is_array($overlayId)) {
            $ids = $overlayId;
        } else {
            $ids = explode(',', (string)$overlayId);
        }
        foreach ($ids as $one) {
            $one = trim((string)$one);
            if ($one === '') continue;
            $overlayPath = __DIR__ . '/../../public/images/overlays/' . $one . '.png';
            if (!file_exists($overlayPath)) {
                return ['success' => false, 'error' => 'Overlay not found'];
            }
            $overlayBlobs[] = file_get_contents($overlayPath);
        }
    }
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    try {
        $gif = new Imagick();
        $gif->setFormat('gif');
        foreach ($base64Frames as $base64) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
            if ($data === false) {
                return ['success' => false, 'error' => 'Invalid frame data'];
            }
            $frame = new Imagick();
            $frame->readImageBlob($data);
            if (!empty($overlayBlobs)) {
                foreach ($overlayBlobs as $blob) {
                    $overlay = new Imagick();
                    $overlay->readImageBlob($blob);
                    $overlay->resizeImage($frame->getImageWidth(), $frame->getImageHeight(), Imagick::FILTER_LANCZOS, 1);
                    $frame->compositeImage($overlay, Imagick::COMPOSITE_OVER, 0, 0);
                    $overlay->destroy();
                }
            }
            $frame->setImageDelay($delayTicks);
            $frame->setImageFormat('gif');
            $gif->addImage($frame);
            $frame->destroy();
        }
        $filename = uniqid('gif_', true) . '.gif';
        $filepath = $uploadDir . $filename;
        $gif->writeImages($filepath, true);
        $gif->destroy();
        return ['success' => true, 'filename' => $filename];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to create GIF: ' . $e->getMessage()];
    }
}
