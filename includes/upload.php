<?php
function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a file.',
        default => 'The file could not be uploaded.',
    };
}

function has_upload(string $field): bool
{
    return isset($_FILES[$field]) && is_array($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/** Normalise $_FILES['x'] with multiple files into a list. */
function upload_list(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f['name'])) return [];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

function load_image_resource(string $tmp, string $mime)
{
    $im = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        default => false,
    };
    if (!$im) throw new RuntimeException('That image could not be read. Please try a different file.');
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($tmp);
        $or = (int) ($ex['Orientation'] ?? 1);
        if ($or === 3) $im = imagerotate($im, 180, 0);
        elseif ($or === 6) $im = imagerotate($im, -90, 0);
        elseif ($or === 8) $im = imagerotate($im, 90, 0);
    }
    return $im;
}

/**
 * Validate, resize and re-encode an uploaded image (strips any hidden payload).
 * Returns a path relative to the site root, e.g. uploads/products/202610-ab12cd.jpg
 */
function upload_image(array $f, string $subdir, int $maxW = 1600, string $destRoot = 'uploads'): string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error_message((int) ($f['error'] ?? 4)));
    if (!is_uploaded_file($f['tmp_name']) && PHP_SAPI !== 'cli') throw new RuntimeException('Invalid upload.');
    if ($f['size'] > 10 * 1024 * 1024) throw new RuntimeException('Image is too large (maximum 10MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Please upload a JPG, PNG or WebP image.');

    $src = load_image_resource($f['tmp_name'], $mime);
    $w = imagesx($src);
    $h = imagesy($src);
    $nw = min($w, $maxW);
    $nh = (int) round($h * $nw / $w);
    $png = $mime === 'image/png';
    $dst = imagecreatetruecolor($nw, $nh);
    if ($png) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    } else {
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $dir = ROOT . '/' . $destRoot . '/' . $subdir;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = date('Ym') . '-' . bin2hex(random_bytes(6)) . ($png ? '.png' : '.jpg');
    $ok = $png ? imagepng($dst, "$dir/$name", 7) : imagejpeg($dst, "$dir/$name", 84);
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) throw new RuntimeException('Could not save the image. Check that the uploads folder is writable.');
    return $destRoot . '/' . $subdir . '/' . $name;
}

/** Payment receipts go to storage/proofs (not publicly reachable). Returns the file name. */
function upload_proof(array $f): string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error_message((int) ($f['error'] ?? 4)));
    if (!is_uploaded_file($f['tmp_name']) && PHP_SAPI !== 'cli') throw new RuntimeException('Invalid upload.');
    if ($f['size'] > 8 * 1024 * 1024) throw new RuntimeException('File is too large (maximum 8MB).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if ($mime === 'application/pdf') {
        $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
        if ($head !== '%PDF-') throw new RuntimeException('That PDF could not be read.');
        $name = date('Ym') . '-' . bin2hex(random_bytes(8)) . '.pdf';
        if (!move_uploaded_file($f['tmp_name'], ROOT . '/storage/proofs/' . $name) && !copy($f['tmp_name'], ROOT . '/storage/proofs/' . $name)) {
            throw new RuntimeException('Could not save the file.');
        }
        return $name;
    }
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Please upload a screenshot/photo (JPG, PNG) or a PDF receipt.');
    return basename(upload_image($f, 'proofs', 1800, 'storage'));
}

function delete_upload(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) return;
    $f = ROOT . '/' . $path;
    if (is_file($f)) @unlink($f);
}
