<?php
function reportEvidenceMimeExtensions(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
}

function validateReportEvidenceUpload($upload, int $maxBytes): array
{
    $result = ['error' => '', 'data' => null, 'mime' => null];
    if ($upload === null) {
        return $result;
    }
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
        $result['error'] = 'Data foto bukti tidak valid.';
        return $result;
    }

    if ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        return $result;
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Ukuran foto bukti maksimal 5 MB.'
            : 'Foto bukti gagal diunggah. Silakan pilih ulang.';
        return $result;
    }

    $temporaryPath = $upload['tmp_name'] ?? null;
    $uploadSize = $upload['size'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        $result['error'] = 'File foto bukti tidak valid.';
        return $result;
    }
    if (!is_int($uploadSize) || $uploadSize < 1 || $uploadSize > $maxBytes) {
        $result['error'] = 'Ukuran foto bukti harus lebih dari 0 dan maksimal 5 MB.';
        return $result;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $imageInfo = @getimagesize($temporaryPath);
    $allowedMimeTypes = reportEvidenceMimeExtensions();
    if (!is_string($mime) || !isset($allowedMimeTypes[$mime])
        || $imageInfo === false || ($imageInfo['mime'] ?? null) !== $mime) {
        $result['error'] = 'Foto bukti harus berupa JPG, PNG, atau WebP yang valid.';
        return $result;
    }
    if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000
        || ($imageInfo[0] * $imageInfo[1]) > 40000000) {
        $result['error'] = 'Resolusi foto bukti terlalu besar.';
        return $result;
    }

    $imageData = file_get_contents($temporaryPath);
    if ($imageData === false) {
        $result['error'] = 'Foto bukti tidak dapat dibaca.';
        return $result;
    }

    $result['data'] = $imageData;
    $result['mime'] = $mime;
    return $result;
}
