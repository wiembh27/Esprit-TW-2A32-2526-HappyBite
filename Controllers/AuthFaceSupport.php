<?php

declare(strict_types=1);

/**
 * Capture visage (JPEG) : enregistrement + comparaison simple (GD), même idée que commande.php.
 * La comparaison est heuristique (cosinus sur image réduite), pas de biométrie certifiée.
 */

const AUTH_FACE_GRID = 64;

/** Seuil cosinus : même personne, pas photo pixel-par-pixel. */
const AUTH_FACE_MIN_COSINE = 0.22;

function authFaceDecodeSnapshot(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('#^data:image/(?:jpeg|jpg);base64,(.+)$#i', $raw, $m)) {
        $bin = base64_decode($m[1], true);
        return $bin !== false && $bin !== '' ? $bin : null;
    }
    return null;
}

/** Grille luminance 64×64 à partir d’une ressource GD déjà chargée. */
function authFaceRawLumaFromGdImage($im, int $srcX = 0, int $srcY = 0, ?int $srcW = null, ?int $srcH = null): ?array
{
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w < 1 || $h < 1) {
        return null;
    }
    $srcW = $srcW ?? $w;
    $srcH = $srcH ?? $h;
    $srcX = max(0, min($srcX, $w - 1));
    $srcY = max(0, min($srcY, $h - 1));
    $srcW = max(1, min($srcW, $w - $srcX));
    $srcH = max(1, min($srcH, $h - $srcY));

    $tw = AUTH_FACE_GRID;
    $th = AUTH_FACE_GRID;
    $tmp = imagecreatetruecolor($tw, $th);
    if ($tmp === false) {
        return null;
    }
    imagecopyresampled($tmp, $im, 0, 0, $srcX, $srcY, $tw, $th, $srcW, $srcH);

    $vec = [];
    for ($y = 0; $y < $th; $y++) {
        for ($x = 0; $x < $tw; $x++) {
            $rgb = imagecolorat($tmp, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            $vec[] = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;
        }
    }
    imagedestroy($tmp);

    return $vec === [] ? null : $vec;
}

/** Grille luminance 64×64 (image entière). */
function authFaceRawLumaFromJpegBytes(string $jpegBytes): ?array
{
    if (!function_exists('imagecreatefromstring')) {
        return null;
    }
    $im = @imagecreatefromstring($jpegBytes);
    if ($im === false) {
        return null;
    }
    $vec = authFaceRawLumaFromGdImage($im);
    imagedestroy($im);

    return $vec;
}

/**
 * Carré centré-haut = zone visage (indépendant du cadrage client).
 * Même logique que le recadrage côté navigateur.
 */
function authFaceRawLumaFaceFocusFromJpegBytes(string $jpegBytes, float $sideFrac = 0.9): ?array
{
    if (!function_exists('imagecreatefromstring')) {
        return null;
    }
    $im = @imagecreatefromstring($jpegBytes);
    if ($im === false) {
        return null;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $side = (int) round(min($w, $h) * $sideFrac);
    $side = max(8, min($side, $w, $h));
    $x0 = (int) max(0, ($w - $side) / 2);
    $y0 = (int) max(0, ($h - $side) * 0.05);
    if ($y0 + $side > $h) {
        $y0 = max(0, $h - $side);
    }
    $vec = authFaceRawLumaFromGdImage($im, $x0, $y0, $side, $side);
    imagedestroy($im);

    return $vec;
}

/** @param list<float> $vec */
function authFaceZscore(array $vec): array
{
    $n = count($vec);
    if ($n === 0) {
        return [];
    }
    $mean = array_sum($vec) / $n;
    $var = 0.0;
    foreach ($vec as $v) {
        $var += ($v - $mean) * ($v - $mean);
    }
    $std = sqrt($var / $n + 1e-8);
    $out = [];
    foreach ($vec as $v) {
        $out[] = ($v - $mean) / $std;
    }
    return $out;
}

/** Retournement horizontal sur une grille 64×64 (luminance brute). */
function authFaceFlipH64(array $vec): array
{
    $tw = AUTH_FACE_GRID;
    $th = AUTH_FACE_GRID;
    $out = [];
    for ($y = 0; $y < $th; $y++) {
        for ($x = 0; $x < $tw; $x++) {
            $out[] = $vec[$y * $tw + ($tw - 1 - $x)];
        }
    }
    return $out;
}

/** @return list<float>|null */
function authFaceVectorFromJpegBytes(string $jpegBytes): ?array
{
    $raw = authFaceRawLumaFromJpegBytes($jpegBytes);
    if ($raw === null) {
        return null;
    }
    $z = authFaceZscore($raw);
    return $z === [] ? null : $z;
}

function authFaceCosineSimilarity(array $a, array $b): float
{
    $n = min(count($a), count($b));
    if ($n < 1) {
        return 0.0;
    }
    $dot = 0.0;
    $na = 0.0;
    $nb = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $dot += $a[$i] * $b[$i];
        $na += $a[$i] * $a[$i];
        $nb += $b[$i] * $b[$i];
    }
    if ($na < 1e-10 || $nb < 1e-10) {
        return 0.0;
    }
    return $dot / (sqrt($na) * sqrt($nb));
}

/** Zone centrale (visage) sur la grille 64×64 — ignore vêtements / fond sur les bords. */
function authFaceCenterCropRaw(array $vec, float $fracW = 0.72, float $fracH = 0.78): array
{
    $tw = AUTH_FACE_GRID;
    $th = AUTH_FACE_GRID;
    $cw = max(8, (int) round($tw * $fracW));
    $ch = max(8, (int) round($th * $fracH));
    $x0 = (int) floor(($tw - $cw) / 2);
    $y0 = (int) floor(($th - $ch) / 2);
    $out = [];
    for ($y = $y0; $y < $y0 + $ch; $y++) {
        for ($x = $x0; $x < $x0 + $cw; $x++) {
            $out[] = $vec[$y * $tw + $x];
        }
    }
    return $out;
}

/** @param list<array>|null $extraA @param list<array>|null $extraB */
function authFaceBestSimilarity(array $rawA, array $rawB, ?array $extraA = null, ?array $extraB = null): float
{
    $variantsA = array_values(array_filter(array_merge(
        [$rawA, authFaceCenterCropRaw($rawA)],
        $extraA ?? []
    )));
    $variantsB = array_values(array_filter(array_merge(
        [$rawB, authFaceCenterCropRaw($rawB)],
        $extraB ?? []
    )));

    $best = 0.0;
    foreach ($variantsA as $a) {
        foreach ($variantsB as $b) {
            $pairs = [
                [$a, $b],
                [$a, authFaceFlipH64($b)],
                [authFaceFlipH64($a), $b],
                [authFaceFlipH64($a), authFaceFlipH64($b)],
            ];
            foreach ($pairs as [$va, $vb]) {
                $za = authFaceZscore($va);
                $zb = authFaceZscore($vb);
                if ($za === [] || $zb === []) {
                    continue;
                }
                $best = max($best, authFaceCosineSimilarity($za, $zb));
            }
        }
    }

    return $best;
}

function authFaceMatchStored(string $absolutePath, string $newJpegBytes, ?float $minCosine = null): bool
{
    if (!is_file($absolutePath)) {
        return false;
    }
    $stored = @file_get_contents($absolutePath);
    if ($stored === false || $stored === '') {
        return false;
    }
    $rawA = authFaceRawLumaFromJpegBytes($stored);
    $rawB = authFaceRawLumaFromJpegBytes($newJpegBytes);
    if ($rawA === null || $rawB === null) {
        return false;
    }

    $focusA = authFaceRawLumaFaceFocusFromJpegBytes($stored);
    $focusB = authFaceRawLumaFaceFocusFromJpegBytes($newJpegBytes);
    $extraA = $focusA !== null ? [$focusA] : [];
    $extraB = $focusB !== null ? [$focusB] : [];

    $threshold = $minCosine ?? AUTH_FACE_MIN_COSINE;

    return authFaceBestSimilarity($rawA, $rawB, $extraA, $extraB) >= $threshold;
}

/** Ré-enregistre le JPEG en carré centré-haut (visage) pour des comparaisons stables. */
function authFaceNormalizeEnrollmentJpeg(string $jpegBytes): string
{
    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        return $jpegBytes;
    }
    $im = @imagecreatefromstring($jpegBytes);
    if ($im === false) {
        return $jpegBytes;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $side = (int) round(min($w, $h) * 0.9);
    $side = max(8, min($side, $w, $h));
    $x0 = (int) max(0, ($w - $side) / 2);
    $y0 = (int) max(0, ($h - $side) * 0.05);
    if ($y0 + $side > $h) {
        $y0 = max(0, $h - $side);
    }
    $crop = imagecreatetruecolor($side, $side);
    if ($crop === false) {
        imagedestroy($im);
        return $jpegBytes;
    }
    imagecopy($crop, $im, 0, 0, $x0, $y0, $side, $side);
    imagedestroy($im);
    ob_start();
    imagejpeg($crop, null, 90);
    imagedestroy($crop);
    $out = ob_get_clean();

    return is_string($out) && $out !== '' ? $out : $jpegBytes;
}

function authFaceDescriptorPath(int $userId): string
{
    return dirname(__DIR__) . '/uploads/face_auth/' . $userId . '.json';
}

/** @return list<float>|null */
function authFaceParseDescriptor(string $raw): ?array
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $vec = json_decode($raw, true);
    if (!is_array($vec) || count($vec) < 64) {
        return null;
    }
    $out = [];
    foreach ($vec as $v) {
        if (!is_numeric($v)) {
            return null;
        }
        $out[] = (float) $v;
    }

    return count($out) >= 64 ? $out : null;
}

/** @param list<float> $vec */
function authFaceSaveDescriptor(int $userId, array $vec): bool
{
    $path = authFaceDescriptorPath($userId);
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }

    $json = json_encode($vec);
    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

/** @return list<float>|null */
function authFaceLoadDescriptor(int $userId): ?array
{
    $path = authFaceDescriptorPath($userId);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }

    return authFaceParseDescriptor($raw);
}

/** Compare des empreintes 64×64 (z-score), sans extension GD. */
function authFaceMatchDescriptorVectors(array $stored, array $live, ?float $minCosine = null): bool
{
    $min = $minCosine ?? AUTH_FACE_MIN_COSINE;
    $variantsA = [$stored];
    $variantsB = [$live];
    if (count($stored) === AUTH_FACE_GRID * AUTH_FACE_GRID) {
        $variantsA[] = authFaceCenterCropRaw($stored);
    }
    if (count($live) === AUTH_FACE_GRID * AUTH_FACE_GRID) {
        $variantsB[] = authFaceCenterCropRaw($live);
    }

    $best = 0.0;
    $gridN = AUTH_FACE_GRID * AUTH_FACE_GRID;
    foreach ($variantsA as $a) {
        foreach ($variantsB as $b) {
            if (count($a) !== count($b)) {
                continue;
            }
            $pairs = [[$a, $b]];
            if (count($a) === $gridN) {
                $pairs = [
                    [$a, $b],
                    [$a, authFaceFlipH64($b)],
                    [authFaceFlipH64($a), $b],
                    [authFaceFlipH64($a), authFaceFlipH64($b)],
                ];
            }
            foreach ($pairs as [$va, $vb]) {
                $best = max($best, authFaceCosineSimilarity($va, $vb));
            }
        }
    }

    return $best >= $min;
}

function authFaceSaveEnrollmentFile(int $userId, string $jpegBinary): ?string
{
    if (function_exists('imagecreatefromstring')) {
        $jpegBinary = authFaceNormalizeEnrollmentJpeg($jpegBinary);
    }
    $root = dirname(__DIR__) . '/uploads/face_auth/';
    if (!is_dir($root)) {
        if (!@mkdir($root, 0755, true)) {
            return null;
        }
    }
    $rel = 'uploads/face_auth/' . $userId . '.jpg';
    $abs = dirname(__DIR__) . '/' . $rel;
    if (file_put_contents($abs, $jpegBinary) === false) {
        return null;
    }
    return $rel;
}
