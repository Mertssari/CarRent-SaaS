<?php
/**
 * includes/upload.php
 * Secure image upload helper. Accepts only jpg/jpeg/png, validates the
 * real MIME type, and stores the file under uploads/ with a unique name.
 */

declare(strict_types=1);

const UPLOAD_DIR       = __DIR__ . '/../uploads/';   // physical path
const UPLOAD_PUBLIC    = 'uploads/';                 // web path (stored in DB)
const UPLOAD_MAX_BYTES = 3145728;                    // 3 MB

/**
 * Takes a $_FILES['x'] array, validates and stores it.
 * @return string Saved public path (e.g. "uploads/abc123.jpg")
 * @throws RuntimeException If validation fails
 */
function save_vehicle_image(array $file): string
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'Dosya sunucu limitini aşıyor (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'Dosya form limitini aşıyor.',
            UPLOAD_ERR_PARTIAL    => 'Dosya kısmen yüklendi, tekrar deneyin.',
            UPLOAD_ERR_NO_FILE    => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici yükleme klasörü yok (sunucu ayarı).',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı (izin sorunu).',
            UPLOAD_ERR_EXTENSION  => 'Bir PHP uzantısı yüklemeyi durdurdu.',
        ];
        throw new RuntimeException($map[$err] ?? 'Dosya yüklenemedi.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Dosya çok büyük (maks. 3 MB).');
    }

    // Validate the real MIME type (never trust the extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Yalnızca JPG ve PNG görselleri kabul edilir.');
    }

    // Is it really an image? (extra safety)
    if (getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Geçersiz görsel dosyası.');
    }

    $ext      = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $target   = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Dosya kaydedilemedi.');
    }

    return UPLOAD_PUBLIC . $filename;
}
