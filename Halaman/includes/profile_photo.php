<?php
function validateProfilePhotoUpload($upload, int $maxBytes): array
{
    $result = ['error' => '', 'data' => null, 'mime' => null];
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) {
        $result['error'] = 'Data foto profil tidak valid.';
        return $result;
    }

    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Ukuran foto profil maksimal 5 MB.'
            : 'Foto profil gagal diunggah. Silakan pilih ulang.';
        return $result;
    }

    $temporaryPath = $upload['tmp_name'] ?? null;
    $uploadSize = $upload['size'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        $result['error'] = 'File foto profil tidak valid.';
        return $result;
    }
    if (!is_int($uploadSize) || $uploadSize < 1 || $uploadSize > $maxBytes) {
        $result['error'] = 'Ukuran foto profil harus lebih dari 0 dan maksimal 5 MB.';
        return $result;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $imageInfo = @getimagesize($temporaryPath);
    if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
        || $imageInfo === false || ($imageInfo['mime'] ?? null) !== $mime) {
        $result['error'] = 'Foto profil harus berupa JPG, PNG, atau WebP yang valid.';
        return $result;
    }
    if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000
        || ($imageInfo[0] * $imageInfo[1]) > 40000000) {
        $result['error'] = 'Resolusi foto profil terlalu besar.';
        return $result;
    }

    $imageData = file_get_contents($temporaryPath);
    if ($imageData === false) {
        $result['error'] = 'Foto profil tidak dapat dibaca.';
        return $result;
    }

    $result['data'] = $imageData;
    $result['mime'] = $mime;
    return $result;
}
