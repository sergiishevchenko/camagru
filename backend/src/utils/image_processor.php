<?php

function processImageWithOverlay($base64Image, $overlayId) {
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $overlayPath = __DIR__ . '/../../../frontend/images/overlays/' . $overlayId . '.png';
    if (!file_exists($overlayPath)) {
        return ['success' => false, 'error' => 'Overlay not found'];
    }

    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Invalid image data'];
    }

    $sourceImage = imagecreatefromstring($imageData);
    if ($sourceImage === false) {
        return ['success' => false, 'error' => 'Failed to create image from data'];
    }

    $overlayImage = imagecreatefrompng($overlayPath);
    if ($overlayImage === false) {
        imagedestroy($sourceImage);
        return ['success' => false, 'error' => 'Failed to load overlay'];
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $overlayWidth = imagesx($overlayImage);
    $overlayHeight = imagesy($overlayImage);

    $x = ($sourceWidth - $overlayWidth) / 2;
    $y = ($sourceHeight - $overlayHeight) / 2;

    imagealphablending($sourceImage, true);
    imagesavealpha($sourceImage, true);
    imagecopy($sourceImage, $overlayImage, (int)$x, (int)$y, 0, 0, $overlayWidth, $overlayHeight);

    $filename = uniqid('img_', true) . '.png';
    $filepath = $uploadDir . $filename;

    if (!imagepng($sourceImage, $filepath)) {
        imagedestroy($sourceImage);
        imagedestroy($overlayImage);
        return ['success' => false, 'error' => 'Failed to save image'];
    }

    imagedestroy($sourceImage);
    imagedestroy($overlayImage);

    return ['success' => true, 'filename' => $filename];
}

function processUploadedImage($uploadedFile, $overlayId) {
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    $maxSize = 5 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $overlayPath = __DIR__ . '/../../../frontend/images/overlays/' . $overlayId . '.png';
    if (!file_exists($overlayPath)) {
        return ['success' => false, 'error' => 'Overlay not found'];
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

    $overlayImage = imagecreatefrompng($overlayPath);
    if ($overlayImage === false) {
        imagedestroy($sourceImage);
        return ['success' => false, 'error' => 'Failed to load overlay'];
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $overlayWidth = imagesx($overlayImage);
    $overlayHeight = imagesy($overlayImage);

    $x = ($sourceWidth - $overlayWidth) / 2;
    $y = ($sourceHeight - $overlayHeight) / 2;

    imagealphablending($sourceImage, true);
    imagesavealpha($sourceImage, true);
    imagecopy($sourceImage, $overlayImage, (int)$x, (int)$y, 0, 0, $overlayWidth, $overlayHeight);

    $filename = uniqid('img_', true) . '.png';
    $filepath = $uploadDir . $filename;

    if (!imagepng($sourceImage, $filepath)) {
        imagedestroy($sourceImage);
        imagedestroy($overlayImage);
        return ['success' => false, 'error' => 'Failed to save image'];
    }

    imagedestroy($sourceImage);
    imagedestroy($overlayImage);

    return ['success' => true, 'filename' => $filename];
}

function getAvailableOverlays() {
    $overlayDir = __DIR__ . '/../../../frontend/images/overlays/';
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
